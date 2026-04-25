<?php

use App\Models\EvaluationFormat;
use App\Models\PanelDefense;
use App\Models\PanelDefenseEvaluation;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\PanelDefenseSchedule;
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

it('includes fixed panel schedule time options on admin research show', function () {
    $admin = adminResearchUser();
    $paper = ResearchPaper::factory()->create(['user_id' => studentResearchUser()->id]);

    $this->actingAs($admin)
        ->get(route('admin.research.show', $paper))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Research/Show')
            ->has('panelScheduleTimeOptions', 25)
            ->where('panelScheduleTimeOptions.0.value', '08:00')
            ->where('panelScheduleTimeOptions.24.value', '20:00')
        );
});

it('stores a panel defense with required date and fixed time slot', function () {
    $admin = adminResearchUser();
    $student = studentResearchUser();
    $faculty = User::factory()
        ->has(UserProfile::factory()->faculty(), 'profile')
        ->create(['name' => 'Dr. Panelist One']);

    $paper = ResearchPaper::factory()->create(['user_id' => $student->id]);

    $date = '2031-05-20';

    $this->actingAs($admin)
        ->post(route('admin.research.panel-defenses.store', $paper), [
            'defense_type' => 'outline',
            'evaluation_format_id' => (string) EvaluationFormat::query()->value('id'),
            'panel_members' => [$faculty->name],
            'schedule_date' => $date,
            'schedule_time' => '10:00',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $pd = PanelDefense::query()->where('research_paper_id', $paper->id)->first();
    expect($pd)->not->toBeNull();
    expect($pd->schedule->timezone(config('app.timezone'))->format('Y-m-d H:i'))->toBe('2031-05-20 10:00');

    $paper->refresh();
    expect($paper->outline_defense_schedule)->not->toBeNull();
});

it('rejects duplicate panel schedule for another paper', function () {
    $admin = adminResearchUser();
    $student = studentResearchUser();
    $student2 = studentResearchUser();
    $faculty = User::factory()
        ->has(UserProfile::factory()->faculty(), 'profile')
        ->create(['name' => 'Dr. Panelist Two']);

    $paper1 = ResearchPaper::factory()->create(['user_id' => $student->id]);
    $paper2 = ResearchPaper::factory()->create(['user_id' => $student2->id]);

    $date = '2031-06-01';

    PanelDefense::factory()->create([
        'research_paper_id' => $paper1->id,
        'defense_type' => 'outline',
        'panel_members' => ['Existing Member'],
        'schedule' => PanelDefenseSchedule::combineToCarbon($date, '09:00'),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.research.panel-defenses.store', $paper2), [
            'defense_type' => 'final',
            'evaluation_format_id' => (string) EvaluationFormat::query()->value('id'),
            'panel_members' => [$faculty->name],
            'schedule_date' => $date,
            'schedule_time' => '09:00',
        ])
        ->assertSessionHasErrors('schedule_conflict');
});

it('allows panel defense at duplicate slot when conflict is acknowledged', function () {
    $admin = adminResearchUser();
    $student = studentResearchUser();
    $student2 = studentResearchUser();
    $faculty = User::factory()
        ->has(UserProfile::factory()->faculty(), 'profile')
        ->create(['name' => 'Dr. Panelist Ack']);

    $paper1 = ResearchPaper::factory()->create(['user_id' => $student->id]);
    $paper2 = ResearchPaper::factory()->create(['user_id' => $student2->id]);

    $date = '2031-06-15';

    PanelDefense::factory()->create([
        'research_paper_id' => $paper1->id,
        'defense_type' => 'outline',
        'panel_members' => ['Other Member'],
        'schedule' => PanelDefenseSchedule::combineToCarbon($date, '10:00'),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.research.panel-defenses.store', $paper2), [
            'defense_type' => 'final',
            'evaluation_format_id' => (string) EvaluationFormat::query()->value('id'),
            'panel_members' => [$faculty->name],
            'schedule_date' => $date,
            'schedule_time' => '10:00',
            'acknowledge_schedule_conflict' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(PanelDefense::count())->toBe(2);
});

it('rejects panel defense when schedule time is not an allowed slot', function () {
    $admin = adminResearchUser();
    $student = studentResearchUser();
    $faculty = User::factory()
        ->has(UserProfile::factory()->faculty(), 'profile')
        ->create(['name' => 'Dr. Panelist Three']);

    $paper = ResearchPaper::factory()->create(['user_id' => $student->id]);

    $this->actingAs($admin)
        ->post(route('admin.research.panel-defenses.store', $paper), [
            'defense_type' => 'title',
            'evaluation_format_id' => (string) EvaluationFormat::query()->value('id'),
            'panel_members' => [$faculty->name],
            'schedule_date' => '2031-07-10',
            'schedule_time' => '09:15',
        ])
        ->assertSessionHasErrors('schedule_time');
});

it('requires schedule date and time for panel defense', function () {
    $admin = adminResearchUser();
    $student = studentResearchUser();
    $faculty = User::factory()
        ->has(UserProfile::factory()->faculty(), 'profile')
        ->create(['name' => 'Dr. Panelist Four']);

    $paper = ResearchPaper::factory()->create(['user_id' => $student->id]);

    $this->actingAs($admin)
        ->post(route('admin.research.panel-defenses.store', $paper), [
            'defense_type' => 'title',
            'panel_members' => [$faculty->name],
        ])
        ->assertSessionHasErrors(['schedule_date', 'schedule_time']);
});

it('updates a panel defense when no evaluation records exist', function () {
    $admin = adminResearchUser();
    $student = studentResearchUser();
    $faculty = User::factory()
        ->has(UserProfile::factory()->faculty(), 'profile')
        ->create(['name' => 'Dr. Edit Target']);

    $paper = ResearchPaper::factory()->create(['user_id' => $student->id]);
    $formatId = (string) EvaluationFormat::query()->value('id');

    $pd = PanelDefense::factory()->create([
        'research_paper_id' => $paper->id,
        'defense_type' => 'title',
        'evaluation_format_id' => $formatId,
        'panel_members' => ['Dr. Edit Target'],
        'schedule' => PanelDefenseSchedule::combineToCarbon('2032-03-01', '09:00'),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.research.panel-defenses.update', [$paper, $pd]), [
            'defense_type' => 'title',
            'evaluation_format_id' => $formatId,
            'panel_members' => ['Dr. Edit Target'],
            'schedule_date' => '2032-03-10',
            'schedule_time' => '16:00',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $pd->refresh();
    expect($pd->schedule->timezone(config('app.timezone'))->format('Y-m-d H:i'))->toBe('2032-03-10 16:00');
});

it('does not update a panel defense when evaluation records exist', function () {
    $admin = adminResearchUser();
    $student = studentResearchUser();
    $faculty = User::factory()
        ->has(UserProfile::factory()->faculty(), 'profile')
        ->create(['name' => 'Dr. Locked']);

    $paper = ResearchPaper::factory()->create(['user_id' => $student->id]);
    $formatId = (string) EvaluationFormat::query()->value('id');

    $pd = PanelDefense::factory()->create([
        'research_paper_id' => $paper->id,
        'defense_type' => 'title',
        'evaluation_format_id' => $formatId,
        'panel_members' => ['Dr. Locked'],
        'schedule' => PanelDefenseSchedule::combineToCarbon('2032-04-01', '10:00'),
        'created_by' => $admin->id,
    ]);

    PanelDefenseEvaluation::factory()->create([
        'panel_defense_id' => $pd->id,
        'evaluator_id' => $faculty->id,
    ]);

    $expected = $pd->schedule?->copy();

    $this->actingAs($admin)
        ->patch(route('admin.research.panel-defenses.update', [$paper, $pd]), [
            'defense_type' => 'title',
            'evaluation_format_id' => $formatId,
            'panel_members' => ['Dr. Locked'],
            'schedule_date' => '2032-12-12',
            'schedule_time' => '12:00',
        ])
        ->assertRedirect();

    $pd->refresh();
    expect($pd->schedule?->equalTo($expected))->toBeTrue();
});

it('does not remove a panel defense when evaluation records exist', function () {
    $admin = adminResearchUser();
    $student = studentResearchUser();
    $faculty = User::factory()
        ->has(UserProfile::factory()->faculty(), 'profile')
        ->create(['name' => 'Dr. Has Eval']);

    $paper = ResearchPaper::factory()->create(['user_id' => $student->id]);
    $formatId = (string) EvaluationFormat::query()->value('id');

    $pd = PanelDefense::factory()->create([
        'research_paper_id' => $paper->id,
        'defense_type' => 'title',
        'evaluation_format_id' => $formatId,
        'panel_members' => ['Dr. Has Eval'],
        'schedule' => PanelDefenseSchedule::combineToCarbon('2032-05-01', '11:00'),
        'created_by' => $admin->id,
    ]);

    PanelDefenseEvaluation::factory()->create([
        'panel_defense_id' => $pd->id,
        'evaluator_id' => $faculty->id,
    ]);

    $id = $pd->id;

    $this->actingAs($admin)
        ->delete(route('admin.research.panel-defenses.destroy', [$paper, $pd]))
        ->assertRedirect();

    expect(PanelDefense::query()->whereKey($id)->exists())->toBeTrue();
});
