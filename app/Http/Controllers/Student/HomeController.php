<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Services\DashboardInsightsBuilder;
use App\Services\WorkflowCatalog;
use App\Support\JsonContains;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, DashboardInsightsBuilder $insightsBuilder): Response
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $user = $request->user();

        $classes = SchoolClass::query()
            ->whereHas('members', fn ($query) => $query->where('student_id', $user->id))
            ->with('subjects.program')
            ->get();

        $userId = $user->id;
        $hasClass = $classes->isNotEmpty();

        $paper = ResearchPaper::query()
            ->with([
                'workflowVersion.steps',
                'trackingRecords' => fn ($query) => $query->latest('status_changed_at')->limit(1),
            ])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId);
                JsonContains::whereArrayObjectContains(
                    $query,
                    'proponents',
                    ['id' => (string) $userId],
                    or: true,
                );
            })
            ->first();

        return Inertia::render('student/Home', [
            'classes' => $classes,
            'paper' => $paper ? [
                ...$paper->toArray(),
                'is_returned' => $paper->isRicReviewReturned(),
                'last_update' => $paper->trackingRecords->first() ? [
                    'step' => $paper->trackingRecords->first()->step,
                    'action' => $paper->trackingRecords->first()->action,
                    'status' => $paper->trackingRecords->first()->status,
                    'notes' => $paper->trackingRecords->first()->notes,
                    'at' => ($paper->trackingRecords->first()->status_changed_at
                        ?? $paper->trackingRecords->first()->created_at)?->toISOString(),
                ] : null,
                'upcoming_defense' => $this->upcomingDefense($paper),
            ] : null,
            'stepLabels' => app(WorkflowCatalog::class)->stepLabelsFor($paper),
            'steps' => app(WorkflowCatalog::class)->stepKeysFor($paper),
            'hasClass' => $hasClass,
            'insights' => $insightsBuilder->forStudent(
                $user,
                $hasClass,
                $paper,
            ),
        ]);
    }

    /**
     * @return array{type: string, at: string}|null
     */
    private function upcomingDefense(ResearchPaper $paper): ?array
    {
        $candidates = collect([
            ['type' => 'outline', 'at' => $paper->outline_defense_schedule],
            ['type' => 'final', 'at' => $paper->final_defense_schedule],
        ])
            ->filter(fn (array $item) => $item['at'] !== null && $item['at']->isFuture())
            ->sortBy('at')
            ->first();

        if (! $candidates) {
            return null;
        }

        return [
            'type' => $candidates['type'],
            'at' => $candidates['at']->toISOString(),
        ];
    }
}
