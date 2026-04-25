<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Mail\ResearchStatusUpdated;
use App\Models\Agenda;
use App\Models\Comment;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\Sdg;
use App\Models\TrackingRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ResearchController extends Controller
{
    public function index(Request $request, SchoolClass $class): Response
    {
        $this->ensureFacultyOwnsClass($request, $class);

        // Get student IDs enrolled in this class
        $studentIds = DB::table('school_class_members')
            ->where('school_class_id', $class->id)
            ->pluck('student_id');

        $paperQuery = ResearchPaper::query()
            ->whereIn('user_id', $studentIds)
            ->with(['user', 'adviser', 'statistician']);

        if ($request->filled('search')) {
            $search = $request->search;
            $paperQuery->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('tracking_id', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', "%{$search}%"));
            });
        }

        if ($request->filled('step')) {
            $paperQuery->where('current_step', $request->step);
        }

        $papers = $paperQuery->latest()->paginate(20)->withQueryString();

        $papers->setCollection(
            $papers->getCollection()->map(fn (ResearchPaper $paper) => [
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
                'sdg_ids' => $paper->sdg_ids ?? [],
                'agenda_ids' => $paper->agenda_ids ?? [],
            ])
        );

        $stepCountsRaw = ResearchPaper::query()
            ->whereIn('user_id', $studentIds)
            ->selectRaw('current_step, count(*) as cnt')
            ->groupBy('current_step')
            ->pluck('cnt', 'current_step');

        $stepCounts = [];
        foreach (ResearchPaper::STEPS as $step) {
            $stepCounts[$step] = (int) ($stepCountsRaw[$step] ?? 0);
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
            'sdgs' => Sdg::where('is_active', true)->orderBy('number')->get(['id', 'number', 'name', 'color']),
            'agendas' => Agenda::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'step']),
        ]);
    }

    public function show(Request $request, SchoolClass $class, ResearchPaper $paper): Response
    {
        $this->ensurePaperBelongsToClass($class, $paper);
        $this->ensureFacultyCanAccessPaper($request, $class, $paper);

        $paper->load(['user.profile', 'schoolClass.subjects.program', 'adviser', 'statistician', 'trackingRecords.updatedBy', 'comments.user', 'panelDefenses.createdBy', 'files']);

        return Inertia::render('faculty/Research/Show', [
            'schoolClass' => [
                'id' => $class->id,
                'name' => $class->name,
                'section' => $class->section,
            ],
            'paper' => [
                'id' => $paper->id,
                'tracking_id' => $paper->tracking_id,
                'title' => $paper->title,
                'abstract' => $paper->abstract,
                'proponents' => $paper->proponents,
                'sdg_ids' => $paper->sdg_ids,
                'agenda_ids' => $paper->agenda_ids,
                'keywords' => $paper->keywords,
                'status' => $paper->status,
                'current_step' => $paper->current_step,
                'step_label' => $paper->step_label,
                'user_id' => $paper->user_id,
                'step_ric_review' => $paper->step_ric_review,
                'step_plagiarism' => $paper->step_plagiarism,
                'plagiarism_attempts' => $paper->plagiarism_attempts,
                'plagiarism_score' => $paper->plagiarism_score,
                'step_outline_defense' => $paper->step_outline_defense,
                'outline_defense_schedule' => $paper->outline_defense_schedule?->toISOString(),
                'step_data_gathering' => $paper->step_data_gathering,
                'step_rating' => $paper->step_rating,
                'grade' => $paper->grade,
                'step_final_manuscript' => $paper->step_final_manuscript,
                'step_final_defense' => $paper->step_final_defense,
                'final_defense_schedule' => $paper->final_defense_schedule?->toISOString(),
                'step_hard_bound' => $paper->step_hard_bound,
                'submission_date' => $paper->submission_date?->toDateString(),
                'created_at' => $paper->created_at->toISOString(),
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
                'files' => $paper->files->map(fn ($f) => [
                    'id' => $f->id,
                    'file_name' => $f->file_name,
                    'file_path' => $f->file_path,
                    'file_type' => $f->file_type,
                    'file_size' => $f->file_size,
                    'disk' => $f->disk,
                    'url' => $f->url,
                ])->values()->all(),
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
            'steps' => ResearchPaper::STEPS,
            'sdgs' => Sdg::where('is_active', true)->orderBy('number')->get(['id', 'number', 'name', 'color']),
            'agendas' => Agenda::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'comments' => $paper->comments->map(fn (Comment $comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                ] : null,
                'created_at' => $comment->created_at?->toISOString(),
            ])->values(),
            'panelDefenses' => $paper->panelDefenses->map(fn ($pd) => [
                'id' => $pd->id,
                'defense_type' => $pd->defense_type,
                'defense_type_label' => $pd->defense_type_label,
                'panel_members' => $pd->panel_members,
                'schedule' => $pd->schedule?->toDateTimeString(),
                'notes' => $pd->notes,
                'created_by' => $pd->createdBy ? ['id' => $pd->createdBy->id, 'name' => $pd->createdBy->name] : null,
                'created_at' => $pd->created_at->toISOString(),
            ])->values(),
        ]);
    }

    public function updateStep(Request $request, SchoolClass $class, ResearchPaper $paper): RedirectResponse
    {
        $this->ensurePaperBelongsToClass($class, $paper);
        $this->ensureFacultyCanAccessPaper($request, $class, $paper);

        $validated = $request->validate([
            'step' => ['required', Rule::in(['outline_defense', 'data_gathering', 'rating', 'final_defense'])],
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
                    $updateData['current_step'] = 'data_gathering';
                    $updateData['step_data_gathering'] = 'pending';
                }
                break;

            case 'data_gathering':
                $oldStatus = $paper->step_data_gathering;
                $updateData['step_data_gathering'] = $status;

                if ($status === 'completed') {
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

        ResearchStatusUpdated::dispatch(
            $paper->fresh(),
            $step,
            ResearchPaper::STEP_LABELS[$step] ?? ucfirst(str_replace('_', ' ', $step)),
            $status,
            $validated['notes'] ?? null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status updated.']);

        return back();
    }

    public function approve(Request $request, SchoolClass $class, ResearchPaper $paper): RedirectResponse
    {
        $this->ensurePaperBelongsToClass($class, $paper);
        $this->ensureFacultyCanAccessPaper($request, $class, $paper);

        if ($paper->current_step !== 'ric_review') {
            abort(422, 'Paper is not in RIC review step.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $oldStatus = $paper->step_ric_review;

        $paper->update([
            'step_ric_review' => 'approved',
            'current_step' => 'outline_defense',
            'step_outline_defense' => 'pending',
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

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Title proposal approved for outline defense.']);

        return back();
    }

    public function storeComment(Request $request, SchoolClass $class, ResearchPaper $paper): RedirectResponse
    {
        $this->ensurePaperBelongsToClass($class, $paper);
        $this->ensureFacultyCanAccessPaper($request, $class, $paper);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $paper->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Comment added.']);

        return back();
    }

    private function ensureFacultyOwnsClass(Request $request, SchoolClass $class): void
    {
        if ($class->faculty_id !== $request->user()->id) {
            abort(403);
        }
    }

    private function ensureFacultyCanAccessPaper(Request $request, SchoolClass $class, ResearchPaper $paper): void
    {
        $facultyUserId = $request->user()->id;

        $isClassFaculty = $class->faculty_id === $facultyUserId;
        $isAssignedFaculty = $paper->adviser_id === $facultyUserId
            || $paper->statistician_id === $facultyUserId;

        if (! $isClassFaculty && ! $isAssignedFaculty) {
            abort(403);
        }
    }

    private function ensurePaperBelongsToClass(SchoolClass $class, ResearchPaper $paper): void
    {
        $isMember = DB::table('school_class_members')
            ->where('school_class_id', $class->id)
            ->where('student_id', $paper->user_id)
            ->exists();

        if (! $isMember) {
            abort(404);
        }
    }
}
