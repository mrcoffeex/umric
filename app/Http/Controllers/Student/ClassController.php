<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $classes = SchoolClass::query()
            ->whereHas('members', fn ($query) => $query->where('student_id', $request->user()->id))
            ->with(['subjects.program', 'faculty', 'researchPapers' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->get();

        return Inertia::render('student/Classes/Index', [
            'classes' => $classes,
        ]);
    }

    public function show(Request $request, SchoolClass $class): Response
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        // Ensure the student is a member of this class
        $isMember = $class->members()->where('student_id', $request->user()->id)->exists();
        if (! $isMember) {
            abort(403);
        }

        $class->load(['faculty', 'subjects.program', 'members.profile']);

        $students = $class->members
            ->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->name,
                'avatar_url' => $student->profile?->avatarUrl(),
                'joined_at' => $student->pivot->joined_at,
            ]);

        $researchPapersCount = ResearchPaper::where('school_class_id', $class->id)->count();

        return Inertia::render('student/Classes/Show', [
            'schoolClass' => [
                'id' => $class->id,
                'name' => $class->name,
                'class_code' => $class->class_code,
                'section' => $class->section,
                'school_year' => $class->school_year,
                'semester' => $class->semester,
                'description' => $class->description,
                'is_active' => $class->is_active,
                'faculty' => $class->faculty ? ['id' => $class->faculty->id, 'name' => $class->faculty->name] : null,
                'subjects' => $class->subjects->map(fn ($s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'program' => $s->program ? ['name' => $s->program->name, 'code' => $s->program->code] : null,
                ]),
            ],
            'students' => $students,
            'researchPapersCount' => $researchPapersCount,
        ]);
    }
}
