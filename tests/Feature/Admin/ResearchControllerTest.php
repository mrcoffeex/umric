<?php

use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function adminResearchUser(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->admin()->create(['user_id' => $user->id]);

    return $user;
}

function studentResearchUser(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $user->id]);

    return $user;
}

it('uses the student membership class for class column in admin research index', function () {
    $admin = adminResearchUser();
    $student = studentResearchUser();

    $memberClass = SchoolClass::factory()->create();
    $paperClass = SchoolClass::factory()->create();

    DB::table('school_class_members')->insert([
        'school_class_id' => $memberClass->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'school_class_id' => $paperClass->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.research.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Research/Index')
            ->where('papers.data.0.school_class.id', $memberClass->id)
            ->where('papers.data.0.school_class.name', $memberClass->name)
        );
});

it('filters admin research by membership class', function () {
    $admin = adminResearchUser();

    $classA = SchoolClass::factory()->create();
    $classB = SchoolClass::factory()->create();

    $studentA = studentResearchUser();
    $studentB = studentResearchUser();

    DB::table('school_class_members')->insert([
        [
            'school_class_id' => $classA->id,
            'student_id' => $studentA->id,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'school_class_id' => $classB->id,
            'student_id' => $studentB->id,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    ResearchPaper::factory()->create([
        'user_id' => $studentA->id,
        'school_class_id' => $classB->id,
        'title' => 'Paper in class A membership',
    ]);

    ResearchPaper::factory()->create([
        'user_id' => $studentB->id,
        'school_class_id' => $classA->id,
        'title' => 'Paper in class B membership',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.research.index', ['class' => $classA->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('papers.data', 1)
            ->where('papers.data.0.title', 'Paper in class A membership')
            ->where('papers.data.0.school_class.id', $classA->id)
        );
});
