<?php

namespace App\Http\Middleware;

use App\Models\DocumentTransmission;
use App\Services\BrandingService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $branding = app(BrandingService::class)->inertiaProps();

        return [
            ...parent::share($request),
            'name' => $branding['name'],
            'branding' => [
                'tagline' => $branding['tagline'],
                'logoUrl' => $branding['logoUrl'],
            ],
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'role' => $user->role(),
                    'avatar_url' => $user->profile?->avatarUrl(),
                    'hasAccountEsignature' => (bool) $user->hasAccountEsignature(),
                    'accountEsignatureUrl' => $user->accountEsignaturePublicUrl(),
                ]) : null,
            ],
            'documentHandoffs' => [
                'incomingPending' => $user
                    ? DocumentTransmission::pendingCountForUser($user->id, 'incoming')
                    : 0,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
