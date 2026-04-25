<?php

use App\Models\Comment;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;
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

function enrollStudent(User $student, SchoolClass $class): void
{
    DB::table('school_class_members')->insert([
        'school_class_id' => $class->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('allows faculty class owner to view class research index', function () {
    $faculty = facultyUser();
    $student = studentUser();

    $class = SchoolClass::factory()->create([
        'faculty_id' => $faculty->id,
        'name' => 'BSIT 4A',
        'section' => 'A',
    ]);

    enrollStudent($student, $class);

    ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'ric_review',
        'step_ric_review' => 'pending',
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.classes.research.index', $class))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('faculty/Research/Index')
            ->has('papers.data', 1)
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

    enrollStudent($student, $class);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
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

it('allows assigned adviser to view paper outside owned class', function () {
    $owner = facultyUser();
    $assignedAdviser = facultyUser();
    $student = studentUser();

    $class = SchoolClass::factory()->create([
        'faculty_id' => $owner->id,
        'name' => 'BSIT 3A',
        'section' => 'A',
    ]);

    enrollStudent($student, $class);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'school_class_id' => $class->id,
        'adviser_id' => $assignedAdviser->id,
    ]);

    $this->actingAs($assignedAdviser)
        ->get(route('faculty.classes.research.show', [$class, $paper]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('faculty/Research/Show')
            ->where('paper.id', $paper->id)
        );
});

it('updates rating step and logs tracking record for faculty owner', function () {
    $faculty = facultyUser();
    $student = studentUser();

    $class = SchoolClass::factory()->create([
        'faculty_id' => $faculty->id,
        'name' => 'BSIT 2C',
        'section' => 'C',
    ]);

    enrollStudent($student, $class);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
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

it('approves ric review and advances to outline defense', function () {
    $faculty = facultyUser();
    $student = studentUser();

    $class = SchoolClass::factory()->create([
        'faculty_id' => $faculty->id,
        'name' => 'BSIT 1D',
        'section' => 'D',
    ]);

    enrollStudent($student, $class);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
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
        'current_step' => 'outline_defense',
        'step_outline_defense' => 'pending',
    ]);

    $this->assertDatabaseHas('tracking_records', [
        'research_paper_id' => $paper->id,
        'step' => 'ric_review',
        'status' => 'approved',
        'updated_by' => $faculty->id,
    ]);
});

it('allows faculty to store a comment on a paper in their class', function () {
    $faculty = facultyUser();
    $student = studentUser();

    $class = SchoolClass::factory()->create(['faculty_id' => $faculty->id]);
    enrollStudent($student, $class);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
    ]);

    $this->actingAs($faculty)
        ->post(route('faculty.classes.research.store-comment', [$class, $paper]), [
            'body' => 'Great progress on the methodology section.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('comments', [
        'research_paper_id' => $paper->id,
        'user_id' => $faculty->id,
        'body' => 'Great progress on the methodology section.',
    ]);
});

it('forbids faculty from commenting on a paper outside their class', function () {
    $faculty = facultyUser();
    $otherFaculty = facultyUser();
    $student = studentUser();

    $class = SchoolClass::factory()->create(['faculty_id' => $otherFaculty->id]);
    enrollStudent($student, $class);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
    ]);

    $this->actingAs($faculty)
        ->post(route('faculty.classes.research.store-comment', [$class, $paper]), [
            'body' => 'Should not be allowed.',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('comments', [
        'research_paper_id' => $paper->id,
    ]);
});

it('validates comment body is required', function () {
    $faculty = facultyUser();
    $student = studentUser();

    $class = SchoolClass::factory()->create(['faculty_id' => $faculty->id]);
    enrollStudent($student, $class);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
    ]);

    $this->actingAs($faculty)
        ->post(route('faculty.classes.research.store-comment', [$class, $paper]), [
            'body' => '',
        ])
        ->assertSessionHasErrors('body');
});

it('returns comments when viewing a paper', function () {
    $faculty = facultyUser();
    $student = studentUser();

    $class = SchoolClass::factory()->create(['faculty_id' => $faculty->id]);
    enrollStudent($student, $class);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
    ]);

    Comment::factory()->count(3)->create([
        'research_paper_id' => $paper->id,
        'user_id' => $faculty->id,
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.classes.research.show', [$class, $paper]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('comments', 3)
        );
});
