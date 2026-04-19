<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SchoolClassController extends Controller
{
    public function index(Request $request): Response
    {
        $classes = SchoolClass::where('faculty_id', $request->user()->id)
            ->with('faculty', 'subjects.program')
            ->withCount('members')
            ->latest()
            ->get()
            ->map(fn (SchoolClass $class) => [
                'id' => $class->id,
                'name' => $class->name,
                'class_code' => $class->class_code,
                'section' => $class->section,
                'school_year' => $class->school_year,
                'semester' => $class->semester,
                'description' => $class->description,
                'is_active' => $class->is_active,
                'join_code' => $class->join_code,
                'members_count' => $class->members_count,
                'subjects' => $class->subjects->map(fn ($s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'program' => $s->program ? ['id' => $s->program->id, 'name' => $s->program->name, 'code' => $s->program->code] : null,
                ]),
            ]);

        $subjects = Subject::with('program')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'program_id']);

        return Inertia::render('faculty/Classes/Index', [
            'classes' => $classes,
            'subjects' => $subjects,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'class_code' => ['nullable', 'string', 'max:30'],
            'school_year' => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['nullable', 'integer', 'between:1,2'],
            'term' => ['nullable', 'string', 'max:20'],
            'section' => ['nullable', 'string', 'max:5'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $subjectId = $validated['subject_id'];
        unset($validated['subject_id']);

        $class = SchoolClass::create([
            ...$validated,
            'faculty_id' => $request->user()->id,
        ]);

        $class->subjects()->sync([$subjectId]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Class created.']);

        return back();
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        if ($class->faculty_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'class_code' => ['nullable', 'string', 'max:30'],
            'school_year' => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['nullable', 'integer', 'between:1,2'],
            'term' => ['nullable', 'string', 'max:20'],
            'section' => ['nullable', 'string', 'max:5'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $subjectId = $validated['subject_id'];
        unset($validated['subject_id']);

        $class->update($validated);
        $class->subjects()->sync([$subjectId]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Class updated.']);

        return back();
    }

    public function destroy(Request $request, SchoolClass $class): RedirectResponse
    {
        if ($class->faculty_id !== $request->user()->id) {
            abort(403);
        }

        $class->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Class deleted.']);

        return to_route('faculty.classes.index');
    }

    public function show(Request $request, SchoolClass $class): Response
    {
        if ($class->faculty_id !== $request->user()->id) {
            abort(403);
        }

        $students = $class->members()
            ->select('users.id', 'users.name', 'users.email')
            ->get()
            ->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'avatar_url' => $student->profile?->avatarUrl(),
                'joined_at' => $student->pivot->joined_at,
            ]);

        $studentIds = DB::table('school_class_members')
            ->where('school_class_id', $class->id)
            ->pluck('student_id');

        $researchPapersCount = ResearchPaper::whereIn('user_id', $studentIds)->count();

        $class->load('subjects.program');

        return Inertia::render('faculty/Classes/Show', [
            'schoolClass' => [
                'id' => $class->id,
                'name' => $class->name,
                'class_code' => $class->class_code,
                'section' => $class->section,
                'school_year' => $class->school_year,
                'semester' => $class->semester,
                'description' => $class->description,
                'is_active' => $class->is_active,
                'join_code' => $class->join_code,
                'subjects' => $class->subjects->map(fn (Subject $subject) => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'program' => $subject->program ? ['name' => $subject->program->name, 'code' => $subject->program->code] : null,
                ]),
            ],
            'students' => $students,
            'researchPapersCount' => $researchPapersCount,
        ]);
    }

    public function generateJoinCode(Request $request, SchoolClass $class): RedirectResponse
    {
        if ($class->faculty_id !== $request->user()->id) {
            abort(403);
        }

        $class->generateAndSetJoinCode();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Join link generated.']);

        return back();
    }

    public function revokeJoinCode(Request $request, SchoolClass $class): RedirectResponse
    {
        if ($class->faculty_id !== $request->user()->id) {
            abort(403);
        }

        $class->update(['join_code' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Join link revoked.']);

        return back();
    }

    public function removeStudent(Request $request, SchoolClass $class, int $studentId): RedirectResponse
    {
        if ($class->faculty_id !== $request->user()->id) {
            abort(403);
        }

        $class->members()->detach($studentId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Student removed from class.']);

        return back();
    }
}
