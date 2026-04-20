<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LogsAdminActions;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    use LogsAdminActions;

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
            'year_level' => ['nullable', 'integer', 'between:1,5'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $subject = Subject::create($validated);

        $this->logAdminAction()
            ->on($subject)
            ->withProperties(['name' => $subject->name, 'code' => $subject->code])
            ->withDescription("Created subject {$subject->code}")
            ->action('created');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subject created.']);

        return back();
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('subjects', 'code')->ignore($subject)],
            'program_id' => ['nullable', 'exists:programs,id'],
            'year_level' => ['nullable', 'integer', 'between:1,5'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $oldCode = $subject->code;
        $subject->update($validated);

        $this->logAdminAction()
            ->on($subject)
            ->withProperties(['old_code' => $oldCode, 'new_code' => $subject->code])
            ->withDescription("Updated subject {$subject->code}")
            ->action('updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subject updated.']);

        return back();
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subjectCode = $subject->code;

        $this->logAdminAction()
            ->on($subject)
            ->withProperties(['code' => $subjectCode])
            ->withDescription("Deleted subject {$subjectCode}")
            ->action('deleted');

        $subject->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subject deleted.']);

        return back();
    }
}
