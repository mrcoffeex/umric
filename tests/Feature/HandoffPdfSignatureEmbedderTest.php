<?php

use App\Models\DocumentTransmission;
use App\Models\DocumentTransmissionItem;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\HandoffPdfSignatureEmbedder;
use Illuminate\Support\Facades\Storage;

test('e-signature is embedded into a PDF on the public disk', function () {
    $this->withoutVite();
    Storage::fake('public');

    $tcpdf = new TCPDF;
    $tcpdf->setPrintHeader(false);
    $tcpdf->setPrintFooter(false);
    $tcpdf->AddPage();
    $tcpdf->Write(0, 'Test content');
    $pdfContent = $tcpdf->Output('', 'S');
    expect($pdfContent)->toBeString()->not->toBe('');

    $path = 'document-handoffs/test-embed/doc.pdf';
    Storage::disk('public')->put($path, $pdfContent);
    $sizeBefore = Storage::disk('public')->size($path);

    $sender = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $sender->id]);
    $receiver = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $receiver->id]);

    $trans = DocumentTransmission::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'purpose' => 'P',
        'share_token' => str_repeat('a', 48),
        'status' => DocumentTransmission::STATUS_PENDING,
    ]);
    $item = DocumentTransmissionItem::create([
        'document_transmission_id' => $trans->id,
        'label' => 'L',
        'file_path' => $path,
        'file_name' => 'file.pdf',
        'file_size' => strlen($pdfContent),
        'disk' => 'public',
        'sort_order' => 0,
    ]);

    $pngB64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
    $sigPath = 'document-handoffs/esignatures/'.$trans->id.'/sig.png';
    Storage::disk('public')->put($sigPath, (string) base64_decode($pngB64, true));

    $embedder = app(HandoffPdfSignatureEmbedder::class);
    $ok = $embedder->embedPngOnLastPage($item, $sigPath, 'Test User', now());
    expect($ok)->toBeTrue();

    $item->refresh();
    expect(Storage::disk('public')->size($path))->toBe($item->file_size);
    expect($item->file_size)->toBeInt()->toBeGreaterThan(0);
    expect($item->file_size === $sizeBefore)
        ->toBeFalse("embedded PDF should differ in size from the original; before={$sizeBefore}, after={$item->file_size}");

    expect($item->pdf_esignature_embed_count)->toBe(1);

    $sizeAfterFirst = Storage::disk('public')->size($path);
    $ok2 = $embedder->embedPngOnLastPage($item->fresh(), $sigPath, 'Second Signer', now()->addMinute());
    expect($ok2)->toBeTrue();

    $item->refresh();
    expect($item->pdf_esignature_embed_count)->toBe(2);
    expect(Storage::disk('public')->size($path))->toBe($item->file_size);
    expect($item->file_size)->toBeInt()->toBeGreaterThan($sizeAfterFirst);
});
