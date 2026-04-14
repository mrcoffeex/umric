<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\TrackingRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ResearchController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $userId = $request->user()->id;

        $papers = ResearchPaper::query()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereRaw('"proponents"::jsonb @> ?::jsonb', [json_encode([['id' => (string) $userId]])]);
            })
            ->with(['schoolClass', 'user', 'adviser'])
            ->latest()
            ->get();

        return Inertia::render('student/Research/Index', [
            'papers' => $papers,
            'stepLabels' => ResearchPaper::STEP_LABELS,
            'steps' => ResearchPaper::STEPS,
        ]);
    }

    public function show(Request $request, ResearchPaper $paper): Response
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $userId = $request->user()->id;
        $isProponent = collect($paper->proponents ?? [])->contains('id', (string) $userId);

        if ($paper->user_id !== $userId && ! $isProponent) {
            abort(403);
        }

        $paper->load(['schoolClass', 'trackingRecords.updatedBy', 'adviser', 'statistician']);

        return Inertia::render('student/Research/Show', [
            'paper' => $paper,
            'trackingLog' => $paper->trackingRecords,
            'stepLabels' => ResearchPaper::STEP_LABELS,
            'steps' => ResearchPaper::STEPS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
            'proponents' => ['nullable', 'array'],
        ]);

        $schoolClass = SchoolClass::query()
            ->whereHas('members', fn ($query) => $query->where('student_id', $request->user()->id))
            ->first();

        if (! $schoolClass) {
            return back()->withErrors(['class' => 'Join a class before submitting a research paper.']);
        }

        $paper = ResearchPaper::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'abstract' => $validated['abstract'] ?? null,
            'proponents' => $validated['proponents'] ?? null,
            'school_class_id' => $schoolClass->id,
            'tracking_id' => 'RP-'.strtoupper(Str::random(8)),
            'current_step' => 'title_proposal',
            'submission_date' => now(),
            'status' => 'submitted',
        ]);

        TrackingRecord::log(
            $paper->id,
            'title_proposal',
            'Title proposal submitted',
            'submitted',
            null,
            $request->user()->id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Research paper submitted.']);

        return redirect()->route('student.research.show', $paper);
    }

    public function update(Request $request, ResearchPaper $paper): RedirectResponse
    {
        if (! $request->user()->isStudent() || $paper->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($paper->current_step !== 'title_proposal') {
            return back()->withErrors(['step' => 'Only title proposals can be edited.']);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
            'proponents' => ['nullable', 'array'],
        ]);

        $paper->update([
            'title' => $validated['title'],
            'abstract' => $validated['abstract'] ?? $paper->abstract,
            'proponents' => $validated['proponents'] ?? $paper->proponents,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Research paper updated.']);

        return back();
    }

    public function destroy(Request $request, ResearchPaper $paper): RedirectResponse
    {
        if (! $request->user()->isStudent() || $paper->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($paper->current_step !== 'title_proposal') {
            return back()->withErrors(['step' => 'Only title proposals can be deleted.']);
        }

        $paper->trackingRecords()->delete();
        $paper->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Research paper deleted.']);

        return redirect()->route('student.research.index');
    }
}
