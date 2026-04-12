<?php

use App\Models\Category;
use App\Models\ResearchPaper;
use App\Models\User;

test('guests cannot access papers index', function () {
    $this->get(route('papers.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view papers index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('papers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Research/Index'));
});

test('authenticated users can view create paper form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('papers.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Research/Create'));
});

test('authenticated users can store a research paper', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('papers.store'), [
            'title' => 'Test Research Paper',
            'abstract' => 'A detailed abstract of the test research paper.',
            'category_id' => $category->id,
            'proponents' => [['id' => $user->id, 'name' => $user->name]],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('research_papers', [
        'title' => 'Test Research Paper',
        'user_id' => $user->id,
        'category_id' => $category->id,
        'status' => 'submitted',
    ]);

    $paper = ResearchPaper::where('title', 'Test Research Paper')->first();
    expect($paper->tracking_id)->toStartWith('RP-');
    expect($paper->trackingRecords)->toHaveCount(1);
});

test('paper store validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('papers.store'), [
            'proponents' => [['id' => $user->id, 'name' => $user->name]],
        ])
        ->assertSessionHasErrors(['title', 'abstract']);
});

test('authenticated users can view their paper', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $paper = ResearchPaper::factory()->recycle([$user, $category])->create();

    $this->actingAs($user)
        ->get(route('papers.show', $paper))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Research/Show')
            ->has('paper')
        );
});

test('paper owner can update their paper', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $paper = ResearchPaper::factory()->recycle([$user, $category])->create();

    $this->actingAs($user)
        ->patch(route('papers.update', $paper), [
            'title' => 'Updated Title',
        ])
        ->assertRedirect();

    expect($paper->fresh()->title)->toBe('Updated Title');
});

test('non-owner cannot update paper', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $category = Category::factory()->create();
    $paper = ResearchPaper::factory()->recycle([$owner, $category])->create();

    $this->actingAs($other)
        ->patch(route('papers.update', $paper), [
            'title' => 'Hacked Title',
        ])
        ->assertForbidden();
});

test('paper owner can delete their paper', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $paper = ResearchPaper::factory()->recycle([$user, $category])->create();

    $this->actingAs($user)
        ->delete(route('papers.destroy', $paper))
        ->assertRedirect(route('papers.index'));

    $this->assertSoftDeleted('research_papers', ['id' => $paper->id]);
});

test('non-owner cannot delete paper', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $category = Category::factory()->create();
    $paper = ResearchPaper::factory()->recycle([$owner, $category])->create();

    $this->actingAs($other)
        ->delete(route('papers.destroy', $paper))
        ->assertForbidden();
});

test('public tracking page is accessible without auth', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $paper = ResearchPaper::factory()->recycle([$user, $category])->create();

    $this->get(route('papers.publicTracking', $paper->tracking_id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Research/PublicTracking'));
});

test('public tracking returns 404 for invalid tracking id', function () {
    $this->get(route('papers.publicTracking', 'RP-INVALID99'))
        ->assertNotFound();
});

test('dashboard shows authenticated user stats', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    ResearchPaper::factory(3)->recycle([$user, $category])->create(['status' => 'published']);
    ResearchPaper::factory(2)->recycle([$user, $category])->create(['status' => 'under_review']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('stats')
            ->has('recentPapers')
            ->has('statusCounts')
        );
});
