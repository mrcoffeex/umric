<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        $announcements = Announcement::with('creator')
            ->orderByDesc('is_pinned')
            ->latest()
            ->get();

        return Inertia::render('admin/Announcements/Index', [
            'announcements' => $announcements->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'content' => $a->content,
                'type' => $a->type,
                'is_pinned' => $a->is_pinned,
                'is_active' => $a->is_active,
                'target_roles' => $a->target_roles,
                'published_at' => $a->published_at?->toISOString(),
                'expires_at' => $a->expires_at?->toISOString(),
                'created_by_name' => $a->creator->name,
                'created_at' => $a->created_at->toISOString(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', 'in:info,success,warning,danger'],
            'is_pinned' => ['boolean'],
            'is_active' => ['boolean'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['in:student,faculty,staff,admin'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ]);

        Announcement::create([...$validated, 'created_by' => $request->user()->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Announcement created.']);

        return back();
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', 'in:info,success,warning,danger'],
            'is_pinned' => ['boolean'],
            'is_active' => ['boolean'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['in:student,faculty,staff,admin'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $announcement->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Announcement updated.']);

        return back();
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Announcement deleted.']);

        return back();
    }
}
