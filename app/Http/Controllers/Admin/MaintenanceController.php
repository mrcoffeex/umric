<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMaintenanceRequest;
use App\Services\MaintenanceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function __construct(
        private MaintenanceService $maintenance,
    ) {}

    public function index(): Response
    {
        $record = $this->maintenance->record();

        return Inertia::render('admin/Maintenance/Index', [
            'maintenance' => [
                'enabled' => $record->maintenance_mode,
                'message' => $record->maintenance_message,
            ],
        ]);
    }

    public function update(UpdateMaintenanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->maintenance->update([
            'maintenance_mode' => (bool) $validated['enabled'],
            'maintenance_message' => $validated['message'] ?? null,
        ]);

        $enabled = (bool) $validated['enabled'];

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $enabled
                ? 'Maintenance mode enabled. Non-admin users are blocked.'
                : 'Maintenance mode disabled. The system is available again.',
        ]);

        return redirect()->route('admin.maintenance.index');
    }
}
