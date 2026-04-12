<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AgendaController extends Controller
{
    public function index(): Response
    {
        $agendas = Agenda::orderBy('name')->get();

        return Inertia::render('admin/Agendas/Index', [
            'agendas' => $agendas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:agendas,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        Agenda::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agenda created.']);

        return back();
    }

    public function update(Request $request, Agenda $agenda): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('agendas', 'code')->ignore($agenda)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $agenda->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agenda updated.']);

        return back();
    }

    public function destroy(Agenda $agenda): RedirectResponse
    {
        $agenda->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agenda deleted.']);

        return back();
    }
}
