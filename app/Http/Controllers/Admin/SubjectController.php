<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(): Response
    {
        $subjects = Subject::with('program')
            ->orderBy('name')
            ->get();

        $programs = Program::orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('admin/Subjects/Index', [
            'subjects' => $subjects,
            'programs' => $programs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        Subject::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subject created.']);

        return back();
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('subjects', 'code')->ignore($subject)],
            'program_id' => ['nullable', 'exists:programs,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $subject->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subject updated.']);

        return back();
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subject deleted.']);

        return back();
    }
}
