<?php

use App\Models\Announcement;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\NewAnnouncementNotification;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('faculty dashboard surfaces unread announcements in insights', function () {
    $this->withoutVite();

    $faculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $faculty->id]);

    $announcement = Announcement::factory()->create([
        'title' => 'Faculty board note',
        'target_roles' => ['faculty'],
        'is_active' => true,
        'published_at' => now(),
    ]);

    $faculty->notifyNow(new NewAnnouncementNotification($announcement));

    $this->actingAs($faculty)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('announcements')
            ->where('insights.actions', fn ($actions) => collect($actions)->contains(
                fn ($action) => ($action['id'] ?? null) === 'new-announcement'
                    && ($action['description'] ?? null) === 'Faculty board note'
            ))
        );
});

test('authenticated students are redirected from dashboard to student home', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('student.home'));
});

test('faculty dashboard includes papers from enrolled students', function () {
    $this->withoutVite();

    $faculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $faculty->id]);

    $student = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $student->id]);

    $class = SchoolClass::factory()->create(['faculty_id' => $faculty->id]);

    DB::table('school_class_members')->insert([
        'school_class_id' => $class->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Paper by the enrolled student (no school_class_id set — should still appear)
    ResearchPaper::factory()->create(['user_id' => $student->id]);

    $this->actingAs($faculty)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.totalPapers', 1)
            ->where('stats.totalStudents', 1)
        );
});

test('faculty dashboard submissions over time groups by month', function () {
    $this->withoutVite();

    $faculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $faculty->id]);

    $student = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $student->id]);

    $class = SchoolClass::factory()->create(['faculty_id' => $faculty->id]);

    DB::table('school_class_members')->insert([
        'school_class_id' => $class->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ResearchPaper::factory()->create([
        'user_id' => $student->id,
    ]);

    $this->actingAs($faculty)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('submissionsOverTime')
            ->loadDeferredProps('charts', fn (Assert $reload) => $reload
                ->has('submissionsOverTime', 6)
                ->where('submissionsOverTime', fn ($rows) => collect($rows)->sum('count') === 1)
            )
        );
});

test('faculty dashboard excludes papers from students in other faculty classes', function () {
    $this->withoutVite();

    $faculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $faculty->id]);

    $otherFaculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $otherFaculty->id]);

    $student = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $student->id]);

    $otherClass = SchoolClass::factory()->create(['faculty_id' => $otherFaculty->id]);

    DB::table('school_class_members')->insert([
        'school_class_id' => $otherClass->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ResearchPaper::factory()->create(['user_id' => $student->id]);

    // Faculty with no classes — should see 0 papers
    $this->actingAs($faculty)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.totalPapers', 0)
        );
});
