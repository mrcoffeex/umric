<?php

use App\Models\Announcement;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function announcementAdmin(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->admin()->create(['user_id' => $user->id]);

    return $user;
}

test('admin can attach up to five thumbnail photos when creating an announcement', function () {
    Storage::fake('public');

    $admin = announcementAdmin();
    $files = collect(range(1, 5))
        ->map(fn (int $i) => UploadedFile::fake()->image("photo-{$i}.jpg"))
        ->all();

    $this->actingAs($admin)
        ->post(route('admin.announcements.store'), [
            'title' => 'Campus open house',
            'content' => 'Join us this Friday.',
            'type' => 'info',
            'is_pinned' => false,
            'is_active' => true,
            'target_roles' => ['student'],
            'published_at' => now()->toDateTimeString(),
            'thumbnails' => $files,
        ])
        ->assertRedirect();

    $announcement = Announcement::query()->where('title', 'Campus open house')->first();

    expect($announcement)->not->toBeNull()
        ->and($announcement->thumbnails)->toHaveCount(5);

    foreach ($announcement->thumbnails as $path) {
        Storage::disk('public')->assertExists($path);
    }
});

test('announcement thumbnails cannot exceed five photos', function () {
    Storage::fake('public');

    $admin = announcementAdmin();
    $files = collect(range(1, 6))
        ->map(fn (int $i) => UploadedFile::fake()->image("photo-{$i}.jpg"))
        ->all();

    $this->actingAs($admin)
        ->post(route('admin.announcements.store'), [
            'title' => 'Too many photos',
            'content' => 'Should fail validation.',
            'type' => 'info',
            'is_active' => true,
            'thumbnails' => $files,
        ])
        ->assertSessionHasErrors('thumbnails');

    expect(Announcement::query()->where('title', 'Too many photos')->exists())->toBeFalse();
});

test('admin can keep and replace thumbnails when updating an announcement', function () {
    Storage::fake('public');

    $admin = announcementAdmin();
    $kept = UploadedFile::fake()->image('keep.jpg')->store('announcements/thumbnails', 'public');
    $remove = UploadedFile::fake()->image('remove.jpg')->store('announcements/thumbnails', 'public');

    $announcement = Announcement::factory()->create([
        'created_by' => $admin->id,
        'thumbnails' => [$kept, $remove],
    ]);

    $replacement = UploadedFile::fake()->image('new.jpg');

    $this->actingAs($admin)
        ->put(route('admin.announcements.update', $announcement), [
            'title' => $announcement->title,
            'content' => $announcement->content,
            'type' => $announcement->type,
            'is_pinned' => $announcement->is_pinned,
            'is_active' => $announcement->is_active,
            'target_roles' => $announcement->target_roles,
            'published_at' => optional($announcement->published_at)->toDateTimeString(),
            'expires_at' => optional($announcement->expires_at)->toDateTimeString(),
            'keep_thumbnails' => [$kept],
            'thumbnails' => [$replacement],
        ])
        ->assertRedirect();

    $announcement->refresh();

    expect($announcement->thumbnails)->toHaveCount(2)
        ->and($announcement->thumbnails[0])->toBe($kept);

    Storage::disk('public')->assertExists($kept);
    Storage::disk('public')->assertMissing($remove);
    Storage::disk('public')->assertExists($announcement->thumbnails[1]);
});

test('deleting an announcement removes its thumbnail files', function () {
    Storage::fake('public');

    $admin = announcementAdmin();
    $path = UploadedFile::fake()->image('gone.jpg')->store('announcements/thumbnails', 'public');

    $announcement = Announcement::factory()->create([
        'created_by' => $admin->id,
        'thumbnails' => [$path],
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.announcements.destroy', $announcement))
        ->assertRedirect();

    expect(Announcement::query()->whereKey($announcement->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($path);
});
