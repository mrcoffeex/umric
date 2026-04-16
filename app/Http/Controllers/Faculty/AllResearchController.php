<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\Sdg;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AllResearchController extends Controller
{
    public function index(Request $request): Response
    {
        $classIds = SchoolClass::query()
            ->where('faculty_id', $request->user()->id)
            ->pluck('id');

        // Get student-to-class mapping via school_class_members pivot
        $studentClassMap = \DB::table('school_class_members')
            ->whereIn('school_class_id', $classIds)
            ->get()
            ->groupBy('student_id');

        $studentIds = $studentClassMap->keys()->all();

        $classes = SchoolClass::query()
            ->whereIn('id', $classIds)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $papers = ResearchPaper::query()
            ->whereIn('user_id', $studentIds)
            ->with(['user'])
            ->latest()
            ->get()
            ->map(function (ResearchPaper $paper) use ($studentClassMap, $classes) {
                // Find the class this student belongs to
                $memberships = $studentClassMap->get($paper->user_id);
                $classInfo = null;
                if ($memberships && $memberships->isNotEmpty()) {
                    $classModel = $classes->get($memberships->first()->school_class_id);
                    if ($classModel) {
                        $classInfo = [
                            'id' => $classModel->id,
                            'name' => $classModel->name,
                            'section' => $classModel->section,
                            'class_code' => $classModel->class_code,
                        ];
                    }
                }

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
                    'school_class' => $classInfo,
                ];
            });

        $stepCounts = [];
        foreach (ResearchPaper::STEPS as $step) {
            $stepCounts[$step] = $papers->where('current_step', $step)->count();
        }

        $classesForView = $classes->values()->map(fn (SchoolClass $class) => [
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
}
