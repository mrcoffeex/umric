<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LogsAdminActions;
use App\Mail\ResearchStatusUpdated;
use App\Models\Agenda;
use App\Models\PanelDefense;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\Sdg;
use App\Models\TrackingRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ResearchController extends Controller
{
    use LogsAdminActions;

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

        if ($request->filled('sdg')) {
            $query->whereJsonContains('sdg_ids', $request->sdg);
        }

        if ($request->filled('agenda')) {
            $query->whereJsonContains('agenda_ids', $request->agenda);
        }

        if ($request->filled('class')) {
            $selectedClassId = $request->class;
            $query->whereIn('user_id', function ($subQuery) use ($selectedClassId) {
                $subQuery->select('student_id')
                    ->from('school_class_members')
                    ->where('school_class_id', $selectedClassId);
            });
        }

        $papers = $query->latest()->paginate(20)->withQueryString();

        $studentIds = $papers->getCollection()->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $studentClassMap = collect();
        $classesById = collect();

        if ($studentIds !== []) {
            $studentClassMap = DB::table('school_class_members')
                ->whereIn('student_id', $studentIds)
                ->orderByDesc('joined_at')
                ->orderByDesc('id')
                ->get(['student_id', 'school_class_id'])
                ->unique('student_id')
                ->keyBy('student_id');

            $classesById = SchoolClass::query()
                ->whereIn('id', $studentClassMap->pluck('school_class_id')->filter()->unique()->values())
                ->get(['id', 'name', 'section'])
                ->keyBy('id');
        }

        $papers->setCollection(
            $papers->getCollection()->map(function (ResearchPaper $paper) use ($classesById, $studentClassMap) {
                $classMembership = $studentClassMap->get($paper->user_id);
                $studentClass = $classMembership
                    ? $classesById->get($classMembership->school_class_id)
                    : null;

                return [
                    'id' => $paper->id,
                    'tracking_id' => $paper->tracking_id,
                    'title' => $paper->title,
                    'current_step' => $paper->current_step,
                    'step_label' => $paper->step_label,
                    'created_at' => $paper->created_at->toISOString(),
                    'sdg_ids' => $paper->sdg_ids,
                    'agenda_ids' => $paper->agenda_ids,
                    'user' => $paper->user ? [
                        'id' => $paper->user->id,
                        'name' => $paper->user->name,
                    ] : null,
                    'school_class' => $studentClass ? [
                        'id' => $studentClass->id,
                        'name' => $studentClass->name,
                        'section' => $studentClass->section,
                    ] : null,
                ];
            })
        );

        $stepCounts = [];
        foreach (ResearchPaper::STEPS as $step) {
            $stepCounts[$step] = ResearchPaper::where('current_step', $step)->count();
        }

        $facultyUsers = User::whereHas('profile', fn ($q) => $q->where('role', 'faculty'))
            ->orderBy('name')->get(['id', 'name']);

        $staffUsers = User::whereHas('profile', fn ($q) => $q->whereIn('role', ['staff', 'admin']))
            ->orderBy('name')->get(['id', 'name']);

        $sdgs = Sdg::where('is_active', true)->orderBy('number')->get(['id', 'number', 'name', 'color']);
        $agendas = Agenda::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $classes = SchoolClass::where('is_active', true)->orderBy('name')->get(['id', 'name', 'section']);

        return Inertia::render('admin/Research/Index', [
            'papers' => $papers,
            'stepCounts' => $stepCounts,
            'facultyUsers' => $facultyUsers,
            'staffUsers' => $staffUsers,
            'filters' => $request->only(['step', 'search', 'sdg', 'agenda', 'class']),
            'stepLabels' => ResearchPaper::STEP_LABELS,
            'sdgs' => $sdgs,
            'agendas' => $agendas,
            'classes' => $classes,
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
            'comments.user',
            'panelDefenses.createdBy',
        ]);

        $facultyUsers = User::whereHas('profile', fn ($q) => $q->where('role', 'faculty'))
            ->orderBy('name')->get(['id', 'name']);

        $staffUsers = User::whereHas('profile', fn ($q) => $q->whereIn('role', ['staff', 'admin']))
            ->orderBy('name')->get(['id', 'name']);

        $sdgs = Sdg::where('is_active', true)->orderBy('number')->get(['id', 'number', 'name', 'color']);
        $agendas = Agenda::where('is_active', true)->orderBy('name')->get(['id', 'name']);

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
                'user_id' => $paper->user_id,
                'student' => [
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
            ],
            'trackingRecords' => $paper->trackingRecords->map(fn ($r) => [
                'id' => $r->id,
                'step' => $r->step,
                'action' => $r->action,
                'status' => $r->status,
                'old_status' => $r->old_status,
                'notes' => $r->notes,
                'metadata' => $r->metadata,
                'updated_by' => $r->updatedBy ? ['id' => $r->updatedBy->id, 'name' => $r->updatedBy->name] : null,
                'created_at' => $r->created_at->toISOString(),
            ]),
            'facultyUsers' => $facultyUsers,
            'staffUsers' => $staffUsers,
            'sdgs' => $sdgs,
            'agendas' => $agendas,
            'stepLabels' => ResearchPaper::STEP_LABELS,
            'steps' => ResearchPaper::STEPS,
            'comments' => $paper->comments->map(fn ($c) => [
                'id' => $c->id,
                'body' => $c->body,
                'user' => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name] : null,
                'created_at' => $c->created_at->toISOString(),
            ]),
            'panelDefenses' => $paper->panelDefenses->map(fn ($pd) => [
                'id' => $pd->id,
                'defense_type' => $pd->defense_type,
                'defense_type_label' => $pd->defense_type_label,
                'panel_members' => $pd->panel_members,
                'schedule' => $pd->schedule?->toDateTimeString(),
                'notes' => $pd->notes,
                'created_by' => $pd->createdBy ? ['id' => $pd->createdBy->id, 'name' => $pd->createdBy->name] : null,
                'created_at' => $pd->created_at->toISOString(),
            ]),
        ]);
    }

    public function storeComment(Request $request, ResearchPaper $paper): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment = $paper->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        // Log the comment addition
        $this->logAdminAction()
            ->on($paper)
            ->withProperties(['comment_id' => $comment->id, 'comment_length' => strlen($validated['body'])])
            ->withDescription("Added comment to paper {$paper->tracking_id}")
            ->action('updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Comment added.']);

        return back();
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

        // Log the assignment action
        $this->logAdminAction()
            ->on($paper)
            ->withProperties([
                'adviser_id' => $validated['adviser_id'],
                'adviser_name' => $paper->adviser?->name,
                'statistician_id' => $validated['statistician_id'],
                'statistician_name' => $paper->statistician?->name,
            ])
            ->withDescription("Assigned advisers to paper {$paper->tracking_id}")
            ->action('assigned');

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

        // Log the step update action
        $this->logAdminAction()
            ->on($paper)
            ->withProperties([
                'step' => $step,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'notes' => $notes,
            ])
            ->withDescription("Updated {$step} status to {$status} for paper {$paper->tracking_id}")
            ->action('updated');

        ResearchStatusUpdated::dispatch(
            $paper->fresh(),
            $step,
            ResearchPaper::STEP_LABELS[$step] ?? ucfirst(str_replace('_', ' ', $step)),
            $status,
            $notes,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status updated.']);

        return back();
    }

    public function receive(Request $request, ResearchPaper $paper): RedirectResponse
    {
        TrackingRecord::log(
            $paper->id,
            'document_received',
            'Document Received',
            'received',
            null,
            $request->user()->id,
            'Received by '.$request->user()->name,
            ['received_at' => now()->toISOString()],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Document receipt recorded for '.$request->user()->name.'.']);

        return redirect()->route('admin.research.show', $paper);
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

    public function storePanelDefense(Request $request, ResearchPaper $paper): RedirectResponse
    {
        $validated = $request->validate([
            'defense_type' => ['required', 'string', 'in:title,outline,final'],
            'panel_members' => ['required', 'array', 'min:1'],
            'panel_members.*' => ['required', 'string', 'max:255'],
            'schedule' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $paper->panelDefenses()->create([
            'defense_type' => $validated['defense_type'],
            'panel_members' => $validated['panel_members'],
            'schedule' => $validated['schedule'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        // Sync schedule + panel info to the corresponding step
        $defenseType = $validated['defense_type'];
        if (in_array($defenseType, ['outline', 'final']) && ! empty($validated['schedule'])) {
            $stepKey = $defenseType === 'outline' ? 'outline_defense' : 'final_defense';
            $scheduleField = $defenseType === 'outline' ? 'outline_defense_schedule' : 'final_defense_schedule';
            $currentStatus = $defenseType === 'outline'
                ? ($paper->step_outline_defense ?? 'pending')
                : ($paper->step_final_defense ?? 'pending');

            $paper->update([$scheduleField => $validated['schedule']]);

            $panelNotes = 'Panel: '.implode(', ', $validated['panel_members']);
            if (! empty($validated['notes'])) {
                $panelNotes .= "\nRemarks: ".$validated['notes'];
            }

            TrackingRecord::log(
                $paper->id,
                $stepKey,
                'Panel management: schedule set',
                $currentStatus,
                null,
                $request->user()->id,
                $panelNotes,
                ['schedule' => $validated['schedule']],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Panel defense record added.']);

        return back();
    }

    public function destroyPanelDefense(Request $request, ResearchPaper $paper, PanelDefense $panelDefense): RedirectResponse
    {
        abort_unless($panelDefense->research_paper_id === $paper->id, 404);

        $panelDefense->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Panel defense record removed.']);

        return back();
    }
}
