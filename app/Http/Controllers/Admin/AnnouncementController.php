<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\AnnouncementNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementNotifier $notifier) {}

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
                'thumbnails' => $a->thumbnailPayload(),
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
        $validated = $this->validateAnnouncement($request);
        $validated['thumbnails'] = $this->storeNewThumbnails($request->file('thumbnails', []));

        if (($validated['is_active'] ?? false) && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $announcement = Announcement::create([...$validated, 'created_by' => $request->user()->id]);

        $this->notifier->notifyIfVisible($announcement->fresh());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Announcement created. Target users have been notified.',
        ]);

        return back();
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $wasVisible = $this->notifier->isVisible($announcement);

        $validated = $this->validateAnnouncement($request, $announcement);

        if ($request->hasFile('thumbnails') || $request->exists('keep_thumbnails')) {
            $validated['thumbnails'] = $this->syncThumbnails(
                $announcement,
                $request->input('keep_thumbnails', []),
                $request->file('thumbnails', []),
            );
        }

        if (($validated['is_active'] ?? false) && empty($validated['published_at'])) {
            $validated['published_at'] = $announcement->published_at ?? now();
        }

        $announcement->update($validated);

        if (! $wasVisible && $this->notifier->isVisible($announcement->fresh())) {
            $this->notifier->notifyIfVisible($announcement->fresh());
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Announcement updated. Target users have been notified.',
            ]);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Announcement updated.']);
        }

        return back();
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->deleteThumbnailFiles();
        $announcement->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Announcement deleted.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAnnouncement(Request $request, ?Announcement $existing = null): array
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
            'expires_at' => ['nullable', 'date', ...($existing ? [] : ['after:published_at'])],
            'thumbnails' => ['nullable', 'array', 'max:'.Announcement::MAX_THUMBNAILS],
            'thumbnails.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'keep_thumbnails' => ['nullable', 'array', 'max:'.Announcement::MAX_THUMBNAILS],
            'keep_thumbnails.*' => ['string', 'max:255'],
        ]);

        $existingPaths = $existing?->thumbnails ?? [];
        $keep = collect($request->input('keep_thumbnails', []))
            ->filter(fn ($path) => is_string($path) && in_array($path, $existingPaths, true))
            ->values();

        $newFiles = collect($request->file('thumbnails', []))
            ->filter(fn ($file) => $file instanceof UploadedFile);

        if ($keep->count() + $newFiles->count() > Announcement::MAX_THUMBNAILS) {
            throw ValidationException::withMessages([
                'thumbnails' => 'You may attach up to '.Announcement::MAX_THUMBNAILS.' photos.',
            ]);
        }

        unset($validated['thumbnails'], $validated['keep_thumbnails']);

        return $validated;
    }

    /**
     * @param  array<int, UploadedFile|null>|UploadedFile|null  $files
     * @return list<string>
     */
    private function storeNewThumbnails(array|UploadedFile|null $files): array
    {
        $paths = [];

        foreach (collect($files)->filter(fn ($file) => $file instanceof UploadedFile) as $file) {
            $paths[] = $file->store('announcements/thumbnails', 'public');
        }

        return $paths;
    }

    /**
     * @param  array<int, mixed>  $keepInput
     * @param  array<int, UploadedFile|null>|UploadedFile|null  $newFiles
     * @return list<string>
     */
    private function syncThumbnails(Announcement $announcement, array $keepInput, array|UploadedFile|null $newFiles): array
    {
        $current = $announcement->thumbnails ?? [];
        $keep = collect($keepInput)
            ->filter(fn ($path) => is_string($path) && in_array($path, $current, true))
            ->values()
            ->all();

        $removed = array_values(array_diff($current, $keep));
        $announcement->deleteThumbnailFiles($removed);

        return [...$keep, ...$this->storeNewThumbnails($newFiles)];
    }
}
