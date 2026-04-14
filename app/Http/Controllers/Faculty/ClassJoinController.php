<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ClassJoinController extends Controller
{
    public function show(string $code): Response
    {
        $class = SchoolClass::with(['faculty', 'subjects.program'])
            ->where('join_code', $code)
            ->first();

        if (! $class) {
            abort(404, 'Class not found.');
        }

        return Inertia::render('faculty/Classes/Join', [
            'schoolClass' => [
                'id' => $class->id,
                'name' => $class->name,
                'section' => $class->section,
                'school_year' => $class->school_year,
                'semester' => $class->semester,
                'description' => $class->description,
                'is_active' => $class->is_active,
                'faculty_name' => $class->faculty?->name,
                'subjects' => $class->subjects->map(fn ($s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'program' => $s->program ? ['name' => $s->program->name, 'code' => $s->program->code] : null,
                ])->values()->toArray(),
                'members_count' => $class->members()->count(),
            ],
            'join_code' => $code,
            'already_joined' => $class->members()->where('student_id', Auth::id())->exists(),
        ]);
    }

    public function store(Request $request, string $code): RedirectResponse
    {
        $class = SchoolClass::where('join_code', $code)->first();

        if (! $class) {
            abort(404);
        }

        if (! $class->is_active) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This class is no longer accepting new members.']);

            return back();
        }

        if ($class->members()->where('student_id', $request->user()->id)->exists()) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'You have already joined this class.']);

            return to_route('dashboard');
        }

        $class->members()->attach($request->user()->id, ['joined_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "You joined \"{$class->name}\"."]);

        return to_route('student.home');
    }
}
