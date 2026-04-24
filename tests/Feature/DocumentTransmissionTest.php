<?php

use App\Models\DocumentTransmission;
use App\Models\DocumentTransmissionHistory;
use App\Models\DocumentTransmissionItem;
use App\Models\DocumentTransmissionItemActivity;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access document handoffs', function () {
    $this->get(route('document-transmissions.index'))->assertRedirect(route('login'));
});

test('users can create a handoff and receiver can complete checklist', function () {
    $this->withoutVite();

    $sender = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $sender->id]);

    $receiver = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $receiver->id]);

    $this->actingAs($sender)
        ->post(route('document-transmissions.store'), [
            'receiver_id' => $receiver->id,
            'purpose' => 'Routing slip for defense packet.',
            'items' => [
                ['label' => 'Signed form'],
                ['label' => 'Manuscript'],
            ],
        ])
        ->assertRedirect();

    $transmission = DocumentTransmission::query()->first();
    expect($transmission)->not->toBeNull()
        ->and($transmission->status)->toBe(DocumentTransmission::STATUS_PENDING)
        ->and($transmission->items)->toHaveCount(2);

    expect(
        DocumentTransmissionHistory::query()
            ->where('document_transmission_id', $transmission->id)
            ->where('event', DocumentTransmissionHistory::EVENT_HANDOFF_CREATED)
            ->count(),
    )->toBe(1);

    $this->actingAs($receiver)
        ->get(route('document-transmissions.show', $transmission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('DocumentTransmissions/Show')
            ->where('isReceiver', true)
            ->has('handoffHistory', 1));

    $ordered = $transmission->items()->orderBy('sort_order')->get();
    expect($ordered)->toHaveCount(2);

    $first = $ordered->first();
    $second = $ordered->last();
    expect($first)->not->toBeNull()
        ->and($second)->not->toBeNull();

    $this->actingAs($receiver)
        ->post(route('document-transmissions.receive', $transmission), [
            'item_ids' => [$first->id],
        ])
        ->assertRedirect();

    $transmission->refresh();
    expect($transmission->status)->toBe(DocumentTransmission::STATUS_PENDING)
        ->and($first->fresh()->received_at)->not->toBeNull()
        ->and($second->fresh()->received_at)->toBeNull();

    expect(
        DocumentTransmissionHistory::query()
            ->where('document_transmission_id', $transmission->id)
            ->count(),
    )->toBe(2);

    $this->actingAs($receiver)
        ->post(route('document-transmissions.receive', $transmission), [
            'item_ids' => [$first->id, $second->id],
        ])
        ->assertRedirect();

    $transmission->refresh();
    expect($transmission->status)->toBe(DocumentTransmission::STATUS_COMPLETED)
        ->and($transmission->completed_at)->not->toBeNull();

    expect(
        DocumentTransmissionHistory::query()
            ->where('document_transmission_id', $transmission->id)
            ->count(),
    )->toBe(3);

    $events = DocumentTransmissionHistory::query()
        ->where('document_transmission_id', $transmission->id)
        ->orderBy('created_at')
        ->pluck('event')
        ->all();

    expect($events)->toBe([
        DocumentTransmissionHistory::EVENT_HANDOFF_CREATED,
        DocumentTransmissionHistory::EVENT_RECEIPT_CONFIRMED,
        DocumentTransmissionHistory::EVENT_RECEIPT_CONFIRMED,
    ]);
});

test('handoff can store optional pdf per line and recipient can download', function () {
    $this->withoutVite();

    $sender = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $sender->id]);

    $receiver = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $receiver->id]);

    $pdf = UploadedFile::fake()->create('routing.pdf', 200, 'application/pdf');

    $this->actingAs($sender)
        ->post(route('document-transmissions.store'), [
            'receiver_id' => $receiver->id,
            'purpose' => 'Digital routing.',
            'items' => [
                ['label' => 'Cover', 'file' => $pdf],
                ['label' => 'Memo only'],
            ],
        ])
        ->assertRedirect();

    $transmission = DocumentTransmission::query()->first();
    expect($transmission)->not->toBeNull();

    $withFile = $transmission->items()->orderBy('sort_order')->first();
    expect($withFile->file_path)->not->toBeNull()
        ->and($withFile->file_name)->toBe('routing.pdf')
        ->and($withFile->disk)->not->toBeNull();

    $downloadResponse = $this->actingAs($receiver)
        ->get(route('document-transmissions.items.file', [$transmission, $withFile]));
    $downloadResponse->assertOk();
    expect($downloadResponse->headers->get('Content-Disposition'))->toContain('attachment');

    $previewResponse = $this->actingAs($receiver)
        ->get(route('document-transmissions.items.file', [$transmission, $withFile, 'preview' => true]));
    $previewResponse->assertOk();
    expect($previewResponse->headers->get('Content-Disposition'))->toContain('inline');

    $outsider = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $outsider->id]);

    $this->actingAs($outsider)
        ->get(route('document-transmissions.items.file', [$transmission, $withFile]))
        ->assertForbidden();
});

test('sender cannot confirm receipt', function () {
    $sender = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $sender->id]);

    $receiver = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $receiver->id]);

    $transmission = DocumentTransmission::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'purpose' => 'Test',
        'share_token' => 'test-token-'.str_repeat('x', 40),
        'status' => DocumentTransmission::STATUS_PENDING,
    ]);

    $item = DocumentTransmissionItem::create([
        'document_transmission_id' => $transmission->id,
        'label' => 'Doc A',
        'sort_order' => 0,
    ]);

    $this->actingAs($sender)
        ->post(route('document-transmissions.receive', $transmission), [
            'item_ids' => [$item->id],
        ])
        ->assertForbidden();
});

test('document handoff index supports search direction and status filters', function () {
    $this->withoutVite();

    $receiver = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $receiver->id]);

    $alice = User::factory()->create([
        'name' => 'Alice Zeta',
        'email' => 'alice.zeta@example.test',
    ]);
    UserProfile::factory()->student()->create(['user_id' => $alice->id]);

    $bob = User::factory()->create(['name' => 'Bob Other']);
    UserProfile::factory()->student()->create(['user_id' => $bob->id]);

    DocumentTransmission::create([
        'sender_id' => $alice->id,
        'receiver_id' => $receiver->id,
        'purpose' => 'UniqueSearchTermAlpha routing packet',
        'share_token' => 'tok-a-'.str_repeat('a', 40),
        'status' => DocumentTransmission::STATUS_PENDING,
    ]);

    DocumentTransmission::create([
        'sender_id' => $bob->id,
        'receiver_id' => $receiver->id,
        'purpose' => 'Unrelated other purpose',
        'share_token' => 'tok-b-'.str_repeat('b', 40),
        'status' => DocumentTransmission::STATUS_PENDING,
    ]);

    $this->actingAs($receiver)
        ->get(route('document-transmissions.index', [
            'direction' => 'incoming',
            'q' => 'UniqueSearchTermAlpha',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('DocumentTransmissions/Index')
            ->where('direction', 'incoming')
            ->where('handoffs.total', 1)
            ->where('counts.incoming.total', 2));

    $this->actingAs($receiver)
        ->get(route('document-transmissions.index', [
            'direction' => 'incoming',
            'q' => 'alice.zeta@example',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('handoffs.total', 1));

    $this->actingAs($alice)
        ->get(route('document-transmissions.index', ['direction' => 'outgoing']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('direction', 'outgoing')
            ->where('handoffs.total', 1));

    $this->actingAs($receiver)
        ->get(route('document-transmissions.index', [
            'direction' => 'incoming',
            'status' => 'pending',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('handoffs.total', 2));
});

test('create handoff rejects duplicate document lines and duplicate pending handoffs', function () {
    $this->withoutVite();

    $sender = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $sender->id]);

    $receiver = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $receiver->id]);

    $this->actingAs($sender)
        ->post(route('document-transmissions.store'), [
            'receiver_id' => $receiver->id,
            'purpose' => 'Test duplicate lines.',
            'items' => [
                ['label' => '  Same Title  '],
                ['label' => 'SAME title'],
            ],
        ])
        ->assertSessionHasErrors('items');

    $this->actingAs($sender)
        ->post(route('document-transmissions.store'), [
            'receiver_id' => $receiver->id,
            'purpose' => 'First handoff',
            'items' => [
                ['label' => 'A'],
                ['label' => 'B'],
            ],
        ])
        ->assertRedirect();

    $this->actingAs($sender)
        ->post(route('document-transmissions.store'), [
            'receiver_id' => $receiver->id,
            'purpose' => 'Second same bundle',
            'items' => [
                ['label' => 'b'],
                ['label' => 'a'],
            ],
        ])
        ->assertSessionHasErrors('items');
});

test('completing handoff allows forward and copies item events', function () {
    $this->withoutVite();

    $sender = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $sender->id]);

    $receiver = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $receiver->id]);

    $next = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $next->id]);

    $this->actingAs($sender)
        ->post(route('document-transmissions.store'), [
            'receiver_id' => $receiver->id,
            'purpose' => 'Packet A',
            'items' => [
                ['label' => 'Form'],
            ],
        ])
        ->assertRedirect();

    $t = DocumentTransmission::query()->first();
    $item = $t->items()->first();
    expect($item->activities()->where('event', DocumentTransmissionItemActivity::EVENT_ADDED)->count())->toBe(1);

    $this->actingAs($receiver)
        ->post(route('document-transmissions.receive', $t), [
            'item_ids' => [$item->id],
        ])
        ->assertRedirect();

    $t->refresh();
    expect($t->status)->toBe(DocumentTransmission::STATUS_COMPLETED);

    $this->actingAs($receiver)
        ->get(route('document-transmissions.forward.create', $t))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('DocumentTransmissions/Forward'));

    $this->actingAs($receiver)
        ->post(route('document-transmissions.forward.store', $t), [
            'receiver_id' => $next->id,
            'purpose' => 'FWD: Packet A',
            'item_ids' => [$item->id],
        ])
        ->assertRedirect();

    $new = DocumentTransmission::query()
        ->where('forwarded_from_id', $t->id)
        ->first();
    expect($new)->not->toBeNull()
        ->and($new->status)->toBe(DocumentTransmission::STATUS_PENDING)
        ->and($new->items)->toHaveCount(1)
        ->and($new->items->first()->source_item_id)->toBe($item->id);

    $out = DocumentTransmissionItemActivity::query()
        ->where('document_transmission_item_id', $item->id)
        ->where('event', DocumentTransmissionItemActivity::EVENT_FORWARDED_OUT)
        ->count();
    $inn = DocumentTransmissionItemActivity::query()
        ->where('document_transmission_item_id', $new->items->first()->id)
        ->where('event', DocumentTransmissionItemActivity::EVENT_FORWARDED_IN)
        ->count();
    expect($out)->toBe(1)
        ->and($inn)->toBe(1);

    $next->refresh();
    $outAct = DocumentTransmissionItemActivity::query()
        ->where('document_transmission_item_id', $item->id)
        ->where('event', DocumentTransmissionItemActivity::EVENT_FORWARDED_OUT)
        ->first();
    expect($outAct?->meta['to_receiver_id'])->toBe($next->id)
        ->and($outAct?->meta['to_receiver_name'])->toBe($next->name)
        ->and($outAct?->meta['to_receiver_email'])->toBe($next->email);
});

test('forward can include a subset of documents', function () {
    $this->withoutVite();

    $sender = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $sender->id]);

    $receiver = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $receiver->id]);

    $next = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $next->id]);

    $this->actingAs($sender)
        ->post(route('document-transmissions.store'), [
            'receiver_id' => $receiver->id,
            'purpose' => 'Multi',
            'items' => [
                ['label' => 'Keep'],
                ['label' => 'Skip'],
            ],
        ])
        ->assertRedirect();

    $t = DocumentTransmission::query()->first();
    $ordered = $t->items()->orderBy('sort_order')->get();
    expect($ordered)->toHaveCount(2);
    $keep = $ordered->first();
    $skip = $ordered->last();
    expect($keep)->not->toBeNull()
        ->and($skip)->not->toBeNull();

    $this->actingAs($receiver)
        ->post(route('document-transmissions.receive', $t), [
            'item_ids' => [$keep->id, $skip->id],
        ])
        ->assertRedirect();

    $t->refresh();
    expect($t->status)->toBe(DocumentTransmission::STATUS_COMPLETED);

    $this->actingAs($receiver)
        ->post(route('document-transmissions.forward.store', $t), [
            'receiver_id' => $next->id,
            'purpose' => 'Subset forward',
            'item_ids' => [$keep->id],
        ])
        ->assertRedirect();

    $new = DocumentTransmission::query()
        ->where('forwarded_from_id', $t->id)
        ->first();
    expect($new)->not->toBeNull()
        ->and($new->items)->toHaveCount(1)
        ->and($new->items->first()->label)->toBe('Keep');
});

test('original sender can forward a completed handoff', function () {
    $this->withoutVite();

    $sender = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $sender->id]);

    $receiver = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $receiver->id]);

    $next = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $next->id]);

    $this->actingAs($sender)
        ->post(route('document-transmissions.store'), [
            'receiver_id' => $receiver->id,
            'purpose' => 'Packet B',
            'items' => [
                ['label' => 'Only doc'],
            ],
        ])
        ->assertRedirect();

    $t = DocumentTransmission::query()->first();
    $item = $t->items()->first();

    $this->actingAs($receiver)
        ->post(route('document-transmissions.receive', $t), [
            'item_ids' => [$item->id],
        ])
        ->assertRedirect();

    $t->refresh();
    expect($t->status)->toBe(DocumentTransmission::STATUS_COMPLETED);

    $this->actingAs($sender)
        ->post(route('document-transmissions.forward.store', $t), [
            'receiver_id' => $next->id,
            'purpose' => 'FWD from sender',
            'item_ids' => [$item->id],
        ])
        ->assertRedirect();

    expect(
        DocumentTransmission::query()
            ->where('forwarded_from_id', $t->id)
            ->where('receiver_id', $next->id)
            ->count(),
    )->toBe(1);
});

test('incomplete handoff cannot be forwarded', function () {
    $sender = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $sender->id]);

    $receiver = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $receiver->id]);

    $t = DocumentTransmission::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'purpose' => 'P',
        'share_token' => 'tok-'.str_repeat('q', 42),
        'status' => DocumentTransmission::STATUS_PENDING,
    ]);
    DocumentTransmissionItem::create([
        'document_transmission_id' => $t->id,
        'label' => 'Doc',
        'sort_order' => 0,
    ]);

    $this->actingAs($receiver)
        ->get(route('document-transmissions.forward.create', $t))
        ->assertForbidden();
});

test('claim route redirects to handoff for authorized user', function () {
    $sender = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $sender->id]);

    $receiver = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $receiver->id]);

    $transmission = DocumentTransmission::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'purpose' => 'Test',
        'share_token' => 'claim-token-'.str_repeat('y', 38),
        'status' => DocumentTransmission::STATUS_PENDING,
    ]);

    $this->actingAs($receiver)
        ->get(route('document-transmissions.claim', ['token' => $transmission->share_token]))
        ->assertRedirect(route('document-transmissions.show', $transmission));
});
