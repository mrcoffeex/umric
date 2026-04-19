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

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('counts research papers from enrolled students in class show', function () {
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

    // Paper by enrolled student — no school_class_id set
    ResearchPaper::factory()->create(['user_id' => $student->id, 'school_class_id' => null]);

    $this->actingAs($faculty)
        ->get(route('faculty.classes.show', $class))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('faculty/Classes/Show')
            ->where('researchPapersCount', 1)
        );
});

it('does not count papers from students not in the class', function () {
    $faculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $faculty->id]);

    $otherStudent = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $otherStudent->id]);

    $class = SchoolClass::factory()->create(['faculty_id' => $faculty->id]);

    // Paper by a student who is NOT enrolled
    ResearchPaper::factory()->create(['user_id' => $otherStudent->id]);

    $this->actingAs($faculty)
        ->get(route('faculty.classes.show', $class))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('researchPapersCount', 0)
        );
});
