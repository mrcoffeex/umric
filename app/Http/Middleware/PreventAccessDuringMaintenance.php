<?php

namespace App\Http\Middleware;

use App\Services\MaintenanceService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PreventAccessDuringMaintenance
{
    public function __construct(
        private MaintenanceService $maintenance,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->maintenance->enabled()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user === null || $user->isAdmin()) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        Inertia::flash('toast', [
            'type' => 'warning',
            'message' => $this->maintenance->message(),
        ]);

        return redirect()->route('maintenance');
    }

    private function shouldBypass(Request $request): bool
    {
        return $request->routeIs(
            'maintenance',
            'logout',
            'login',
            'register',
            'password.*',
            'verification.*',
            'two-factor.*',
            'home',
        );
    }
}
