<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBrandingRequest;
use App\Services\BrandingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    public function __construct(
        private BrandingService $branding,
    ) {}

    public function index(): Response
    {
        $r = $this->branding->record();

        return Inertia::render('admin/Branding/Index', [
            'branding' => [
                'site_name' => $r->site_name,
                'tagline' => $r->tagline,
                'logo_url' => $r->logoPublicUrl(),
            ],
        ]);
    }

    public function update(UpdateBrandingRequest $request): RedirectResponse
    {
        $record = $this->branding->record();
        $validated = $request->validated();

        if ($request->hasFile('logo')) {
            $this->branding->removeLogoFile($record->logo_path);
            $path = $request->file('logo')->store('brand', 'public');
            $record->logo_path = $path;
        } elseif ($request->boolean('remove_logo')) {
            $this->branding->removeLogoFile($record->logo_path);
            $record->logo_path = null;
        }

        $record->site_name = $validated['site_name'];
        $record->tagline = $validated['tagline'] ?? null;
        $record->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Site branding updated.']);

        return redirect()->route('admin.branding.index');
    }
}
