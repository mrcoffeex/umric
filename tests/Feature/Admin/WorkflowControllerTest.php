<?php

use App\Models\ResearchPaper;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\WorkflowVersion;
use App\Services\WorkflowCatalog;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function workflowAdminUser(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->admin()->create(['user_id' => $user->id]);

    return $user;
}

function workflowStaffUser(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->staff()->create(['user_id' => $user->id]);

    return $user;
}

function workflowStudentUser(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $user->id]);

    return $user;
}

function defaultWorkflowSteps(): array
{
    return collect(ResearchPaper::STEPS)
        ->map(fn (string $key) => [
            'key' => $key,
            'label' => ResearchPaper::STEP_LABELS[$key],
        ])
        ->values()
        ->all();
}

it('redirects guests from workflow steps index', function () {
    $this->get(route('admin.workflow-steps.index'))
        ->assertRedirect(route('login'));
});

it('denies students access to workflow steps', function () {
    $student = workflowStudentUser();

    $this->actingAs($student)
        ->get(route('admin.workflow-steps.index'))
        ->assertForbidden();
});

it('allows staff to view workflow steps', function () {
    $staff = workflowStaffUser();

    $this->actingAs($staff)
        ->get(route('admin.workflow-steps.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Workflow/Index')
            ->has('workflow.steps')
            ->where('workflow.version', 1)
        );
});

it('publishes a new workflow version without changing existing papers', function () {
    $admin = workflowAdminUser();
    $catalog = app(WorkflowCatalog::class);
    $original = $catalog->current();

    $existing = ResearchPaper::factory()->create();

    $steps = defaultWorkflowSteps();
    $steps = array_values(array_filter($steps, fn (array $step) => $step['key'] !== 'rating'));
    array_splice($steps, -1, 0, [[
        'key' => 'ethics_review',
        'label' => 'Ethics Review',
    ]]);

    $this->actingAs($admin)
        ->put(route('admin.workflow-steps.update'), [
            'steps' => $steps,
        ])
        ->assertRedirect(route('admin.workflow-steps.index'));

    $existing->refresh();
    expect($existing->workflow_version_id)->toBe($original->id)
        ->and($existing->stepKeys())->toContain('rating')
        ->and($existing->stepKeys())->not->toContain('ethics_review');

    $published = $catalog->current();
    expect($published->id)->not->toBe($original->id)
        ->and($published->is_current)->toBeTrue()
        ->and($published->stepKeys())->toContain('ethics_review')
        ->and($published->stepKeys())->not->toContain('rating');

    $newPaper = ResearchPaper::factory()->create();
    expect($newPaper->workflow_version_id)->toBe($published->id)
        ->and($newPaper->stepKeys())->toContain('ethics_review')
        ->and($newPaper->stepKeys())->not->toContain('rating')
        ->and($newPaper->labelForStep('ethics_review'))->toBe('Ethics Review');
});

it('requires the last step to be completed', function () {
    $admin = workflowAdminUser();
    app(WorkflowCatalog::class)->current();

    $this->actingAs($admin)
        ->from(route('admin.workflow-steps.index'))
        ->put(route('admin.workflow-steps.update'), [
            'steps' => [
                ['key' => 'title_proposal', 'label' => 'Title Evaluation'],
                ['key' => 'ric_review', 'label' => 'RIC/Admin Review'],
            ],
        ])
        ->assertRedirect(route('admin.workflow-steps.index'))
        ->assertSessionHasErrors('steps');

    expect(WorkflowVersion::query()->count())->toBe(1)
        ->and(WorkflowVersion::query()->where('is_current', true)->count())->toBe(1);
});

it('publishes step statuses and custom inputs for new papers only', function () {
    $admin = workflowAdminUser();
    $catalog = app(WorkflowCatalog::class);
    $original = $catalog->current();
    $existing = ResearchPaper::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.workflow-steps.update'), [
            'steps' => [
                ['key' => 'title_proposal', 'label' => 'Title Evaluation', 'config' => ['statuses' => [], 'inputs' => []]],
                [
                    'key' => 'ethics_review',
                    'label' => 'Ethics Review',
                    'config' => [
                        'statuses' => [
                            ['value' => 'pending', 'label' => 'Pending', 'color' => 'muted', 'completes' => false],
                            ['value' => 'cleared', 'label' => 'Cleared', 'color' => 'teal', 'completes' => true],
                        ],
                        'inputs' => [
                            ['key' => 'reviewer_name', 'label' => 'Reviewer', 'type' => 'text'],
                            ['key' => 'hearing_at', 'label' => 'Hearing', 'type' => 'datetime', 'show_on_calendar' => true],
                        ],
                    ],
                ],
                ['key' => 'completed', 'label' => 'Completed', 'config' => ['statuses' => [], 'inputs' => []]],
            ],
        ])
        ->assertRedirect(route('admin.workflow-steps.index'));

    $existing->refresh();
    expect($existing->workflow_version_id)->toBe($original->id)
        ->and($existing->stepKeys())->not->toContain('ethics_review')
        ->and($existing->statusCompletesStep('ethics_review', 'cleared'))->toBeFalse();

    $published = $catalog->current();
    $ethics = $published->steps->firstWhere('key', 'ethics_review');
    expect($ethics->config['statuses'][1]['value'])->toBe('cleared')
        ->and($ethics->config['statuses'][1]['completes'])->toBeTrue()
        ->and($ethics->config['inputs'][0]['key'])->toBe('reviewer_name')
        ->and($ethics->config['inputs'][0]['show_on_calendar'])->toBeFalse()
        ->and($ethics->config['inputs'][1]['key'])->toBe('hearing_at')
        ->and($ethics->config['inputs'][1]['show_on_calendar'])->toBeTrue();

    $newPaper = ResearchPaper::factory()->create();
    expect($newPaper->workflow_version_id)->toBe($published->id)
        ->and($newPaper->statusCompletesStep('ethics_review', 'cleared'))->toBeTrue()
        ->and($newPaper->statusCompletesStep('ethics_review', 'pending'))->toBeFalse();
});

it('advances a custom step using the configured finishing status and stores custom inputs', function () {
    $admin = workflowAdminUser();
    $catalog = app(WorkflowCatalog::class);

    $catalog->publish([
        ['key' => 'title_proposal', 'label' => 'Title Evaluation'],
        [
            'key' => 'ethics_review',
            'label' => 'Ethics Review',
            'config' => [
                'statuses' => [
                    ['value' => 'pending', 'label' => 'Pending', 'color' => 'muted', 'completes' => false],
                    ['value' => 'cleared', 'label' => 'Cleared', 'color' => 'teal', 'completes' => true],
                ],
                'inputs' => [
                    ['key' => 'reviewer_name', 'label' => 'Reviewer', 'type' => 'text'],
                ],
            ],
        ],
        ['key' => 'completed', 'label' => 'Completed'],
    ], $admin);

    $paper = ResearchPaper::factory()->create([
        'current_step' => 'ethics_review',
        'custom_step_statuses' => ['ethics_review' => 'pending'],
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.research.update-step', $paper), [
            'step' => 'ethics_review',
            'status' => 'cleared',
            'notes' => 'Looks good',
            'fields' => [
                'reviewer_name' => 'Dr. Reyes',
            ],
        ])
        ->assertRedirect();

    $paper->refresh();
    expect($paper->current_step)->toBe('completed')
        ->and($paper->customStepStatus('ethics_review'))->toBe('cleared')
        ->and($paper->step_input_values['ethics_review']['reviewer_name'])->toBe('Dr. Reyes');
});

it('rejects a step setup that has statuses but none that finish the step', function () {
    $admin = workflowAdminUser();
    app(WorkflowCatalog::class)->current();

    $this->actingAs($admin)
        ->from(route('admin.workflow-steps.index'))
        ->put(route('admin.workflow-steps.update'), [
            'steps' => [
                ['key' => 'title_proposal', 'label' => 'Title Evaluation'],
                [
                    'key' => 'ric_review',
                    'label' => 'RIC/Admin Review',
                    'config' => [
                        'statuses' => [
                            ['value' => 'pending', 'label' => 'Pending', 'color' => 'muted', 'completes' => false],
                            ['value' => 'approved', 'label' => 'Approved', 'color' => 'teal', 'completes' => false],
                        ],
                        'inputs' => [],
                    ],
                ],
                ['key' => 'completed', 'label' => 'Completed'],
            ],
        ])
        ->assertRedirect(route('admin.workflow-steps.index'))
        ->assertSessionHasErrors('steps.1.config.statuses');
});

it('advances a paper to the next configured step instead of the default sequence', function () {
    $admin = workflowAdminUser();
    $catalog = app(WorkflowCatalog::class);

    $steps = [
        ['key' => 'title_proposal', 'label' => 'Title Evaluation'],
        ['key' => 'ric_review', 'label' => 'RIC/Admin Review'],
        ['key' => 'ethics_review', 'label' => 'Ethics Review'],
        ['key' => 'completed', 'label' => 'Completed'],
    ];

    $catalog->publish($steps, $admin);

    $paper = ResearchPaper::factory()->create([
        'current_step' => 'ric_review',
        'step_ric_review' => 'pending',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.research.update-step', $paper), [
            'step' => 'ric_review',
            'status' => 'approved',
        ])
        ->assertRedirect();

    $paper->refresh();
    expect($paper->current_step)->toBe('ethics_review')
        ->and($paper->customStepStatus('ethics_review'))->toBe('pending');
});
