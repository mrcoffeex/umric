<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name'          => ['required', 'string', 'max:255'],
            'code'          => ['required', 'string', 'max:20', 'unique:programs,code'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'is_active'     => ['boolean'],
        ]);

        Program::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Program created.']);

        return back();
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:20', 'unique:programs,code,'.$program->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['boolean'],
        ]);

        $program->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Program updated.']);

        return back();
    }

    public function destroy(Program $program): RedirectResponse
    {
        $program->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Program deleted.']);

        return back();
    }
}
