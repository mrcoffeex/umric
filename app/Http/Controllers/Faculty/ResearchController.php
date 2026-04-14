<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\TrackingRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ResearchController extends Controller
{
    public function index(Request $request, SchoolClass $class): Response
    {
        $this->ensureFacultyOwnsClass($request, $class);

        $papers = ResearchPaper::query()
            ->where('school_class_id', $class->id)
            ->with(['user', 'adviser', 'statistician'])
            ->latest()
            ->get()
            ->map(fn (ResearchPaper $paper) => [
                'id' => $paper->id,
                'tracking_id' => $paper->tracking_id,
                'title' => $paper->title,
                'status' => $paper->status,
                'current_step' => $paper->current_step,
                'step_label' => $paper->step_label,
                'step_ric_review' => $paper->step_ric_review,
                'step_plagiarism' => $paper->step_plagiarism,
                'step_outline_defense' => $paper->step_outline_defense,
                'step_rating' => $paper->step_rating,
                'step_final_manuscript' => $paper->step_final_manuscript,
                'step_final_defense' => $paper->step_final_defense,
                'step_hard_bound' => $paper->step_hard_bound,
                'outline_defense_schedule' => $paper->outline_defense_schedule?->toISOString(),
                'final_defense_schedule' => $paper->final_defense_schedule?->toISOString(),
                'grade' => $paper->grade,
                'student' => $paper->user ? [
                    'id' => $paper->user->id,
                    'name' => $paper->user->name,
                    'email' => $paper->user->email,
                ] : null,
                'adviser' => $paper->adviser ? ['id' => $paper->adviser->id, 'name' => $paper->adviser->name] : null,
                'statistician' => $paper->statistician ? ['id' => $paper->statistician->id, 'name' => $paper->statistician->name] : null,
            ]);

        $stepCounts = [];
        foreach (ResearchPaper::STEPS as $step) {
            $stepCounts[$step] = ResearchPaper::query()
                ->where('school_class_id', $class->id)
                ->where('current_step', $step)
                ->count();
        }

        return Inertia::render('faculty/Research/Index', [
            'schoolClass' => [
                'id' => $class->id,
                'name' => $class->name,
                'section' => $class->section,
                'class_code' => $class->class_code,
                'school_year' => $class->school_year,
                'semester' => $class->semester,
                'term' => $class->term,
            ],
            'papers' => $papers,
            'stepCounts' => $stepCounts,
            'stepLabels' => ResearchPaper::STEP_LABELS,
        ]);
    }

    public function show(Request $request, SchoolClass $class, ResearchPaper $paper): Response
    {
        $this->ensureFacultyOwnsClass($request, $class);
        $this->ensurePaperBelongsToClass($class, $paper);

        $paper->load(['user.profile', 'schoolClass.subjects.program', 'adviser', 'statistician', 'trackingRecords.updatedBy']);

        return Inertia::render('faculty/Research/Show', [
            'paper' => [
                'id' => $paper->id,
                'tracking_id' => $paper->tracking_id,
                'title' => $paper->title,
                'abstract' => $paper->abstract,
                'proponents' => $paper->proponents,
                'status' => $paper->status,
                'current_step' => $paper->current_step,
                'step_label' => $paper->step_label,
                'step_ric_review' => $paper->step_ric_review,
                'step_plagiarism' => $paper->step_plagiarism,
                'plagiarism_attempts' => $paper->plagiarism_attempts,
                'plagiarism_score' => $paper->plagiarism_score,
                'step_outline_defense' => $paper->step_outline_defense,
                'outline_defense_schedule' => $paper->outline_defense_schedule?->toISOString(),
                'step_rating' => $paper->step_rating,
                'grade' => $paper->grade,
                'step_final_manuscript' => $paper->step_final_manuscript,
                'step_final_defense' => $paper->step_final_defense,
                'final_defense_schedule' => $paper->final_defense_schedule?->toISOString(),
                'step_hard_bound' => $paper->step_hard_bound,
                'submission_date' => $paper->submission_date?->toDateString(),
                'student' => $paper->user ? [
                    'id' => $paper->user->id,
                    'name' => $paper->user->name,
                    'email' => $paper->user->email,
                    'avatar_url' => $paper->user->profile?->avatarUrl(),
                ] : null,
                'school_class' => $paper->schoolClass ? [
                    'id' => $paper->schoolClass->id,
                    'name' => $paper->schoolClass->name,
                    'section' => $paper->schoolClass->section,
                    'subjects' => $paper->schoolClass->subjects->map(fn ($subject) => [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'code' => $subject->code,
                        'program' => $subject->program ? [
                            'id' => $subject->program->id,
                            'name' => $subject->program->name,
                            'code' => $subject->program->code,
                        ] : null,
                    ])->values()->all(),
                ] : null,
                'adviser' => $paper->adviser ? ['id' => $paper->adviser->id, 'name' => $paper->adviser->name] : null,
                'statistician' => $paper->statistician ? ['id' => $paper->statistician->id, 'name' => $paper->statistician->name] : null,
            ],
            'trackingRecords' => $paper->trackingRecords->map(fn (TrackingRecord $record) => [
                'id' => $record->id,
                'step' => $record->step,
                'action' => $record->action,
                'status' => $record->status,
                'old_status' => $record->old_status,
                'notes' => $record->notes,
                'metadata' => $record->metadata,
                'updated_by' => $record->updatedBy ? [
                    'id' => $record->updatedBy->id,
                    'name' => $record->updatedBy->name,
                ] : null,
                'created_at' => $record->created_at?->toISOString(),
            ])->values(),
            'stepLabels' => ResearchPaper::STEP_LABELS,
        ]);
    }

    public function updateStep(Request $request, SchoolClass $class, ResearchPaper $paper): RedirectResponse
    {
        $this->ensureFacultyOwnsClass($request, $class);
        $this->ensurePaperBelongsToClass($class, $paper);

        $validated = $request->validate([
            'step' => ['required', Rule::in(['outline_defense', 'rating', 'final_defense'])],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'schedule' => ['nullable', 'date'],
            'grade' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $step = $validated['step'];
        $status = $validated['status'];
        $oldStatus = null;
        $updateData = [];
        $metadata = [];

        switch ($step) {
            case 'outline_defense':
                $oldStatus = $paper->step_outline_defense;
                $updateData['step_outline_defense'] = $status;

                if (! empty($validated['schedule'])) {
                    $updateData['outline_defense_schedule'] = $validated['schedule'];
                    $metadata['schedule'] = $validated['schedule'];
                }

                if ($status === 'passed') {
                    $updateData['current_step'] = 'rating';
                    $updateData['step_rating'] = $paper->step_rating ?? 'pending';
                }
                break;

            case 'rating':
                $oldStatus = $paper->step_rating;
                $updateData['step_rating'] = $status;

                if (array_key_exists('grade', $validated) && $validated['grade'] !== null) {
                    $updateData['grade'] = $validated['grade'];
                    $metadata['grade'] = $validated['grade'];
                }

                if ($status === 'rated') {
                    $updateData['current_step'] = 'final_manuscript';
                    $updateData['step_final_manuscript'] = $paper->step_final_manuscript ?? 'pending';
                }
                break;

            case 'final_defense':
                $oldStatus = $paper->step_final_defense;
                $updateData['step_final_defense'] = $status;

                if (! empty($validated['schedule'])) {
                    $updateData['final_defense_schedule'] = $validated['schedule'];
                    $metadata['schedule'] = $validated['schedule'];
                }

                if ($status === 'passed') {
                    $updateData['current_step'] = 'hard_bound';
                    $updateData['step_hard_bound'] = $paper->step_hard_bound ?? 'ongoing';
                }
                break;
        }

        $metadata['step_status'] = $status;

        $paper->update($updateData);

        TrackingRecord::log(
            $paper->id,
            $step,
            ucfirst(str_replace('_', ' ', $step)).': '.ucfirst($status),
            'under_review',
            $oldStatus,
            $request->user()->id,
            $validated['notes'] ?? null,
            $metadata ?: null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status updated.']);

        return back();
    }

    public function approve(Request $request, SchoolClass $class, ResearchPaper $paper): RedirectResponse
    {
        $this->ensureFacultyOwnsClass($request, $class);
        $this->ensurePaperBelongsToClass($class, $paper);

        if ($paper->current_step !== 'ric_review') {
            abort(422, 'Paper is not in RIC review step.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $oldStatus = $paper->step_ric_review;

        $paper->update([
            'step_ric_review' => 'approved',
            'current_step' => 'plagiarism_check',
            'step_plagiarism' => 'pending',
        ]);

        TrackingRecord::log(
            $paper->id,
            'ric_review',
            'RIC review approved',
            'approved',
            $oldStatus,
            $request->user()->id,
            $validated['notes'] ?? null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Paper approved for plagiarism check.']);

        return back();
    }

    private function ensureFacultyOwnsClass(Request $request, SchoolClass $class): void
    {
        if ($class->faculty_id !== $request->user()->id) {
            abort(403);
        }
    }

    private function ensurePaperBelongsToClass(SchoolClass $class, ResearchPaper $paper): void
    {
        if ($paper->school_class_id !== $class->id) {
            abort(404);
        }
    }
}
