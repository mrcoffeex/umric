<?php

use App\Models\Announcement;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

function boardUser(string $role): User
{
    $user = User::factory()->create();
    UserProfile::factory()->{$role}()->create(['user_id' => $user->id]);

    return $user;
}

test('faculty can view announcements targeted to them', function () {
    $this->withoutVite();

    $faculty = boardUser('faculty');
    $admin = boardUser('admin');

    Announcement::factory()->create([
        'title' => 'Faculty briefing',
        'content' => 'Please join the panel orientation.',
        'is_active' => true,
        'published_at' => now(),
        'target_roles' => ['faculty'],
        'created_by' => $admin->id,
    ]);

    Announcement::factory()->create([
        'title' => 'Students only',
        'content' => 'Not for faculty.',
        'is_active' => true,
        'published_at' => now(),
        'target_roles' => ['student'],
        'created_by' => $admin->id,
    ]);

    $this->actingAs($faculty)
        ->get(route('announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Announcements/Index')
            ->has('announcements', 1)
            ->where('announcements.0.title', 'Faculty briefing')
            ->where('role', 'faculty')
        );
});

test('students can view announcements targeted to them', function () {
    $this->withoutVite();

    $student = boardUser('student');
    $admin = boardUser('admin');

    Announcement::factory()->create([
        'title' => 'All-campus notice',
        'content' => 'Applies to everyone.',
        'is_active' => true,
        'published_at' => now(),
        'target_roles' => null,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($student)
        ->get(route('announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Announcements/Index')
            ->has('announcements', 1)
            ->where('announcements.0.title', 'All-campus notice')
        );
});

test('inactive announcements are hidden from the board', function () {
    $this->withoutVite();

    $faculty = boardUser('faculty');
    $admin = boardUser('admin');

    Announcement::factory()->create([
        'title' => 'Draft',
        'is_active' => false,
        'published_at' => now(),
        'target_roles' => ['faculty'],
        'created_by' => $admin->id,
    ]);

    $this->actingAs($faculty)
        ->get(route('announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Announcements/Index')
            ->has('announcements', 0)
        );
});
