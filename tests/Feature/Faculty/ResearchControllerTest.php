<?php

use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function facultyUser(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $user->id]);

    return $user;
}

function studentUser(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $user->id]);

    return $user;
}

it('allows faculty class owner to view class research index', function () {
    $faculty = facultyUser();
    $student = studentUser();

    $class = SchoolClass::factory()->create([
        'faculty_id' => $faculty->id,
        'name' => 'BSIT 4A',
        'section' => 'A',
    ]);

    ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'school_class_id' => $class->id,
        'current_step' => 'ric_review',
        'step_ric_review' => 'pending',
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.classes.research.index', $class))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('faculty/Research/Index')
            ->has('papers', 1)
            ->has('schoolClass')
            ->has('stepCounts')
            ->has('stepLabels')
        );
});

it('forbids faculty from updating paper of class they do not own', function () {
    $owner = facultyUser();
    $otherFaculty = facultyUser();
    $student = studentUser();

    $class = SchoolClass::factory()->create([
        'faculty_id' => $owner->id,
        'name' => 'BSCS 3B',
        'section' => 'B',
    ]);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'school_class_id' => $class->id,
        'current_step' => 'outline_defense',
        'step_outline_defense' => 'pending',
    ]);

    $this->actingAs($otherFaculty)
        ->patch(route('faculty.classes.research.update-step', [$class, $paper]), [
            'step' => 'outline_defense',
            'status' => 'passed',
        ])
        ->assertForbidden();
});

it('updates rating step and logs tracking record for faculty owner', function () {
    $faculty = facultyUser();
    $student = studentUser();

    $class = SchoolClass::factory()->create([
        'faculty_id' => $faculty->id,
        'name' => 'BSIT 2C',
        'section' => 'C',
    ]);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'school_class_id' => $class->id,
        'current_step' => 'rating',
        'step_rating' => 'pending',
    ]);

    $this->actingAs($faculty)
        ->patch(route('faculty.classes.research.update-step', [$class, $paper]), [
            'step' => 'rating',
            'status' => 'rated',
            'grade' => 95.5,
            'notes' => 'Panel rating recorded.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('research_papers', [
        'id' => $paper->id,
        'step_rating' => 'rated',
        'current_step' => 'final_manuscript',
        'step_final_manuscript' => 'pending',
    ]);

    $this->assertDatabaseHas('tracking_records', [
        'research_paper_id' => $paper->id,
        'step' => 'rating',
        'status' => 'under_review',
        'updated_by' => $faculty->id,
    ]);
});

it('approves ric review and advances to plagiarism check', function () {
    $faculty = facultyUser();
    $student = studentUser();

    $class = SchoolClass::factory()->create([
        'faculty_id' => $faculty->id,
        'name' => 'BSIT 1D',
        'section' => 'D',
    ]);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'school_class_id' => $class->id,
        'current_step' => 'ric_review',
        'step_ric_review' => 'pending',
    ]);

    $this->actingAs($faculty)
        ->post(route('faculty.classes.research.approve', [$class, $paper]), [
            'notes' => 'RIC review passed.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('research_papers', [
        'id' => $paper->id,
        'step_ric_review' => 'approved',
        'current_step' => 'plagiarism_check',
        'step_plagiarism' => 'pending',
    ]);

    $this->assertDatabaseHas('tracking_records', [
        'research_paper_id' => $paper->id,
        'step' => 'ric_review',
        'status' => 'approved',
        'updated_by' => $faculty->id,
    ]);
});
