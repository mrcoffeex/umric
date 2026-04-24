<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCriterion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationCriteriaController extends Controller
{
    public function index(): Response
    {
        $criteria = EvaluationCriterion::query()->orderBy('sort_order')->get();

        return Inertia::render('admin/EvaluationCriteria/Index', [
            'criteria' => $criteria,
            'total_max' => (int) $criteria->sum('max_points'),
            'target_total' => EvaluationCriterion::MAX_TOTAL,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:500'],
            'max_points' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $current = (int) EvaluationCriterion::query()->sum('max_points');
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

        $next = (int) (EvaluationCriterion::query()->max('sort_order') ?? 0) + 1;
        EvaluationCriterion::query()->create([
            'name' => $validated['name'],
            'max_points' => (int) $validated['max_points'],
            'sort_order' => $next,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Criterion created.'),
        ]);

        return back();
    }

    public function update(Request $request, EvaluationCriterion $evaluation_criterion): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:500'],
            'max_points' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $criterion = $evaluation_criterion;
        $current = (int) EvaluationCriterion::query()
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

        $criterion->update([
            'name' => $validated['name'],
            'max_points' => (int) $validated['max_points'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Criterion updated.'),
        ]);

        return back();
    }

    public function destroy(EvaluationCriterion $evaluation_criterion): RedirectResponse
    {
        $evaluation_criterion->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Criterion removed.'),
        ]);

        return back();
    }
}
