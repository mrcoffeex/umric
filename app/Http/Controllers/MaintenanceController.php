<?php

namespace App\Http\Controllers;

use App\Services\MaintenanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function __construct(
        private MaintenanceService $maintenance,
    ) {}

    public function show(Request $request): Response|RedirectResponse
    {
        if (! $this->maintenance->enabled()) {
            return redirect()->route('dashboard');
        }

        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.maintenance.index');
        }

        return Inertia::render('Maintenance', [
            'message' => $this->maintenance->message(),
        ]);
    }
}
