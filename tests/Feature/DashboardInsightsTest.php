<?php

use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('admin dashboard includes ric pending insight when a paper awaits review', function () {
    $this->withoutVite();

    $admin = User::factory()->create();
    UserProfile::factory()->admin()->create(['user_id' => $admin->id]);

    ResearchPaper::factory()->create([
        'current_step' => 'ric_review',
        'step_ric_review' => 'pending',
        'updated_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('insights.actions')
            ->has('insights.health')
            ->where('insights.actions.0.id', 'ric-pending')
            ->where('insights.actions.0.count', 1)
            ->where('insights.health', fn ($health) => collect($health)->contains(
                fn ($metric) => ($metric['id'] ?? null) === 'pending-ric'
                    && (int) ($metric['value'] ?? 0) === 1
            ))
        );
});

test('faculty dashboard includes advisee-scoped insight when they advise a paper', function () {
    $this->withoutVite();

    $faculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $faculty->id]);

    $student = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $student->id]);

    ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'adviser_id' => $faculty->id,
        'current_step' => 'data_gathering',
        'updated_at' => now(),
    ]);

    $this->actingAs($faculty)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('insights.actions')
            ->where('insights.actions', fn ($actions) => collect($actions)->contains(
                fn ($action) => ($action['id'] ?? null) === 'advisees-active'
                    && (int) ($action['count'] ?? 0) === 1
            ))
            ->where('insights.health', fn ($health) => collect($health)->contains(
                fn ($metric) => ($metric['id'] ?? null) === 'with-papers'
                    && (int) ($metric['value'] ?? 0) === 1
            ))
        );
});

test('student home includes insights without duplicating research progress metrics', function () {
    $this->withoutVite();

    $student = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $student->id]);

    $faculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $faculty->id]);

    $class = SchoolClass::factory()->create(['faculty_id' => $faculty->id]);

    DB::table('school_class_members')->insert([
        'school_class_id' => $class->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'school_class_id' => $class->id,
        'current_step' => 'ric_review',
        'step_ric_review' => 'pending',
        'updated_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('student.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Home')
            ->where('hasClass', true)
            ->where('paper.id', $paper->id)
            ->has('insights.actions')
            ->where('insights.health', [])
            ->where('insights.actions', fn ($actions) => collect($actions)->doesntContain(
                fn ($action) => in_array($action['id'] ?? null, ['waiting-stage', 'workflow-complete'], true)
            ))
        );
});
