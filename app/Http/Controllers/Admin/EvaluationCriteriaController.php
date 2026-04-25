<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationFormat;
use App\Support\RichTextSanitizer;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EvaluationCriteriaController extends Controller
{
    public function index(EvaluationFormat $evaluationFormat): Response
    {
        $criteria = EvaluationCriterion::query()
            ->where('evaluation_format_id', $evaluationFormat->id)
            ->orderBy('sort_order')
            ->get();

        $total = (int) $criteria->sum('max_points');

        return Inertia::render('admin/EvaluationCriteria/Index', [
            'format' => [
                'id' => (string) $evaluationFormat->id,
                'name' => $evaluationFormat->name,
                'evaluation_type' => $evaluationFormat->evaluation_type,
                'use_weights' => (bool) $evaluationFormat->use_weights,
            ],
            'criteria' => $criteria,
            'total_max' => $total,
            'target_total' => EvaluationCriterion::MAX_TOTAL,
        ]);
    }

    public function store(Request $request, EvaluationFormat $evaluationFormat): RedirectResponse
    {
        $sectionRule = ['nullable', 'string', 'max:500'];

        if ($evaluationFormat->isChecklist()) {
            $validated = $request->validate([
                'content' => $this->contentValidationRules(),
                'section_heading' => $sectionRule,
            ]);
            $next = (int) (EvaluationCriterion::query()
                ->where('evaluation_format_id', $evaluationFormat->id)
                ->max('sort_order') ?? 0) + 1;
            EvaluationCriterion::query()->create([
                'evaluation_format_id' => $evaluationFormat->id,
                'content' => $validated['content'],
                'section_heading' => $validated['section_heading'] ?? null,
                'max_points' => 1,
                'sort_order' => $next,
            ]);
        } else {
            if ($evaluationFormat->scoringUsesWeights()) {
                $validated = $request->validate([
                    'content' => $this->contentValidationRules(),
                    'max_points' => ['required', 'integer', 'min:1', 'max:100'],
                    'section_heading' => $sectionRule,
                ]);

                $current = (int) EvaluationCriterion::query()
                    ->where('evaluation_format_id', $evaluationFormat->id)
                    ->sum('max_points');
                if ($current + (int) $validated['max_points'] > EvaluationCriterion::MAX_TOTAL) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'max_points' => __('These weights cannot total more than 100. Current: :c. Remaining: :r.', [
                                'c' => (string) $current,
                                'r' => (string) (EvaluationCriterion::MAX_TOTAL - $current),
                            ]),
                        ]);
                }
                $newMax = (int) $validated['max_points'];
            } else {
                $validated = $request->validate([
                    'content' => $this->contentValidationRules(),
                    'section_heading' => $sectionRule,
                ]);
                $newMax = EvaluationCriterion::MAX_TOTAL;
            }

            $next = (int) (EvaluationCriterion::query()
                ->where('evaluation_format_id', $evaluationFormat->id)
                ->max('sort_order') ?? 0) + 1;
            EvaluationCriterion::query()->create([
                'evaluation_format_id' => $evaluationFormat->id,
                'content' => $validated['content'],
                'section_heading' => $validated['section_heading'] ?? null,
                'max_points' => $newMax,
                'sort_order' => $next,
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Criterion created.'),
        ]);

        return back();
    }

    public function update(
        Request $request,
        EvaluationFormat $evaluationFormat,
        EvaluationCriterion $evaluation_criterion,
    ): RedirectResponse {
        $criterion = $this->criterionInFormatOrAbort($evaluationFormat, $evaluation_criterion);

        $sectionRule = ['nullable', 'string', 'max:500'];

        if ($evaluationFormat->isChecklist()) {
            $validated = $request->validate([
                'content' => $this->contentValidationRules(),
                'section_heading' => $sectionRule,
            ]);
            $criterion->update([
                'content' => $validated['content'],
                'section_heading' => $validated['section_heading'] ?? null,
                'max_points' => 1,
            ]);
        } else {
            if ($evaluationFormat->scoringUsesWeights()) {
                $validated = $request->validate([
                    'content' => $this->contentValidationRules(),
                    'max_points' => ['required', 'integer', 'min:1', 'max:100'],
                    'section_heading' => $sectionRule,
                ]);

                $current = (int) EvaluationCriterion::query()
                    ->where('evaluation_format_id', $evaluationFormat->id)
                    ->where('id', '!=', $criterion->id)
                    ->sum('max_points');
                if ($current + (int) $validated['max_points'] > EvaluationCriterion::MAX_TOTAL) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'max_points' => __('These weights cannot total more than 100. Other criteria: :c. Remaining for this one: at most :r.', [
                                'c' => (string) $current,
                                'r' => (string) (EvaluationCriterion::MAX_TOTAL - $current),
                            ]),
                        ]);
                }
                $newMax = (int) $validated['max_points'];
            } else {
                $validated = $request->validate([
                    'content' => $this->contentValidationRules(),
                    'section_heading' => $sectionRule,
                ]);
                $newMax = EvaluationCriterion::MAX_TOTAL;
            }

            $criterion->update([
                'content' => $validated['content'],
                'section_heading' => $validated['section_heading'] ?? null,
                'max_points' => $newMax,
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Criterion updated.'),
        ]);

        return back();
    }

    public function destroy(EvaluationFormat $evaluationFormat, EvaluationCriterion $evaluation_criterion): RedirectResponse
    {
        $criterion = $this->criterionInFormatOrAbort($evaluationFormat, $evaluation_criterion);
        $criterion->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Criterion removed.'),
        ]);

        return back();
    }

    private function criterionInFormatOrAbort(EvaluationFormat $format, EvaluationCriterion $criterion): EvaluationCriterion
    {
        if ((string) $criterion->evaluation_format_id !== (string) $format->id) {
            throw new NotFoundHttpException;
        }

        return $criterion;
    }

    /**
     * @return list<string|Closure>
     */
    private function contentValidationRules(): array
    {
        return [
            'required',
            'string',
            'max:65500',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value)) {
                    $fail(__('Invalid criterion text.'));

                    return;
                }
                $safe = RichTextSanitizer::sanitize($value);
                $plain = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($safe), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
                if ($plain === '') {
                    $fail(__('The criterion text cannot be empty.'));
                }
            },
        ];
    }
}
