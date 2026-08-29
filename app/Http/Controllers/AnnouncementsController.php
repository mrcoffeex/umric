<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $role = $user->profile?->role ?? 'student';

        $announcements = Announcement::query()
            ->active()
            ->forRole($role)
            ->with('creator:id,name')
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->get()
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'thumbnails' => $announcement->thumbnailPayload(),
                'type' => $announcement->type,
                'is_pinned' => $announcement->is_pinned,
                'published_at' => $announcement->published_at?->toISOString(),
                'expires_at' => $announcement->expires_at?->toISOString(),
                'created_by_name' => $announcement->creator?->name,
            ]);

        return Inertia::render('Announcements/Index', [
            'announcements' => $announcements,
            'role' => $role,
        ]);
    }
}
