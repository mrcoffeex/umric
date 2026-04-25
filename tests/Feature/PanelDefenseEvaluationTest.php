<?php

use App\Models\EvaluationCriterion;
use App\Models\PanelDefense;
use App\Models\PanelDefenseEvaluation;
use App\Models\ResearchPaper;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function makePanelEvalFaculty(?string $name = null): User
{
    $user = User::factory()->create(['name' => $name ?? 'Dr. '.Str::ulid()]);
    UserProfile::factory()->faculty()->create(['user_id' => $user->id]);

    return $user;
}

function makePanelEvalStudent(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $user->id]);

    return $user;
}

function scheduledDefenseOnPanelFor(User $panelist): PanelDefense
{
    $student = makePanelEvalStudent();
    $paper = ResearchPaper::factory()->create(['user_id' => $student->id]);

    return PanelDefense::factory()
        ->forPaper($paper)
        ->ofType('final')
        ->withMembers([$panelist->name, 'Dr. Other Chair'])
        ->create([
            'schedule' => now()->addWeek(),
        ]);
}

/**
 * @return array<string, int>
 */
function evalScoresExample(int $each = 3, ?PanelDefense $defense = null): array
{
    $query = EvaluationCriterion::query()->orderBy('sort_order');
    if ($defense !== null) {
        $query->where('evaluation_format_id', $defense->evaluation_format_id);
    }
    $out = [];
    foreach ($query->get() as $c) {
        $out[(string) $c->id] = $each;
    }

    return $out;
}

beforeEach(function () {
    $this->withoutVite();
});

it('redirects guests to login for evaluation pages', function () {
    $this->get(route('admin.evaluation.index'))->assertRedirect(route('login'));
    $this->get(route('faculty.evaluation.index'))->assertRedirect(route('login'));
});

it('denies students from admin and faculty evaluation pages', function () {
    $student = makePanelEvalStudent();

    $this->actingAs($student)
        ->get(route('admin.evaluation.index'))
        ->assertForbidden();

    $this->actingAs($student)
        ->get(route('faculty.evaluation.index'))
        ->assertForbidden();
});

it('lets faculty on the panel list only their panel defenses and submit once', function () {
    $faculty = makePanelEvalFaculty();
    $defense = scheduledDefenseOnPanelFor($faculty);
    scheduledDefenseOnPanelFor(makePanelEvalFaculty());

    $this->actingAs($faculty)
        ->get(route('faculty.evaluation.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('defenses.data', 1)
            ->where('defenses.data.0.id', (string) $defense->id)
            ->where('defenses.data.0.can_evaluate', true)
        );

    $scores = evalScoresExample(3, $defense);
    // Weighted scoring: sum of (score/100 * weight%) with four 25% criteria and 3 each → 3.
    $expectedTotal = 3;

    $this->actingAs($faculty)
        ->from(route('faculty.evaluation.index'))
        ->post(route('faculty.evaluation.store', $defense), [
            'scores' => $scores,
            'comments' => 'Adequate work; see chapter 3 for revisions.',
        ])
        ->assertRedirect(route('faculty.evaluation.index'));

    $eval = PanelDefenseEvaluation::query()
        ->where('panel_defense_id', (string) $defense->id)
        ->where('evaluator_id', (string) $faculty->id)
        ->first();
    expect($eval)->not->toBeNull();
    expect((int) $eval->final_score)->toBe($expectedTotal);
    expect($eval->comments)->toBe('Adequate work; see chapter 3 for revisions.');

    $this->actingAs($faculty)
        ->get(route('faculty.evaluation.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('defenses.data.0.my_evaluation.final_score', $expectedTotal)
            ->where('defenses.data.0.can_evaluate', false)
        );

    $this->actingAs($faculty)
        ->from(route('faculty.evaluation.index'))
        ->post(route('faculty.evaluation.store', $defense), [
            'scores' => evalScoresExample(1, $defense),
            'comments' => 'Re-eval attempt.',
        ])
        ->assertSessionHasErrors('scores');
});

it('forbids evaluation when the user is not on the panel', function () {
    $faculty = makePanelEvalFaculty();
    $defense = scheduledDefenseOnPanelFor(makePanelEvalFaculty());

    $this->actingAs($faculty)
        ->post(route('faculty.evaluation.store', $defense), [
            'scores' => evalScoresExample(2, $defense),
            'comments' => 'Not on panel; should not get here.',
        ])
        ->assertForbidden();
});

it('filters admin evaluation list by schedule date', function () {
    $admin = User::factory()->create(['name' => 'Dr. Admin Filter']);
    UserProfile::factory()->admin()->create(['user_id' => $admin->id]);

    $dayA = '2030-06-10';
    $dayB = '2030-06-11';

    $student = makePanelEvalStudent();
    $paper1 = ResearchPaper::factory()->create(['user_id' => $student->id]);
    $d1 = PanelDefense::factory()
        ->forPaper($paper1)
        ->withMembers([$admin->name])
        ->create([
            'schedule' => $dayA.' 14:00:00',
        ]);

    $paper2 = ResearchPaper::factory()->create(['user_id' => $student->id]);
    PanelDefense::factory()
        ->forPaper($paper2)
        ->withMembers([$admin->name])
        ->create([
            'schedule' => $dayB.' 10:00:00',
        ]);

    $this->actingAs($admin)
        ->get(route('admin.evaluation.index', ['schedule_date' => $dayA]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('defenses.data', 1)
            ->where('defenses.data.0.id', (string) $d1->id)
            ->where('filters.schedule_date', $dayA)
        );
});

it('lets admin list all scheduled defenses and submit when on the panel', function () {
    $admin = User::factory()->create(['name' => 'Dr. Admin Panelist']);
    UserProfile::factory()->admin()->create(['user_id' => $admin->id]);

    $defense = scheduledDefenseOnPanelFor($admin);

    $this->actingAs($admin)
        ->get(route('admin.evaluation.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('defenses.data', 1)
            ->where('defenses.data.0.can_evaluate', true)
        );

    $scores = evalScoresExample(0, $defense);
    $firstId = (string) EvaluationCriterion::query()
        ->where('evaluation_format_id', $defense->evaluation_format_id)
        ->orderBy('sort_order')
        ->value('id');
    $scores[$firstId] = 25;

    $this->actingAs($admin)
        ->post(route('admin.evaluation.store', $defense), [
            'scores' => $scores,
            'comments' => 'Strong manuscript and defense.',
        ])
        ->assertRedirect(route('admin.evaluation.index'));

    $final = (int) PanelDefenseEvaluation::query()
        ->where('panel_defense_id', (string) $defense->id)
        ->value('final_score');

    $this->actingAs($admin)
        ->get(route('admin.evaluation.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('defenses.data.0.average_score', $final)
        );
});

it('requires comments when submitting an evaluation', function () {
    $faculty = makePanelEvalFaculty();
    $defense = scheduledDefenseOnPanelFor($faculty);

    $this->actingAs($faculty)
        ->post(route('faculty.evaluation.store', $defense), [
            'scores' => evalScoresExample(3, $defense),
        ])
        ->assertSessionHasErrors('comments');
});

it('rejects out of range criteria scores', function () {
    $faculty = makePanelEvalFaculty();
    $defense = scheduledDefenseOnPanelFor($faculty);

    $scores = evalScoresExample(0, $defense);
    $firstId = (string) EvaluationCriterion::query()
        ->where('evaluation_format_id', $defense->evaluation_format_id)
        ->orderBy('sort_order')
        ->value('id');
    $scores[$firstId] = 101;

    $this->actingAs($faculty)
        ->post(route('faculty.evaluation.store', $defense), [
            'scores' => $scores,
            'comments' => 'Out of range test.',
        ])
        ->assertSessionHasErrors('scores.'.$firstId);
});

it('allows admin to update any panel evaluation', function () {
    $admin = User::factory()->create();
    UserProfile::factory()->admin()->create(['user_id' => $admin->id]);
    $panelist = makePanelEvalFaculty();
    $defense = scheduledDefenseOnPanelFor($panelist);
    $eval = PanelDefenseEvaluation::factory()->create([
        'panel_defense_id' => $defense->id,
        'evaluator_id' => (string) $panelist->id,
    ]);

    $ids = EvaluationCriterion::query()
        ->where('evaluation_format_id', $defense->evaluation_format_id)
        ->orderBy('sort_order')
        ->pluck('id')
        ->all();
    $scores = [];
    foreach ($ids as $id) {
        $scores[(string) $id] = 0;
    }
    $scores[(string) $ids[0]] = 25;

    $this->actingAs($admin)
        ->patch(route('admin.evaluation.update', $eval), [
            'scores' => $scores,
            'comments' => 'Admin correction to submitted scores.',
        ])
        ->assertRedirect(route('admin.evaluation.index'));

    $eval->refresh();
    // 25% weight, score 25 → 25*25/100 = 6.25 → 6
    expect($eval->final_score)->toBe(6);
});

it('lets the faculty evaluator download a PDF when the format enables it', function () {
    $faculty = makePanelEvalFaculty();
    $defense = scheduledDefenseOnPanelFor($faculty);
    $defense->evaluationFormat?->update([
        'pdf_settings' => ['enabled' => true],
    ]);
    $scores = evalScoresExample(80, $defense);
    $this->actingAs($faculty)
        ->post(route('faculty.evaluation.store', $defense), [
            'scores' => $scores,
            'comments' => 'PDF test comment.',
        ]);
    $eval = PanelDefenseEvaluation::query()
        ->where('panel_defense_id', (string) $defense->id)
        ->where('evaluator_id', (string) $faculty->id)
        ->first();
    expect($eval)->not->toBeNull();

    $this->actingAs($faculty)
        ->get(route('faculty.evaluation.pdf', $eval))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('returns 404 when PDF export is disabled for the format', function () {
    $faculty = makePanelEvalFaculty();
    $defense = scheduledDefenseOnPanelFor($faculty);
    $defense->evaluationFormat?->update([
        'pdf_settings' => ['enabled' => false],
    ]);
    $scores = evalScoresExample(80, $defense);
    $this->actingAs($faculty)
        ->post(route('faculty.evaluation.store', $defense), [
            'scores' => $scores,
            'comments' => 'No PDF.',
        ]);
    $eval = PanelDefenseEvaluation::query()
        ->where('panel_defense_id', (string) $defense->id)
        ->where('evaluator_id', (string) $faculty->id)
        ->first();

    $this->actingAs($faculty)
        ->get(route('faculty.evaluation.pdf', $eval))
        ->assertNotFound();
});
