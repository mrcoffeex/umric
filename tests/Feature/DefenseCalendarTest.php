<?php

use App\Models\PanelDefense;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function makeCalendarAdmin(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->admin()->create(['user_id' => $user->id]);

    return $user;
}

function makeCalendarFaculty(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $user->id]);

    return $user;
}

function makeCalendarStudent(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $user->id]);

    return $user;
}

// ── Admin ─────────────────────────────────────────────────────────────────────

it('redirects guests from admin defense calendar', function () {
    $this->get(route('admin.defense-calendar.index'))
        ->assertRedirect(route('login'));
});

it('denies non-admin access to admin defense calendar', function () {
    $faculty = makeCalendarFaculty();

    $this->actingAs($faculty)
        ->get(route('admin.defense-calendar.index'))
        ->assertForbidden();
});

it('allows admin to view defense calendar', function () {
    $admin = makeCalendarAdmin();

    $this->actingAs($admin)
        ->get(route('admin.defense-calendar.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/DefenseCalendar/Index')
            ->has('events')
            ->has('month')
            ->has('year')
            ->has('classes')
        );
});

it('admin calendar shows outline and final defense events in the requested month', function () {
    $admin = makeCalendarAdmin();
    $student = makeCalendarStudent();

    ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'outline_defense_schedule' => '2026-04-15 09:00:00',
        'final_defense_schedule' => null,
    ]);

    ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'final_defense_schedule' => '2026-04-20 14:00:00',
        'outline_defense_schedule' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.defense-calendar.index', ['month' => 4, 'year' => 2026]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('month', 4)
            ->where('year', 2026)
            ->has('events', 2)
            ->where('events.0.type', 'outline_defense')
            ->where('events.1.type', 'final_defense')
        );
});

it('admin calendar excludes events from other months', function () {
    $admin = makeCalendarAdmin();
    $student = makeCalendarStudent();

    ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'outline_defense_schedule' => '2026-05-10 09:00:00',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.defense-calendar.index', ['month' => 4, 'year' => 2026]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('events', []));
});

it('admin calendar shows schedules from panel_defenses when paper columns are empty', function () {
    $admin = makeCalendarAdmin();
    $student = makeCalendarStudent();

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'outline_defense_schedule' => null,
        'final_defense_schedule' => null,
    ]);

    PanelDefense::factory()
        ->forPaper($paper)
        ->ofType('title')
        ->createdBy($admin)
        ->create([
            'schedule' => Carbon::parse('2026-04-10 14:00:00', config('app.timezone')),
            'panel_members' => ['Dr. A'],
        ]);

    $this->actingAs($admin)
        ->get(route('admin.defense-calendar.index', ['month' => 4, 'year' => 2026]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 1)
            ->where('events.0.type', 'title_defense')
        );
});

it('admin calendar shows every panel row including duplicate same-day-time slots', function () {
    $admin = makeCalendarAdmin();
    $student = makeCalendarStudent();
    $schedule = Carbon::parse('2026-04-10 14:00:00', config('app.timezone'));

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'outline_defense_schedule' => null,
        'final_defense_schedule' => null,
    ]);

    PanelDefense::factory()
        ->forPaper($paper)
        ->ofType('title')
        ->createdBy($admin)
        ->create(['schedule' => $schedule, 'panel_members' => ['Dr. A']]);

    PanelDefense::factory()
        ->forPaper($paper)
        ->ofType('title')
        ->createdBy($admin)
        ->create(['schedule' => $schedule, 'panel_members' => ['Dr. B']]);

    $this->actingAs($admin)
        ->get(route('admin.defense-calendar.index', ['month' => 4, 'year' => 2026]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 2)
            ->where('events.0.type', 'title_defense')
            ->where('events.1.type', 'title_defense')
        );
});

// ── Faculty ───────────────────────────────────────────────────────────────────

it('redirects guests from faculty defense calendar', function () {
    $this->get(route('faculty.defense-calendar.index'))
        ->assertRedirect(route('login'));
});

it('denies student access to faculty defense calendar', function () {
    $student = makeCalendarStudent();

    $this->actingAs($student)
        ->get(route('faculty.defense-calendar.index'))
        ->assertForbidden();
});

it('allows faculty to view defense calendar', function () {
    $faculty = makeCalendarFaculty();

    $this->actingAs($faculty)
        ->get(route('faculty.defense-calendar.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('faculty/DefenseCalendar/Index')
            ->has('events')
            ->has('month')
            ->has('year')
            ->has('classes')
        );
});

it('faculty calendar only shows events from students in their classes', function () {
    $faculty = makeCalendarFaculty();
    $student = makeCalendarStudent();
    $otherStudent = makeCalendarStudent();

    $class = SchoolClass::factory()->create(['faculty_id' => $faculty->id]);

    DB::table('school_class_members')->insert([
        'school_class_id' => $class->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Enrolled student has a defense this month
    ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'outline_defense_schedule' => '2026-04-18 10:00:00',
        'final_defense_schedule' => null,
    ]);

    // Other student (not enrolled) also has a defense — should NOT appear
    ResearchPaper::factory()->create([
        'user_id' => $otherStudent->id,
        'outline_defense_schedule' => '2026-04-18 11:00:00',
        'final_defense_schedule' => null,
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.defense-calendar.index', ['month' => 4, 'year' => 2026]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 1)
            ->where('events.0.type', 'outline_defense')
        );
});

it('faculty calendar shows both outline and final events for enrolled students', function () {
    $faculty = makeCalendarFaculty();
    $student = makeCalendarStudent();

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
        'outline_defense_schedule' => '2026-04-05 09:00:00',
        'final_defense_schedule' => '2026-04-25 14:00:00',
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.defense-calendar.index', ['month' => 4, 'year' => 2026]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('events', 2));
});
