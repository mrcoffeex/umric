<?php

use App\Models\Announcement;
use App\Models\PaperFile;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function studentActor(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $user->id]);

    return $user;
}

it('shows student research index with papers list', function () {
    $student = studentActor();

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'title_proposal',
        'status' => 'submitted',
    ]);

    $this->actingAs($student)
        ->get(route('student.research.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Research/Index')
            ->has('papers', 1)
            ->has('stepLabels')
            ->has('steps')
        );
});

it('shows student research show page for owned paper', function () {
    $student = studentActor();

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'title_proposal',
        'status' => 'submitted',
    ]);

    $this->actingAs($student)
        ->get(route('student.research.show', $paper))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Research/Show')
            ->where('paper.id', $paper->id)
            ->has('defenseDocumentUpload')
            ->where('defenseDocumentUpload.mayManage', true)
            ->where('defenseDocumentUpload.ricReturned', false)
            ->where('defenseDocumentUpload.outline', false)
            ->where('defenseDocumentUpload.final', false)
            ->where('defenseDocumentUpload.outlineUploaded', false)
            ->where('defenseDocumentUpload.finalUploaded', false)
            ->where('trackingPublicUrl', url('/track/'.$paper->tracking_id))
            ->has('stepLabels')
            ->has('steps')
        );
});

it('shows co-proponent papers in index', function () {
    $owner = studentActor();
    $coProponent = studentActor();

    $paper = ResearchPaper::factory()->create([
        'user_id' => $owner->id,
        'proponents' => [['id' => (string) $coProponent->id, 'name' => $coProponent->name]],
        'current_step' => 'title_proposal',
        'status' => 'submitted',
    ]);

    $this->actingAs($coProponent)
        ->get(route('student.research.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Research/Index')
            ->has('papers', 1)
        );
});

it('includes defense upload flags on show when the paper allows outline upload', function () {
    $student = studentActor();
    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'outline_defense',
        'step_outline_defense' => 'pending',
        'step_ric_review' => 'approved',
    ]);

    $this->actingAs($student)
        ->get(route('student.research.show', $paper))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('defenseDocumentUpload.outline', true)
            ->where('defenseDocumentUpload.final', false)
        );
});

it('denies access to show for non-owner non-proponent', function () {
    $student = studentActor();
    $other = studentActor();

    $paper = ResearchPaper::factory()->create([
        'user_id' => $other->id,
        'current_step' => 'title_proposal',
    ]);

    $this->actingAs($student)
        ->get(route('student.research.show', $paper))
        ->assertForbidden();
});

it('stores student title proposal and tracking log', function () {
    $student = studentActor();

    $class = SchoolClass::factory()->create([
        'name' => 'BSCS 4A',
        'section' => 'A',
    ]);

    DB::table('school_class_members')->insert([
        'school_class_id' => $class->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($student)
        ->post(route('student.research.store'), [
            'title' => 'AI-Assisted Literature Mapping',
            'abstract' => 'A study on automated mapping workflows.',
            'proponents' => [
                ['id' => (string) $student->id, 'name' => $student->name],
            ],
        ])
        ->assertRedirect();

    $paper = ResearchPaper::query()->where('user_id', $student->id)->first();

    expect($paper)->not->toBeNull();

    $this->assertDatabaseHas('research_papers', [
        'id' => $paper->id,
        'user_id' => $student->id,
        'school_class_id' => $class->id,
        'title' => 'AI-Assisted Literature Mapping',
        'current_step' => 'title_proposal',
        'status' => 'submitted',
    ]);

    $this->assertDatabaseHas('tracking_records', [
        'research_paper_id' => $paper->id,
        'step' => 'title_proposal',
        'action' => 'Title proposal submitted',
        'status' => 'submitted',
        'updated_by' => $student->id,
    ]);
});

it('updates a title proposal paper', function () {
    $student = studentActor();

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'title' => 'Old Title',
        'current_step' => 'title_proposal',
    ]);

    $this->actingAs($student)
        ->put(route('student.research.update', $paper), [
            'title' => 'New Title',
            'abstract' => 'Updated abstract',
        ])
        ->assertRedirect();

    expect($paper->fresh()->title)->toBe('New Title');
});

it('prevents updating a paper past title_proposal step', function () {
    $student = studentActor();

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'ric_review',
    ]);

    $this->actingAs($student)
        ->put(route('student.research.update', $paper), [
            'title' => 'Should Not Work',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('step');
});

it('deletes a title proposal paper', function () {
    $student = studentActor();

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'title_proposal',
    ]);

    $this->actingAs($student)
        ->delete(route('student.research.destroy', $paper))
        ->assertRedirect(route('student.research.index'));

    $this->assertSoftDeleted('research_papers', ['id' => $paper->id]);
});

it('prevents deleting a paper past title_proposal step', function () {
    $student = studentActor();

    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'ric_review',
    ]);

    $this->actingAs($student)
        ->delete(route('student.research.destroy', $paper))
        ->assertRedirect()
        ->assertSessionHasErrors('step');

    $this->assertDatabaseHas('research_papers', ['id' => $paper->id]);
});

it('shows student home with announcements, classes, and paper data', function () {
    $student = studentActor();

    $class = SchoolClass::factory()->create([
        'name' => 'BSIT 3B',
        'section' => 'B',
    ]);

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
    ]);

    Announcement::factory()->create([
        'target_roles' => ['student'],
        'is_active' => true,
        'is_pinned' => true,
    ]);

    $this->actingAs($student)
        ->get(route('student.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Home')
            ->has('announcements')
            ->has('classes', 1)
            ->where('paper.id', $paper->id)
            ->has('stepLabels')
            ->has('steps')
        );
});

it('shows co-proponent paper on student home', function () {
    $owner = studentActor();
    $coProponent = studentActor();

    $paper = ResearchPaper::factory()->create([
        'user_id' => $owner->id,
        'proponents' => [['id' => (string) $coProponent->id, 'name' => $coProponent->name]],
        'current_step' => 'ric_review',
    ]);

    $this->actingAs($coProponent)
        ->get(route('student.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Home')
            ->where('paper.id', $paper->id)
        );
});

it('stores outline defense document when the paper is at the outline defense step', function () {
    $student = studentActor();
    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'outline_defense',
        'step_outline_defense' => 'pending',
    ]);

    $file = UploadedFile::fake()->create('outline.pdf', 120, 'application/pdf');

    $this->actingAs($student)
        ->post(route('student.research.defense-documents.store', $paper), [
            'defense' => 'outline',
            'file' => $file,
        ])
        ->assertRedirect();

    $uploaded = $paper->fresh()->files()->where('file_category', PaperFile::CATEGORY_OUTLINE_DEFENSE)->first();
    expect($uploaded)->not->toBeNull();
    expect($uploaded->file_name)->toBe('outline.pdf');
});

it('rejects a second outline defense upload because the slot is consumed after the first', function () {
    $student = studentActor();
    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'outline_defense',
        'step_outline_defense' => 'pending',
    ]);

    $first = UploadedFile::fake()->create('first.pdf', 100, 'application/pdf');
    $second = UploadedFile::fake()->create('second.pdf', 100, 'application/pdf');

    $this->actingAs($student)
        ->post(route('student.research.defense-documents.store', $paper), [
            'defense' => 'outline',
            'file' => $first,
        ])
        ->assertRedirect();

    $this->actingAs($student)
        ->post(route('student.research.defense-documents.store', $paper), [
            'defense' => 'outline',
            'file' => $second,
        ])
        ->assertForbidden();

    $names = $paper->fresh()->files()
        ->where('file_category', PaperFile::CATEGORY_OUTLINE_DEFENSE)
        ->pluck('file_name');
    expect($names->all())->toBe(['first.pdf']);
});

it('rejects a second final defense upload while RIC review is returned', function () {
    $student = studentActor();
    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'title_proposal',
        'step_ric_review' => 'returned',
    ]);

    $first = UploadedFile::fake()->create('first-final.pdf', 100, 'application/pdf');
    $second = UploadedFile::fake()->create('second-final.pdf', 100, 'application/pdf');

    $this->actingAs($student)
        ->post(route('student.research.defense-documents.store', $paper), [
            'defense' => 'final',
            'file' => $first,
        ])
        ->assertRedirect();

    $this->actingAs($student)
        ->post(route('student.research.defense-documents.store', $paper), [
            'defense' => 'final',
            'file' => $second,
        ])
        ->assertForbidden();

    expect(
        $paper->fresh()->files()
            ->where('file_category', PaperFile::CATEGORY_FINAL_DEFENSE)
            ->count()
    )->toBe(1);
});

it('stores final defense document when the paper is at the final defense step', function () {
    $student = studentActor();
    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'final_defense',
        'step_final_defense' => 'pending',
    ]);

    $file = UploadedFile::fake()->create('final.pdf', 200, 'application/pdf');

    $this->actingAs($student)
        ->post(route('student.research.defense-documents.store', $paper), [
            'defense' => 'final',
            'file' => $file,
        ])
        ->assertRedirect();

    $uploaded = $paper->fresh()->files()->where('file_category', PaperFile::CATEGORY_FINAL_DEFENSE)->first();
    expect($uploaded)->not->toBeNull();
});

it('forbids defense document upload when the workflow step does not match', function () {
    $student = studentActor();
    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'data_gathering',
    ]);

    $file = UploadedFile::fake()->create('outline.pdf', 100, 'application/pdf');

    $this->actingAs($student)
        ->post(route('student.research.defense-documents.store', $paper), [
            'defense' => 'outline',
            'file' => $file,
        ])
        ->assertForbidden();
});

it('allows outline and final defense documents when RIC is returned even if the current step is not a defense step', function () {
    $student = studentActor();
    $paper = ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'title_proposal',
        'step_ric_review' => 'returned',
    ]);

    $outline = UploadedFile::fake()->create('outline.pdf', 100, 'application/pdf');
    $this->actingAs($student)
        ->post(route('student.research.defense-documents.store', $paper), [
            'defense' => 'outline',
            'file' => $outline,
        ])
        ->assertRedirect();

    $final = UploadedFile::fake()->create('final.pdf', 100, 'application/pdf');
    $this->actingAs($student)
        ->post(route('student.research.defense-documents.store', $paper), [
            'defense' => 'final',
            'file' => $final,
        ])
        ->assertRedirect();

    $files = $paper->fresh()->files;
    expect($files->where('file_category', PaperFile::CATEGORY_OUTLINE_DEFENSE)->count())->toBe(1);
    expect($files->where('file_category', PaperFile::CATEGORY_FINAL_DEFENSE)->count())->toBe(1);
});
