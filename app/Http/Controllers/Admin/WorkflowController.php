<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateWorkflowRequest;
use App\Models\ResearchPaper;
use App\Models\WorkflowVersion;
use App\Services\WorkflowCatalog;
use App\Support\WorkflowStepConfig;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowController extends Controller
{
    public function __construct(
        private WorkflowCatalog $workflows,
    ) {}

    public function index(): Response
    {
        $current = $this->workflows->current();
        $current->loadCount('papers');

        $olderPaperCount = ResearchPaper::query()
            ->where(function ($query) use ($current): void {
                $query->whereNull('workflow_version_id')
                    ->orWhere('workflow_version_id', '!=', $current->id);
            })
            ->count();

        return Inertia::render('admin/Workflow/Index', [
            'workflow' => [
                'id' => (string) $current->id,
                'version' => $current->version,
                'paper_count' => (int) $current->papers_count,
                'older_paper_count' => $olderPaperCount,
                'steps' => $current->steps
                    ->map(fn ($step) => [
                        'key' => $step->key,
                        'label' => $step->label,
                        'config' => WorkflowStepConfig::normalize(
                            is_array($step->config) ? $step->config : null,
                            $step->key,
                        ),
                    ])
                    ->values()
                    ->all(),
            ],
            'templates' => collect($this->workflows->defaultSteps())
                ->map(fn (array $step) => [
                    'key' => $step['key'],
                    'label' => $step['label'],
                    'config' => $step['config'],
                ])
                ->values()
                ->all(),
            'custom_default_config' => WorkflowStepConfig::defaultsFor('custom'),
            'version_count' => WorkflowVersion::query()->count(),
        ]);
    }

    public function update(UpdateWorkflowRequest $request): RedirectResponse
    {
        $steps = collect($request->validated('steps'))
            ->values()
            ->map(fn (array $step, int $index) => [
                'key' => $step['key'],
                'label' => $step['label'],
                'sort_order' => $index,
                'config' => $step['config'] ?? null,
            ])
            ->all();

        $this->workflows->publish($steps, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Research steps published. New papers will use this sequence. Existing papers keep theirs.',
        ]);

        return redirect()->route('admin.workflow-steps.index');
    }
}
