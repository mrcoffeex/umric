<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolClassController extends Controller
{
    public function index(): Response
    {
        $classes = SchoolClass::with(['faculty', 'subjects'])
            ->orderBy('section')
            ->get()
            ->map(fn (SchoolClass $class) => [
                'id' => $class->id,
                'faculty_id' => $class->faculty_id,
                'subject_id' => $class->subjects->first()?->id,
                'name' => $class->name,
                'class_code' => $class->class_code,
                'school_year' => $class->school_year,
                'semester' => $class->semester,
                'term' => $class->term,
                'section' => $class->section,
                'description' => $class->description,
                'is_active' => $class->is_active,
                'faculty' => $class->faculty ? [
                    'id' => $class->faculty->id,
                    'name' => $class->faculty->name,
                ] : null,
                'subjects' => $class->subjects->map(fn ($s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                ]),
            ]);

        $facultyUsers = User::whereHas('profile', fn ($q) => $q->where('role', 'faculty'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjects = Subject::with('program')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'program_id']);

        return Inertia::render('admin/Classes/Index', [
            'classes' => $classes,
            'facultyUsers' => $facultyUsers,
            'subjects' => $subjects,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'faculty_id' => ['nullable', 'exists:users,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'name' => ['required', 'string', 'max:255'],
            'class_code' => ['nullable', 'string', 'max:30'],
            'school_year' => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['nullable', 'integer', 'between:1,2'],
            'term' => ['nullable', 'string', 'max:20'],
            'section' => ['required', 'string', 'max:5'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $subjectId = $validated['subject_id'];
        unset($validated['subject_id']);

        $class = SchoolClass::create($validated);
        $class->subjects()->sync([$subjectId]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Class created.']);

        return back();
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        $validated = $request->validate([
            'faculty_id' => ['nullable', 'exists:users,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'name' => ['required', 'string', 'max:255'],
            'class_code' => ['nullable', 'string', 'max:30'],
            'school_year' => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['nullable', 'integer', 'between:1,2'],
            'term' => ['nullable', 'string', 'max:20'],
            'section' => ['required', 'string', 'max:5'],
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

    public function destroy(SchoolClass $class): RedirectResponse
    {
        $class->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Class deleted.']);

        return back();
    }

    public function show(SchoolClass $class): Response
    {
        $class->load(['faculty', 'subjects.program', 'members.profile']);

        $students = $class->members->map(fn ($student) => [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'avatar_url' => $student->profile?->avatarUrl(),
            'joined_at' => $student->pivot->joined_at,
        ]);

        return Inertia::render('admin/Classes/Show', [
            'class' => [
                'id' => $class->id,
                'name' => $class->name,
                'class_code' => $class->class_code,
                'section' => $class->section,
                'school_year' => $class->school_year,
                'semester' => $class->semester,
                'term' => $class->term,
                'description' => $class->description,
                'is_active' => $class->is_active,
                'join_code' => $class->join_code,
                'faculty' => $class->faculty ? ['id' => $class->faculty->id, 'name' => $class->faculty->name] : null,
                'subjects' => $class->subjects->map(fn ($s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'program' => $s->program ? ['name' => $s->program->name, 'code' => $s->program->code] : null,
                ]),
            ],
            'students' => $students,
        ]);
    }

    public function generateJoinCode(SchoolClass $class): RedirectResponse
    {
        $class->generateAndSetJoinCode();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Join link generated.']);

        return back();
    }

    public function revokeJoinCode(SchoolClass $class): RedirectResponse
    {
        $class->update(['join_code' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Join link revoked.']);

        return back();
    }

    public function removeStudent(SchoolClass $class, int $studentId): RedirectResponse
    {
        $class->members()->detach($studentId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Student removed from class.']);

        return back();
    }
}
