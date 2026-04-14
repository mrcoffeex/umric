<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\ResearchPaper;
use App\Models\Sdg;
use App\Models\TrackingRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResearchController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ResearchPaper::with(['user', 'schoolClass', 'adviser', 'statistician'])
            ->withTrashed(false);

        if ($request->filled('step')) {
            $query->where('current_step', $request->step);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('tracking_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $papers = $query->latest()->paginate(20)->withQueryString();

        $stepCounts = [];
        foreach (ResearchPaper::STEPS as $step) {
            $stepCounts[$step] = ResearchPaper::where('current_step', $step)->count();
        }

        $facultyUsers = User::whereHas('profile', fn ($q) => $q->where('role', 'faculty'))
            ->orderBy('name')->get(['id', 'name']);

        $staffUsers = User::whereHas('profile', fn ($q) => $q->whereIn('role', ['staff', 'admin']))
            ->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/Research/Index', [
            'papers' => $papers,
            'stepCounts' => $stepCounts,
            'facultyUsers' => $facultyUsers,
            'staffUsers' => $staffUsers,
            'filters' => $request->only(['step', 'search']),
            'stepLabels' => ResearchPaper::STEP_LABELS,
        ]);
    }

    public function show(ResearchPaper $paper): Response
    {
        $paper->load([
            'user.profile',
            'schoolClass',
            'adviser',
            'statistician',
            'trackingRecords.updatedBy',
        ]);

        $facultyUsers = User::whereHas('profile', fn ($q) => $q->where('role', 'faculty'))
            ->orderBy('name')->get(['id', 'name']);

        $staffUsers = User::whereHas('profile', fn ($q) => $q->whereIn('role', ['staff', 'admin']))
            ->orderBy('name')->get(['id', 'name']);

        $sdgs = Sdg::where('is_active', true)->orderBy('number')->get(['id', 'number', 'name', 'color']);
        $agendas = Agenda::where('is_active', true)->orderBy('title')->get(['id', 'title']);

        return Inertia::render('admin/Research/Show', [
            'paper' => [
                'id' => $paper->id,
                'tracking_id' => $paper->tracking_id,
                'title' => $paper->title,
                'abstract' => $paper->abstract,
                'proponents' => $paper->proponents,
                'sdg_ids' => $paper->sdg_ids,
                'agenda_ids' => $paper->agenda_ids,
                'keywords' => $paper->keywords,
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
                'created_at' => $paper->created_at->toISOString(),
                'user' => [
                    'id' => $paper->user->id,
                    'name' => $paper->user->name,
                    'email' => $paper->user->email,
                ],
                'school_class' => $paper->schoolClass ? [
                    'id' => $paper->schoolClass->id,
                    'name' => $paper->schoolClass->name,
                ] : null,
                'adviser' => $paper->adviser ? ['id' => $paper->adviser->id, 'name' => $paper->adviser->name] : null,
                'statistician' => $paper->statistician ? ['id' => $paper->statistician->id, 'name' => $paper->statistician->name] : null,
                'tracking_records' => $paper->trackingRecords->map(fn ($r) => [
                    'id' => $r->id,
                    'step' => $r->step,
                    'action' => $r->action,
                    'status' => $r->status,
                    'old_status' => $r->old_status,
                    'notes' => $r->notes,
                    'metadata' => $r->metadata,
                    'performed_by' => $r->updatedBy?->name,
                    'created_at' => $r->created_at->toISOString(),
                ]),
            ],
            'facultyUsers' => $facultyUsers,
            'staffUsers' => $staffUsers,
            'sdgs' => $sdgs,
            'agendas' => $agendas,
            'stepLabels' => ResearchPaper::STEP_LABELS,
        ]);
    }

    public function assign(Request $request, ResearchPaper $paper): RedirectResponse
    {
        $validated = $request->validate([
            'adviser_id' => ['nullable', 'exists:users,id'],
            'statistician_id' => ['nullable', 'exists:users,id'],
        ]);

        $paper->update($validated);

        TrackingRecord::log(
            $paper->id,
            $paper->current_step,
            'Adviser/Statistician assigned',
            $paper->current_step_status ?? 'pending',
            null,
            $request->user()->id,
            'Assigned: '.($paper->adviser?->name ?? 'none').' / '.($paper->statistician?->name ?? 'none'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Assignments updated.']);

        return back();
    }

    public function updateStep(Request $request, ResearchPaper $paper): RedirectResponse
    {
        $step = $request->input('step');
        $status = $request->input('status');
        $notes = $request->input('notes');
        $grade = $request->input('grade');
        $schedule = $request->input('schedule');
        $plagiarism_score = $request->input('plagiarism_score');

        $oldStatus = null;
        $updateData = [];
        $metadata = [];

        switch ($step) {
            case 'ric_review':
                $oldStatus = $paper->step_ric_review;
                $updateData['step_ric_review'] = $status;
                if ($status === 'approved') {
                    $updateData['current_step'] = 'plagiarism_check';
                    $updateData['step_plagiarism'] = 'pending';
                } elseif ($status === 'rejected') {
                    $updateData['current_step'] = 'ric_review';
                }
                break;

            case 'plagiarism_check':
                if ($paper->plagiarism_attempts >= ResearchPaper::MAX_PLAGIARISM_ATTEMPTS && $status === 'failed') {
                    Inertia::flash('toast', ['type' => 'error', 'message' => 'Maximum plagiarism attempts reached.']);

                    return back();
                }
                $oldStatus = $paper->step_plagiarism;
                $updateData['step_plagiarism'] = $status;
                if ($plagiarism_score !== null) {
                    $updateData['plagiarism_score'] = $plagiarism_score;
                    $metadata['plagiarism_score'] = $plagiarism_score;
                }
                if ($status === 'failed') {
                    $updateData['plagiarism_attempts'] = $paper->plagiarism_attempts + 1;
                    $metadata['attempt'] = $paper->plagiarism_attempts + 1;
                } elseif ($status === 'passed') {
                    $updateData['current_step'] = 'outline_defense';
                    $updateData['step_outline_defense'] = 'pending';
                }
                break;

            case 'outline_defense':
                $oldStatus = $paper->step_outline_defense;
                $updateData['step_outline_defense'] = $status;
                if ($schedule) {
                    $updateData['outline_defense_schedule'] = $schedule;
                    $metadata['schedule'] = $schedule;
                }
                if ($status === 'passed') {
                    $updateData['current_step'] = 'rating';
                    $updateData['step_rating'] = 'pending';
                }
                break;

            case 'rating':
                $oldStatus = $paper->step_rating;
                $updateData['step_rating'] = $status;
                if ($grade !== null) {
                    $updateData['grade'] = $grade;
                    $metadata['grade'] = $grade;
                }
                if ($status === 'rated') {
                    $updateData['current_step'] = 'final_manuscript';
                    $updateData['step_final_manuscript'] = 'pending';
                }
                break;

            case 'final_manuscript':
                $oldStatus = $paper->step_final_manuscript;
                $updateData['step_final_manuscript'] = $status;
                if ($status === 'submitted') {
                    $updateData['current_step'] = 'final_defense';
                    $updateData['step_final_defense'] = 'pending';
                }
                break;

            case 'final_defense':
                $oldStatus = $paper->step_final_defense;
                $updateData['step_final_defense'] = $status;
                if ($schedule) {
                    $updateData['final_defense_schedule'] = $schedule;
                    $metadata['schedule'] = $schedule;
                }
                if ($status === 'passed') {
                    $updateData['current_step'] = 'hard_bound';
                    $updateData['step_hard_bound'] = 'ongoing';
                }
                break;

            case 'hard_bound':
                $oldStatus = $paper->step_hard_bound;
                $updateData['step_hard_bound'] = $status;
                if ($status === 'submitted') {
                    $updateData['current_step'] = 'completed';
                    $updateData['status'] = 'published';
                }
                break;
        }

        $paper->update($updateData);

        TrackingRecord::log(
            $paper->id,
            $step,
            ucfirst(str_replace('_', ' ', $step)).': '.ucfirst($status),
            $status,
            $oldStatus,
            $request->user()->id,
            $notes,
            $metadata ?: null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status updated.']);

        return back();
    }

    public function advanceStep(Request $request, ResearchPaper $paper): RedirectResponse
    {
        if (! $paper->canProceedToNextStep()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Cannot advance: current step not completed.']);

            return back();
        }

        $currentStep = $paper->current_step;
        $paper->advanceToNextStep();

        TrackingRecord::log(
            $paper->id,
            $paper->current_step,
            'Advanced to '.ResearchPaper::STEP_LABELS[$paper->current_step],
            'pending',
            null,
            $request->user()->id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Moved to next step.']);

        return back();
    }
}
