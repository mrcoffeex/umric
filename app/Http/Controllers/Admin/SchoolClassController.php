<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolClassController extends Controller
{
    public function index(): Response
    {
        $classes = SchoolClass::with('program')
            ->orderBy('year_level')
            ->orderBy('section')
            ->get();

        $programs = Program::orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('admin/Classes/Index', [
            'classes' => $classes,
            'programs' => $programs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'class_code' => ['nullable', 'string', 'max:30'],
            'school_year' => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['nullable', 'integer', 'between:1,2'],
            'term' => ['nullable', 'string', 'max:20'],
            'year_level' => ['required', 'integer', 'between:1,5'],
            'section' => ['required', 'string', 'max:5'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        SchoolClass::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Class created.']);

        return back();
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'class_code' => ['nullable', 'string', 'max:30'],
            'school_year' => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['nullable', 'integer', 'between:1,2'],
            'term' => ['nullable', 'string', 'max:20'],
            'year_level' => ['required', 'integer', 'between:1,5'],
            'section' => ['required', 'string', 'max:5'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $class->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Class updated.']);

        return back();
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        $class->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Class deleted.']);

        return back();
    }
}
