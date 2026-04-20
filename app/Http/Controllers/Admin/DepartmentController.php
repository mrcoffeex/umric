<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LogsAdminActions;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    use LogsAdminActions;

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

        $department = Department::create($validated);

        $this->logAdminAction()
            ->on($department)
            ->withProperties(['name' => $department->name, 'code' => $department->code])
            ->withDescription("Created department {$department->code}")
            ->action('created');

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

        $oldCode = $department->code;
        $department->update($validated);

        $this->logAdminAction()
            ->on($department)
            ->withProperties(['old_code' => $oldCode, 'new_code' => $department->code])
            ->withDescription("Updated department {$department->code}")
            ->action('updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Department updated.']);

        return back();
    }

    public function destroy(Department $department): RedirectResponse
    {
        $deptCode = $department->code;

        $this->logAdminAction()
            ->on($department)
            ->withProperties(['code' => $deptCode])
            ->withDescription("Deleted department {$deptCode}")
            ->action('deleted');

        $department->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Department deleted.']);

        return back();
    }
}
