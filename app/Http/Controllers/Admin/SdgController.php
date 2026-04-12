<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sdg;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SdgController extends Controller
{
    public function index(): Response
    {
        $sdgs = Sdg::orderBy('number')->get();

        return Inertia::render('admin/Sdgs/Index', [
            'sdgs' => $sdgs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'integer', 'between:1,17', 'unique:sdgs,number'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:sdgs,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        Sdg::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'SDG created.']);

        return back();
    }

    public function update(Request $request, Sdg $sdg): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'integer', 'between:1,17', Rule::unique('sdgs', 'number')->ignore($sdg)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', Rule::unique('sdgs', 'code')->ignore($sdg)],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $sdg->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'SDG updated.']);

        return back();
    }

    public function destroy(Sdg $sdg): RedirectResponse
    {
        $sdg->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'SDG deleted.']);

        return back();
    }
}
