<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Comment;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\Sdg;
use App\Models\TrackingRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AllResearchController extends Controller
{
    public function index(Request $request): Response
    {
        $facultyUserId = (int) $request->user()->id;

        $classIds = SchoolClass::query()
            ->where('faculty_id', $facultyUserId)
            ->pluck('id');

        $studentIds = DB::table('school_class_members')
            ->whereIn('school_class_id', $classIds)
            ->pluck('student_id')
            ->all();

        $facultyName = $request->user()->name;

        $papers = ResearchPaper::query()
            ->where(function ($query) use ($studentIds, $facultyUserId, $facultyName) {
                if ($studentIds !== []) {
                    $query->whereIn('user_id', $studentIds)
                        ->orWhere('adviser_id', $facultyUserId)
                        ->orWhere('statistician_id', $facultyUserId)
                        ->orWhereHas('panelDefenses', fn ($q) => $q->whereRaw(
                            'panel_members::jsonb @> ?::jsonb',
                            [json_encode([$facultyName])]
                        ));

                    return;
                }

                $query->where('adviser_id', $facultyUserId)
                    ->orWhere('statistician_id', $facultyUserId)
                    ->orWhereHas('panelDefenses', fn ($q) => $q->whereRaw(
                        'panel_members::jsonb @> ?::jsonb',
                        [json_encode([$facultyName])]
                    ));
            })
            ->with(['user'])
            ->latest()
            ->get();

        $studentIdsFromPapers = $papers->pluck('user_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $studentClassMap = collect();
        $classesById = collect();

        if ($studentIdsFromPapers !== []) {
            $studentClassMap = DB::table('school_class_members')
                ->whereIn('student_id', $studentIdsFromPapers)
                ->orderByDesc('joined_at')
                ->orderByDesc('id')
                ->get(['student_id', 'school_class_id'])
                ->unique('student_id')
                ->keyBy('student_id');

            $classesById = SchoolClass::query()
                ->whereIn('id', $studentClassMap->pluck('school_class_id')->filter()->unique()->values())
                ->get(['id', 'name', 'section', 'class_code'])
                ->keyBy('id');
        }

        $papers = $papers->map(function (ResearchPaper $paper) use ($classesById, $studentClassMap) {
            $classMembership = $studentClassMap->get((int) $paper->user_id);
            $studentClass = $classMembership
                ? $classesById->get((int) $classMembership->school_class_id)
                : null;

            return [
                'id' => $paper->id,
                'tracking_id' => $paper->tracking_id,
                'title' => $paper->title,
                'status' => $paper->status,
                'current_step' => $paper->current_step,
                'step_label' => $paper->step_label,
                'grade' => $paper->grade,
                'sdg_ids' => $paper->sdg_ids ?? [],
                'agenda_ids' => $paper->agenda_ids ?? [],
                'outline_defense_schedule' => $paper->outline_defense_schedule?->toISOString(),
                'final_defense_schedule' => $paper->final_defense_schedule?->toISOString(),
                'student' => $paper->user ? [
                    'id' => $paper->user->id,
                    'name' => $paper->user->name,
                ] : null,
                'school_class' => $studentClass ? [
                    'id' => $studentClass->id,
                    'name' => $studentClass->name,
                    'section' => $studentClass->section,
                    'class_code' => $studentClass->class_code,
                ] : null,
            ];
        });

        $classIdsFromPapers = $papers->pluck('school_class.id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $classIdsForFilter = $classIds->map(fn ($id) => (int) $id)
            ->merge($classIdsFromPapers)
            ->unique()
            ->values();

        $classes = SchoolClass::query()
            ->whereIn('id', $classIdsForFilter)
            ->orderBy('name')
            ->get();

        $stepCounts = [];
        foreach (ResearchPaper::STEPS as $step) {
            $stepCounts[$step] = $papers->where('current_step', $step)->count();
        }

        $classesForView = $classes->map(fn (SchoolClass $class) => [
            'id' => $class->id,
            'name' => $class->name,
            'section' => $class->section,
            'class_code' => $class->class_code,
        ]);

        $sdgs = Sdg::query()
            ->where('is_active', true)
            ->orderBy('number')
            ->get()
            ->map(fn (Sdg $sdg) => [
                'id' => $sdg->id,
                'number' => $sdg->number,
                'name' => $sdg->name,
                'code' => $sdg->code,
                'color' => $sdg->color,
            ]);

        $agendas = Agenda::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Agenda $agenda) => [
                'id' => $agenda->id,
                'name' => $agenda->name,
                'code' => $agenda->code,
            ]);

        return Inertia::render('faculty/Research/AllIndex', [
            'papers' => $papers,
            'classes' => $classesForView,
            'sdgs' => $sdgs,
            'agendas' => $agendas,
            'stepCounts' => $stepCounts,
            'stepLabels' => ResearchPaper::STEP_LABELS,
        ]);
    }

    public function show(Request $request, ResearchPaper $paper): Response
    {
        $facultyUserId = (int) $request->user()->id;
        $facultyName = $request->user()->name;

        $isAdviserOrStatistician = (int) $paper->adviser_id === $facultyUserId
            || (int) $paper->statistician_id === $facultyUserId;

        $isPanelMember = $paper->panelDefenses()
            ->whereRaw('panel_members::jsonb @> ?::jsonb', [json_encode([$facultyName])])
            ->exists();

        if (! $isAdviserOrStatistician && ! $isPanelMember) {
            $isClassFaculty = false;

            if ($paper->school_class_id) {
                $isClassFaculty = DB::table('school_classes')
                    ->where('id', $paper->school_class_id)
                    ->where('faculty_id', $facultyUserId)
                    ->exists();
            }

            if (! $isClassFaculty) {
                abort(403);
            }
        }

        $paper->load(['user.profile', 'schoolClass.subjects.program', 'adviser', 'statistician', 'trackingRecords.updatedBy', 'comments.user', 'panelDefenses.createdBy']);

        return Inertia::render('faculty/Research/Show', [
            'schoolClass' => $paper->schoolClass ? [
                'id' => $paper->schoolClass->id,
                'name' => $paper->schoolClass->name,
                'section' => $paper->schoolClass->section,
            ] : null,
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
}
