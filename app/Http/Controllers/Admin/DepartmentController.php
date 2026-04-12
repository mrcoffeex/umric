<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        $departments = Department::withCount('programs')
            ->with(['programs' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/Departments/Index', [
            'departments' => $departments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:departments,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        Department::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Department created.']);

        return back();
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:departments,code,'.$department->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $department->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Department updated.']);

        return back();
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Department deleted.']);

        return back();
    }
}
