<?php

namespace App\Services;

use App\Models\AppBranding;
use Illuminate\Support\Facades\Storage;

class BrandingService
{
    public function record(): AppBranding
    {
        $first = AppBranding::query()->first();
        if ($first !== null) {
            return $first;
        }

        return AppBranding::query()->create([
            'site_name' => (string) config('app.name', 'Laravel'),
            'tagline' => 'Research & innovation',
            'logo_path' => null,
        ]);
    }

    public function removeLogoFile(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array{name: string, logoUrl: string|null, tagline: string|null}
     */
    public function inertiaProps(): array
    {
        $b = $this->record();

        return [
            'name' => $b->site_name,
            'logoUrl' => $b->logoPublicUrl(),
            'tagline' => $b->tagline,
        ];
    }

    public function siteName(): string
    {
        return $this->record()->site_name;
    }
}
