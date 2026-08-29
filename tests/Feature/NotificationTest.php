<?php

use App\Mail\ResearchStatusUpdated;
use App\Models\Announcement;
use App\Models\ResearchPaper;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\NewAnnouncementNotification;
use App\Notifications\ResearchPaperUpdatedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

function studentWithProfile(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $user->id]);

    return $user;
}

function adminWithProfile(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->admin()->create(['user_id' => $user->id]);

    return $user;
}

it('notifies target role users when an announcement is published', function () {
    Notification::fake();

    $admin = adminWithProfile();
    $student = studentWithProfile();
    $otherStudent = studentWithProfile();

    $this->actingAs($admin)
        ->post(route('admin.announcements.store'), [
            'title' => 'Defense week schedule',
            'content' => 'Please prepare your manuscripts.',
            'type' => 'info',
            'is_pinned' => false,
            'is_active' => true,
            'target_roles' => ['student'],
            'published_at' => now()->toDateTimeString(),
        ])
        ->assertRedirect();

    Notification::assertSentTo(
        $student,
        NewAnnouncementNotification::class,
        fn (NewAnnouncementNotification $notification, array $channels) => in_array('mail', $channels, true)
            && in_array('database', $channels, true),
    );
    Notification::assertSentTo($otherStudent, NewAnnouncementNotification::class);
    Notification::assertNotSentTo($admin, NewAnnouncementNotification::class);
});

it('stores an in-app notification immediately when an announcement is published', function () {
    $admin = adminWithProfile();
    $student = studentWithProfile();

    $this->actingAs($admin)
        ->post(route('admin.announcements.store'), [
            'title' => 'Immediate notice',
            'content' => 'You should see this in the bell right away.',
            'type' => 'info',
            'is_pinned' => false,
            'is_active' => true,
            'target_roles' => ['student'],
            'published_at' => now()->toDateTimeString(),
        ])
        ->assertRedirect();

    $notification = $student->fresh()->unreadNotifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->data['category'])->toBe('announcement')
        ->and($notification->data['title'])->toBe('Immediate notice')
        ->and($notification->data['body'])->toContain('You should see this');
});

it('does not notify users when an announcement is inactive', function () {
    Notification::fake();

    $admin = adminWithProfile();
    $student = studentWithProfile();

    $this->actingAs($admin)
        ->post(route('admin.announcements.store'), [
            'title' => 'Draft only',
            'content' => 'Hidden draft',
            'type' => 'info',
            'is_pinned' => false,
            'is_active' => false,
            'target_roles' => ['student'],
        ])
        ->assertRedirect();

    Notification::assertNotSentTo($student, NewAnnouncementNotification::class);
});

it('notifies paper proponents when research status is updated', function () {
    Mail::fake();
    Notification::fake();

    $lead = studentWithProfile();
    $co = studentWithProfile();

    $paper = ResearchPaper::factory()->create([
        'user_id' => $lead->id,
        'proponents' => [
            ['id' => (string) $lead->id, 'name' => $lead->name],
            ['id' => (string) $co->id, 'name' => $co->name],
        ],
    ]);

    ResearchStatusUpdated::dispatch(
        $paper->fresh(),
        'ric_review',
        'RIC/Admin Review',
        'approved',
        'Looks good.',
    );

    Notification::assertSentTo($lead, ResearchPaperUpdatedNotification::class);
    Notification::assertSentTo($co, ResearchPaperUpdatedNotification::class);
    Mail::assertQueued(ResearchStatusUpdated::class, 2);
});

it('lists notifications for the authenticated user', function () {
    $student = studentWithProfile();
    $announcement = Announcement::factory()->create([
        'title' => 'Campus update',
        'content' => 'Library hours change next week.',
        'is_active' => true,
        'published_at' => now(),
        'created_by' => adminWithProfile()->id,
    ]);

    $student->notifyNow(new NewAnnouncementNotification($announcement));

    $this->actingAs($student)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Notifications/Index')
            ->has('notifications.data', 1)
            ->where('notifications.data.0.title', 'Campus update')
            ->where('notifications.data.0.body', 'Library hours change next week.')
        );
});

it('marks a notification as read', function () {
    $student = studentWithProfile();
    $announcement = Announcement::factory()->create([
        'title' => 'Read me',
        'is_active' => true,
        'published_at' => now(),
        'created_by' => adminWithProfile()->id,
    ]);

    $student->notifyNow(new NewAnnouncementNotification($announcement));
    $notification = $student->notifications()->first();

    expect($notification->read_at)->toBeNull();

    $this->actingAs($student)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('returns a notification feed as json', function () {
    $student = studentWithProfile();
    $announcement = Announcement::factory()->create([
        'title' => 'Feed item',
        'is_active' => true,
        'published_at' => now(),
        'created_by' => adminWithProfile()->id,
    ]);

    $student->notifyNow(new NewAnnouncementNotification($announcement));

    $this->actingAs($student)
        ->getJson(route('notifications.feed'))
        ->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonCount(1, 'notifications');
});

it('shares unread notification count on authenticated pages', function () {
    $student = studentWithProfile();
    $announcement = Announcement::factory()->create([
        'title' => 'Shared count',
        'is_active' => true,
        'published_at' => now(),
        'created_by' => adminWithProfile()->id,
    ]);

    $student->notifyNow(new NewAnnouncementNotification($announcement));

    $this->actingAs($student)
        ->get(route('student.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.unreadCount', 1)
        );
});
