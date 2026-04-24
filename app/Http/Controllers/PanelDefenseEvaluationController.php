<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePanelDefenseEvaluationRequest;
use App\Http\Requests\UpdatePanelDefenseEvaluationRequest;
use App\Models\EvaluationCriterion;
use App\Models\PanelDefense;
use App\Models\PanelDefenseEvaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class PanelDefenseEvaluationController extends Controller
{
    public function index(Request $request): Response
    {
        $isAdmin = $request->routeIs('admin.*');
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        $perPage = max(1, min(50, (int) $request->get('per_page', 15)));

        $query = $this->baseDefenseQuery($request, $isAdmin, $user);

        /** @var LengthAwarePaginator<int, PanelDefense> $defenses */
        $defenses = $query
            ->orderBy('schedule')
            ->paginate($perPage)
            ->withQueryString();

        $defenses->setCollection(
            $defenses->getCollection()->map(fn (PanelDefense $d) => $this->mapDefenseRow(
                $d,
                $user,
                $isAdmin,
            )),
        );

        $criteria = EvaluationCriterion::query()->orderBy('sort_order')->get();
        $totalMax = (int) $criteria->sum('max_points');
        $criteriaReady = $criteria->isNotEmpty() && $totalMax === EvaluationCriterion::MAX_TOTAL;

        return Inertia::render('Evaluation/Index', [
            'defenses' => $defenses,
            'criteriaReady' => $criteriaReady,
            'criteriaTotalMax' => $totalMax,
            'context' => $isAdmin ? 'admin' : 'faculty',
            'defenseTypeOptions' => collect(PanelDefense::DEFENSE_TYPES)
                ->map(fn (string $label, string $key) => ['value' => $key, 'label' => $label])
                ->values(),
            'filters' => $this->currentFilters($request, $isAdmin, $perPage),
        ]);
    }

    public function evaluate(Request $request, PanelDefense $panelDefense): Response|RedirectResponse
    {
        $isAdmin = $request->routeIs('admin.*');
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        $this->loadDefenseForContext($panelDefense, $isAdmin, $user);
        if ($panelDefense->schedule === null) {
            abort(404);
        }

        $onPanel = $panelDefense->includesPanelMember($user);
        $myEval = PanelDefenseEvaluation::query()
            ->where('panel_defense_id', (string) $panelDefense->id)
            ->where('evaluator_id', (string) $user->id)
            ->first();

        if (! $onPanel) {
            abort(403);
        }

        if ($myEval) {
            $myEval->loadMissing('evaluator');

            return Inertia::render('Evaluation/Evaluate', $this->evaluatePagePayload(
                $request,
                $panelDefense,
                $isAdmin,
                'view',
                $myEval,
            ));
        }

        if (! $this->userCanStartNew($user, $isAdmin)) {
            abort(403);
        }

        return Inertia::render('Evaluation/Evaluate', $this->evaluatePagePayload(
            $request,
            $panelDefense,
            $isAdmin,
            'create',
            null,
        ));
    }

    public function edit(Request $request, PanelDefenseEvaluation $panelDefenseEvaluation): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->hasRole('admin', 'staff')) {
            abort(403);
        }

        $isAdmin = true;
        $panelDefense = $panelDefenseEvaluation->panelDefense;
        if (! $panelDefense instanceof PanelDefense) {
            abort(404);
        }
        if ($panelDefense->schedule === null) {
            abort(404);
        }
        $panelDefenseEvaluation->loadMissing('evaluator');
        $this->loadDefenseForContext($panelDefense, $isAdmin, $user);

        return Inertia::render('Evaluation/Evaluate', $this->evaluatePagePayload(
            $request,
            $panelDefense,
            $isAdmin,
            'admin_edit',
            $panelDefenseEvaluation,
        ));
    }

    public function store(StorePanelDefenseEvaluationRequest $request, PanelDefense $panelDefense): RedirectResponse
    {
        $data = $request->validated();
        $scores = (array) ($data['scores'] ?? []);
        $built = $this->buildLineItemsForCreate($scores);

        PanelDefenseEvaluation::query()->create([
            'panel_defense_id' => $panelDefense->id,
            'evaluator_id' => (string) $request->user()->id,
            'line_items' => $built['line_items'],
            'final_score' => $built['final_score'],
        ]);

        $isFacultyOnly = $request->user()?->isFaculty() === true;

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $isFacultyOnly
                ? 'Evaluation saved. Scores are final and cannot be edited.'
                : 'Evaluation saved.',
        ]);

        if ($request->routeIs('admin.*')) {
            return redirect()->route('admin.evaluation.index', $this->indexRedirectQuery($request));
        }

        return redirect()->route('faculty.evaluation.index', $this->indexRedirectQuery($request));
    }

    public function update(UpdatePanelDefenseEvaluationRequest $request, PanelDefenseEvaluation $panelDefenseEvaluation): RedirectResponse
    {
        $data = $request->validated();
        $scores = (array) ($data['scores'] ?? []);
        $lineItems = $panelDefenseEvaluation->line_items;
        if (! is_array($lineItems)) {
            $lineItems = [];
        }
        $built = $this->applyScoresToLineItemsSnapshot($lineItems, $scores);
        $panelDefenseEvaluation->update([
            'line_items' => $built['line_items'],
            'final_score' => $built['final_score'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Evaluation updated.',
        ]);

        return redirect()->route('admin.evaluation.index', $this->indexRedirectQuery($request));
    }

    /**
     * @return array{line_items: list<array<string, mixed>>, final_score: int}
     */
    private function buildLineItemsForCreate(array $scores): array
    {
        $lineItems = [];
        $final = 0;
        $criteria = EvaluationCriterion::query()->orderBy('sort_order')->get();
        foreach ($criteria as $c) {
            $id = (string) $c->id;
            $s = (int) ($scores[$id] ?? 0);
            $lineItems[] = [
                'criterion_id' => $id,
                'name' => $c->name,
                'max_points' => (int) $c->max_points,
                'score' => $s,
            ];
            $final += $s;
        }

        return ['line_items' => $lineItems, 'final_score' => $final];
    }

    /**
     * @param  list<array<string, mixed>>  $lineItems
     * @return array{line_items: list<array<string, mixed>>, final_score: int}
     */
    private function applyScoresToLineItemsSnapshot(array $lineItems, array $scores): array
    {
        $final = 0;
        foreach ($lineItems as $i => $row) {
            if (! is_array($row) || ! isset($row['criterion_id'], $row['max_points'])) {
                continue;
            }
            $id = (string) $row['criterion_id'];
            $s = (int) ($scores[$id] ?? 0);
            $lineItems[$i]['score'] = $s;
            $final += $s;
        }

        return ['line_items' => $lineItems, 'final_score' => $final];
    }

    /**
     * @return array<string, int|string|array<string, string>|null|bool|array<int, array<string, mixed>>|int|null>
     */
    private function evaluatePagePayload(
        Request $request,
        PanelDefense $panelDefense,
        bool $isAdmin,
        string $mode,
        ?PanelDefenseEvaluation $evaluation,
    ): array {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }
        $criteria = EvaluationCriterion::query()->orderBy('sort_order')->get();
        $totalMax = (int) $criteria->sum('max_points');
        $criteriaReady = $criteria->isNotEmpty() && $totalMax === EvaluationCriterion::MAX_TOTAL;

        $out = [
            'context' => $isAdmin ? 'admin' : 'faculty',
            'mode' => $mode,
            'defense' => $this->defenseInfo($panelDefense),
            'listFilters' => $this->indexRedirectQuery($request),
            'criteria' => $criteria
                ->map(fn (EvaluationCriterion $c) => [
                    'id' => (string) $c->id,
                    'name' => $c->name,
                    'max_points' => (int) $c->max_points,
                    'sort_order' => (int) $c->sort_order,
                ])
                ->values()
                ->all(),
            'criteriaReady' => $criteriaReady,
            'criteriaTotalMax' => $totalMax,
        ];

        if ($mode === 'create' && $evaluation === null) {
            $out['evaluation'] = null;
            $out['initialScores'] = $criteria
                ->mapWithKeys(fn (EvaluationCriterion $c) => [(string) $c->id => 0])
                ->all();
        } elseif (in_array($mode, ['view', 'admin_edit'], true) && $evaluation !== null) {
            $out['evaluation'] = $this->mapSingleEvaluation($evaluation, $user);
            $lineItems = $evaluation->line_items;
            if (! is_array($lineItems)) {
                $lineItems = [];
            }
            $initial = [];
            foreach ($lineItems as $row) {
                if (is_array($row) && isset($row['criterion_id'], $row['score'])) {
                    $initial[(string) $row['criterion_id']] = (int) $row['score'];
                }
            }
            $out['initialScores'] = $initial;
        } else {
            $out['evaluation'] = null;
            $out['initialScores'] = [];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function defenseInfo(PanelDefense $d): array
    {
        return [
            'id' => (string) $d->id,
            'schedule' => $d->schedule?->toIso8601String(),
            'defense_type' => $d->defense_type,
            'defense_type_label' => $d->defense_type_label,
            'paper_title' => $d->researchPaper?->title,
            'tracking_id' => $d->researchPaper?->tracking_id,
            'student_name' => $d->researchPaper?->user?->name,
        ];
    }

    private function userCanStartNew(User $user, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return $user->hasRole('admin', 'staff');
        }

        return $user->hasRole('faculty');
    }

    private function loadDefenseForContext(PanelDefense $panelDefense, bool $isAdmin, User $user): void
    {
        if (! $isAdmin) {
            if (! PanelDefense::query()->whereKey($panelDefense->id)->whereUserIsOnPanel($user)->exists()) {
                abort(403);
            }
        }
        $panelDefense->load(['researchPaper.user']);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSingleEvaluation(PanelDefenseEvaluation $e, User $user): array
    {
        return [
            'id' => (string) $e->id,
            'line_items' => is_array($e->line_items) ? $e->line_items : [],
            'final_score' => (int) $e->final_score,
            'is_mine' => (string) $e->evaluator_id === (string) $user->id,
            'evaluator_name' => $e->evaluator?->name,
        ];
    }

    /**
     * @return array<string, int|string|array<string, string>|null>
     */
    private function indexRedirectQuery(Request $request): array
    {
        return $request->only(['q', 'defense_type', 'status', 'per_page', 'page', 'schedule_date']);
    }

    /**
     * @return Builder<PanelDefense>
     */
    private function baseDefenseQuery(Request $request, bool $isAdmin, User $user)
    {
        $query = PanelDefense::query()
            ->whereNotNull('schedule');

        if (! $isAdmin) {
            $query->whereUserIsOnPanel($user);
        }

        $q = trim((string) $request->get('q', ''));
        if ($q !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q).'%';
            $query->where(function ($outer) use ($like): void {
                $outer->whereHas('researchPaper', function ($pq) use ($like): void {
                    $pq->where('title', 'ilike', $like)
                        ->orWhere('tracking_id', 'ilike', $like);
                })->orWhereHas('researchPaper.user', function ($uq) use ($like): void {
                    $uq->where('name', 'ilike', $like);
                });
            });
        }

        $type = $request->get('defense_type');
        if (is_string($type) && $type !== '' && array_key_exists($type, PanelDefense::DEFENSE_TYPES)) {
            $query->where('defense_type', $type);
        }

        $scheduleDate = trim((string) $request->get('schedule_date', ''));
        if ($scheduleDate !== '' && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $scheduleDate, $m)) {
            $y = (int) $m[1];
            $mon = (int) $m[2];
            $d = (int) $m[3];
            if (checkdate($mon, $d, $y)) {
                $query->whereDate('schedule', $scheduleDate);
            }
        }

        $status = (string) $request->get('status', 'all');
        if ($isAdmin) {
            if ($status === 'with_evaluations') {
                $query->whereHas('evaluations');
            } elseif ($status === 'no_evaluations') {
                $query->whereDoesntHave('evaluations');
            }
        } else {
            if ($status === 'mine_submitted') {
                $query->whereHas('evaluations', fn ($q) => $q->where('evaluator_id', (string) $user->id));
            } elseif ($status === 'mine_pending') {
                $query->whereDoesntHave('evaluations', fn ($q) => $q->where('evaluator_id', (string) $user->id));
            }
        }

        if ($isAdmin) {
            $query->with(['researchPaper.user', 'evaluations.evaluator']);
        } else {
            $query->with(['researchPaper.user', 'evaluations' => function ($q) use ($user): void {
                $q->where('evaluator_id', (string) $user->id);
            }]);
        }

        return $query;
    }

    private function currentFilters(Request $request, bool $isAdmin, int $perPage): array
    {
        $out = [
            'q' => (string) $request->get('q', ''),
            'defense_type' => (string) $request->get('defense_type', ''),
            'status' => (string) $request->get('status', 'all'),
            'schedule_date' => (string) $request->get('schedule_date', ''),
            'per_page' => $perPage,
        ];
        if ($isAdmin) {
            $out['status_options'] = [
                'all' => 'All',
                'with_evaluations' => 'Has any evaluation',
                'no_evaluations' => 'No evaluations yet',
            ];
        } else {
            $out['status_options'] = [
                'all' => 'All on my panel',
                'mine_pending' => 'My evaluation pending',
                'mine_submitted' => 'I submitted',
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDefenseRow(PanelDefense $d, User $user, bool $isAdmin): array
    {
        $onPanel = $d->includesPanelMember($user);
        $myEval = $d->evaluations->first(fn (PanelDefenseEvaluation $e) => (string) $e->evaluator_id === (string) $user->id);

        $panelMembers = [];
        foreach ($d->panel_members ?? [] as $member) {
            $name = is_string($member) ? trim($member) : (string) $member;
            if ($name !== '') {
                $panelMembers[] = $name;
            }
        }

        $row = [
            'id' => $d->id,
            'schedule' => $d->schedule?->toIso8601String(),
            'defense_type' => $d->defense_type,
            'defense_type_label' => $d->defense_type_label,
            'research_paper_id' => $d->research_paper_id !== null
                ? (string) $d->research_paper_id
                : null,
            'paper_title' => $d->researchPaper?->title,
            'tracking_id' => $d->researchPaper?->tracking_id,
            'student_name' => $d->researchPaper?->user?->name,
            'panel_members' => $panelMembers,
            'is_on_panel' => $onPanel,
            'can_evaluate' => $onPanel && $myEval === null,
            'my_evaluation' => $myEval ? $this->mapMyEvaluation($myEval) : null,
        ];

        if ($isAdmin) {
            $row['evaluations'] = $d->evaluations->map(fn (PanelDefenseEvaluation $e) => [
                'id' => (string) $e->id,
                'evaluator_id' => (string) $e->evaluator_id,
                'evaluator_name' => $e->evaluator?->name ?? 'Unknown',
                'line_items' => is_array($e->line_items) ? $e->line_items : [],
                'final_score' => (int) $e->final_score,
                'is_mine' => (string) $e->evaluator_id === (string) $user->id,
            ])->values()->all();
            $row['average_score'] = $d->evaluations->isEmpty()
                ? null
                : round((float) $d->evaluations->avg('final_score'), 1);
        } else {
            $row['evaluations'] = [];
        }

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMyEvaluation(PanelDefenseEvaluation $e): array
    {
        return [
            'id' => (string) $e->id,
            'line_items' => is_array($e->line_items) ? $e->line_items : [],
            'final_score' => (int) $e->final_score,
        ];
    }
}
