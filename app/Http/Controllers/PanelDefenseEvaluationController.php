<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePanelDefenseEvaluationRequest;
use App\Http\Requests\UpdatePanelDefenseEvaluationRequest;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationFormat;
use App\Models\PanelDefense;
use App\Models\PanelDefenseEvaluation;
use App\Models\User;
use App\Services\DefenseEvaluationPdfGenerator;
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

        $anyEvaluationFormatReady = EvaluationFormat::query()
            ->get()
            ->contains(fn (EvaluationFormat $f) => $f->isReady());

        return Inertia::render('Evaluation/Index', [
            'defenses' => $defenses,
            'anyEvaluationFormatReady' => $anyEvaluationFormatReady,
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
        $panelDefense->loadMissing('evaluationFormat');

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
        $panelDefense->loadMissing('evaluationFormat');

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
        $built = $this->buildLineItemsForCreate($panelDefense, $scores);

        PanelDefenseEvaluation::query()->create([
            'panel_defense_id' => $panelDefense->id,
            'evaluator_id' => (string) $request->user()->id,
            'line_items' => $built['line_items'],
            'final_score' => $built['final_score'],
            'comments' => $data['comments'],
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
        $panelDefenseEvaluation->loadMissing('panelDefense.evaluationFormat');
        $defense = $panelDefenseEvaluation->panelDefense;
        $format = $defense?->evaluationFormat;
        $built = $this->applyScoresToLineItemsSnapshot($lineItems, $scores, $format);
        $panelDefenseEvaluation->update([
            'line_items' => $built['line_items'],
            'final_score' => $built['final_score'],
            'comments' => $data['comments'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Evaluation updated.',
        ]);

        return redirect()->route('admin.evaluation.index', $this->indexRedirectQuery($request));
    }

    public function pdf(Request $request, PanelDefenseEvaluation $panelDefenseEvaluation): \Illuminate\Http\Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }
        $isAdmin = $request->routeIs('admin.*');
        if (! $isAdmin && (string) $panelDefenseEvaluation->evaluator_id !== (string) $user->id) {
            abort(403);
        }
        if ($isAdmin && ! $user->hasRole('admin', 'staff')) {
            abort(403);
        }

        $panelDefenseEvaluation->load(['evaluator', 'panelDefense.evaluationFormat']);
        $defense = $panelDefenseEvaluation->panelDefense;
        if ($defense === null || $defense->schedule === null) {
            abort(404);
        }

        $format = $defense->evaluationFormat;
        if (! $format?->isPdfExportEnabled()) {
            abort(404, __('PDF export is not enabled for this evaluation format.'));
        }

        try {
            $binary = app(DefenseEvaluationPdfGenerator::class)->build($panelDefenseEvaluation);
        } catch (\InvalidArgumentException) {
            abort(404);
        }

        $filename = 'defense-evaluation-'.$panelDefenseEvaluation->id.'.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array{line_items: list<array<string, mixed>>, final_score: int}
     */
    private function buildLineItemsForCreate(PanelDefense $panelDefense, array $scores): array
    {
        $lineItems = [];
        $panelDefense->loadMissing('evaluationFormat');
        $format = $panelDefense->evaluationFormat;
        $criteria = EvaluationCriterion::query()
            ->where('evaluation_format_id', $panelDefense->evaluation_format_id)
            ->orderBy('sort_order')
            ->get();
        foreach ($criteria as $c) {
            $id = (string) $c->id;
            $s = (int) ($scores[$id] ?? 0);
            $lineItems[] = [
                'criterion_id' => $id,
                'name' => $c->name,
                'content' => $c->content,
                'max_points' => (int) $c->max_points,
                'score' => $s,
            ];
        }

        $final = $this->computeFinalFromLineItems($lineItems, $format);

        return ['line_items' => $lineItems, 'final_score' => $final];
    }

    /**
     * @return array{line_items: list<array<string, mixed>>, final_score: int}
     */
    private function applyScoresToLineItemsSnapshot(array $lineItems, array $scores, ?EvaluationFormat $format = null): array
    {
        foreach ($lineItems as $i => $row) {
            if (! is_array($row) || ! isset($row['criterion_id'], $row['max_points'])) {
                continue;
            }
            $id = (string) $row['criterion_id'];
            $s = (int) ($scores[$id] ?? 0);
            $lineItems[$i]['score'] = $s;
        }

        $final = $this->computeFinalFromLineItems($lineItems, $format);

        return ['line_items' => $lineItems, 'final_score' => $final];
    }

    /**
     * @param  list<array<string, mixed>>  $lineItems
     */
    private function computeFinalFromLineItems(array $lineItems, ?EvaluationFormat $format = null): int
    {
        if ($format?->isChecklist()) {
            $total = 0;
            foreach ($lineItems as $row) {
                if (is_array($row) && isset($row['score'])) {
                    $total += (int) $row['score'];
                }
            }

            return $total;
        }

        $scoring = array_values(array_filter($lineItems, function (array $row): bool {
            return is_array($row) && isset($row['criterion_id'], $row['max_points'], $row['score']);
        }));
        if ($scoring === []) {
            return 0;
        }

        $allCapsAre100 = ! collect($scoring)->contains(
            fn (array $r) => (int) ($r['max_points'] ?? 0) !== 100,
        );

        if ($format !== null && $format->isScoring() && $format->scoringUsesWeights()) {
            $sum = 0.0;
            foreach ($scoring as $r) {
                $w = (int) $r['max_points'];
                $s = (int) $r['score'];
                $sum += ($s / 100.0) * $w;
            }

            return (int) round($sum);
        }

        if ($allCapsAre100) {
            $scores = array_map(fn (array $r) => (int) $r['score'], $scoring);
            $n = count($scores);

            return $n === 0 ? 0 : (int) round(array_sum($scores) / $n);
        }

        $sum = 0.0;
        foreach ($scoring as $r) {
            $w = (int) $r['max_points'];
            $s = (int) $r['score'];
            $sum += ($s / 100.0) * $w;
        }

        return (int) round($sum);
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
        $panelDefense->loadMissing('evaluationFormat');
        $format = $panelDefense->evaluationFormat;
        $criteria = $format
            ? $format->criteria()->orderBy('sort_order')->get()
            : collect();
        $totalMax = (int) $criteria->sum('max_points');
        $criteriaReady = false;
        if ($format !== null && $criteria->isNotEmpty()) {
            if ($format->isChecklist()) {
                $criteriaReady = true;
            } elseif ($format->scoringUsesWeights()) {
                $criteriaReady = $totalMax === EvaluationCriterion::MAX_TOTAL;
            } else {
                $criteriaReady = true;
            }
        }

        $out = [
            'context' => $isAdmin ? 'admin' : 'faculty',
            'mode' => $mode,
            'defense' => $this->defenseInfo($panelDefense),
            'listFilters' => $this->indexRedirectQuery($request),
            'evaluation_type' => $format?->evaluation_type ?? EvaluationFormat::TYPE_SCORING,
            'scoring_uses_weights' => $format?->scoringUsesWeights() ?? false,
            'criteria' => $criteria
                ->map(fn (EvaluationCriterion $c) => [
                    'id' => (string) $c->id,
                    'name' => $c->name,
                    'content' => $c->content,
                    'section_heading' => $c->section_heading,
                    'max_points' => (int) $c->max_points,
                    'sort_order' => (int) $c->sort_order,
                ])
                ->values()
                ->all(),
            'criteriaReady' => $criteriaReady,
            'criteriaTotalMax' => $totalMax,
        ];

        $out['canDownloadEvaluationPdf'] = false;
        if ($evaluation !== null && $format?->isPdfExportEnabled()) {
            $out['canDownloadEvaluationPdf'] = $isAdmin || ((string) $evaluation->evaluator_id === (string) $user->id);
        }

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

        $out['initialComment'] = $evaluation !== null
            ? (string) ($evaluation->comments ?? '')
            : '';

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function defenseInfo(PanelDefense $d): array
    {
        $d->loadMissing('evaluationFormat');

        return [
            'id' => (string) $d->id,
            'schedule' => $d->schedule?->toIso8601String(),
            'defense_type' => $d->defense_type,
            'defense_type_label' => $d->defense_type_label,
            'paper_title' => $d->researchPaper?->title,
            'tracking_id' => $d->researchPaper?->tracking_id,
            'student_name' => $d->researchPaper?->user?->name,
            'evaluation_format' => $d->evaluationFormat ? [
                'id' => (string) $d->evaluationFormat->id,
                'name' => $d->evaluationFormat->name,
                'evaluation_type' => $d->evaluationFormat->evaluation_type,
                'use_weights' => (bool) $d->evaluationFormat->use_weights,
            ] : null,
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
        $panelDefense->load(['researchPaper.user', 'evaluationFormat']);
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
            'comments' => $e->comments,
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
            $query->with(['researchPaper.user', 'evaluations.evaluator', 'evaluationFormat']);
        } else {
            $query->with(['researchPaper.user', 'evaluationFormat', 'evaluations' => function ($q) use ($user): void {
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
        $d->loadMissing('evaluationFormat');
        $onPanel = $d->includesPanelMember($user);
        $myEval = $d->evaluations->first(fn (PanelDefenseEvaluation $e) => (string) $e->evaluator_id === (string) $user->id);
        $formatReady = $d->evaluationFormat?->isReady() ?? false;

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
            'evaluation_format' => $d->evaluationFormat ? [
                'id' => (string) $d->evaluationFormat->id,
                'name' => $d->evaluationFormat->name,
                'evaluation_type' => $d->evaluationFormat->evaluation_type,
                'use_weights' => (bool) $d->evaluationFormat->use_weights,
            ] : null,
            'evaluation_format_ready' => $formatReady,
            'can_evaluate' => $onPanel && $myEval === null && $formatReady,
            'my_evaluation' => $myEval ? $this->mapMyEvaluation($myEval) : null,
        ];

        if ($isAdmin) {
            $row['evaluations'] = $d->evaluations->map(fn (PanelDefenseEvaluation $e) => [
                'id' => (string) $e->id,
                'evaluator_id' => (string) $e->evaluator_id,
                'evaluator_name' => $e->evaluator?->name ?? 'Unknown',
                'line_items' => is_array($e->line_items) ? $e->line_items : [],
                'final_score' => (int) $e->final_score,
                'comments' => $e->comments,
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
            'comments' => $e->comments,
        ];
    }
}
