<?php

namespace App\Services;

use App\Models\ResearchPaper;
use App\Models\User;
use App\Models\WorkflowStep;
use App\Models\WorkflowVersion;
use App\Support\WorkflowStepConfig;
use Illuminate\Support\Facades\DB;

class WorkflowCatalog
{
    /**
     * @return list<array{key: string, label: string, sort_order: int, config: array{statuses: list<array{value: string, label: string, color: string, completes: bool}>, inputs: list<array{key: string, label: string, type: string, show_on_calendar: bool}>}}>
     */
    public function defaultSteps(): array
    {
        $steps = [];

        foreach (ResearchPaper::STEPS as $index => $key) {
            $steps[] = [
                'key' => $key,
                'label' => ResearchPaper::STEP_LABELS[$key] ?? $key,
                'sort_order' => $index,
                'config' => WorkflowStepConfig::defaultsFor($key),
            ];
        }

        return $steps;
    }

    public function current(): WorkflowVersion
    {
        $current = WorkflowVersion::query()
            ->where('is_current', true)
            ->with('steps')
            ->first();

        if ($current instanceof WorkflowVersion) {
            return $current;
        }

        return $this->publish($this->defaultSteps(), null);
    }

    /**
     * @param  list<array{key: string, label: string, sort_order?: int, config?: array<string, mixed>|null}>  $steps
     */
    public function publish(array $steps, ?User $user): WorkflowVersion
    {
        return DB::transaction(function () use ($steps, $user): WorkflowVersion {
            WorkflowVersion::query()->where('is_current', true)->update(['is_current' => false]);

            $version = WorkflowVersion::query()->create([
                'version' => ((int) WorkflowVersion::query()->max('version')) + 1,
                'is_current' => true,
                'created_by' => $user?->id,
            ]);

            foreach (array_values($steps) as $index => $step) {
                $version->steps()->create([
                    'key' => $step['key'],
                    'label' => $step['label'],
                    'sort_order' => $step['sort_order'] ?? $index,
                    'config' => WorkflowStepConfig::normalize(
                        isset($step['config']) && is_array($step['config']) ? $step['config'] : null,
                        $step['key'],
                    ),
                ]);
            }

            return $version->load('steps');
        });
    }

    /**
     * @return list<string>
     */
    public function currentStepKeys(): array
    {
        $keys = $this->current()->stepKeys();

        return $keys !== [] ? $keys : ResearchPaper::STEPS;
    }

    /**
     * @return array<string, string>
     */
    public function currentStepLabels(): array
    {
        $labels = $this->current()->stepLabels();

        return $labels !== [] ? $labels : ResearchPaper::STEP_LABELS;
    }

    /**
     * @return list<string>
     */
    public function allKnownStepKeys(): array
    {
        $keys = collect(ResearchPaper::STEPS)
            ->merge(
                WorkflowStep::query()
                    ->orderBy('sort_order')
                    ->pluck('key')
            )
            ->unique()
            ->values()
            ->all();

        return $keys;
    }

    /**
     * @return array<string, string>
     */
    public function allKnownLabels(): array
    {
        $labels = ResearchPaper::STEP_LABELS;

        foreach (WorkflowStep::query()->orderBy('sort_order')->get(['key', 'label']) as $step) {
            $labels[$step->key] = $step->label;
        }

        return $labels;
    }

    /**
     * @return list<string>
     */
    public function stepKeysFor(?ResearchPaper $paper): array
    {
        if ($paper instanceof ResearchPaper) {
            return $paper->stepKeys();
        }

        return $this->currentStepKeys();
    }

    /**
     * @return array<string, string>
     */
    public function stepLabelsFor(?ResearchPaper $paper): array
    {
        if ($paper instanceof ResearchPaper) {
            return $paper->stepLabels();
        }

        return $this->currentStepLabels();
    }
}
