<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Support\JsonContains;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $user = $request->user();

        $announcements = Announcement::active()
            ->forRole('student')
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->take(3)
            ->get();

        $classes = SchoolClass::query()
            ->whereHas('members', fn ($query) => $query->where('student_id', $user->id))
            ->with('subjects.program')
            ->get();

        $userId = $user->id;

        $paper = ResearchPaper::query()
            ->with([
                'trackingRecords' => fn ($query) => $query->latest('status_changed_at')->limit(1),
            ])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId);
                JsonContains::whereArrayObjectContains(
                    $query,
                    'proponents',
                    ['id' => (string) $userId],
                    or: true,
                );
            })
            ->first();

        $recentNotifications = $user->unreadNotifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'category' => $notification->data['category'] ?? 'general',
                'title' => $notification->data['title'] ?? 'Notification',
                'body' => $notification->data['body'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'created_at' => $notification->created_at?->toISOString(),
            ]);

        return Inertia::render('student/Home', [
            'announcements' => $announcements,
            'classes' => $classes,
            'paper' => $paper ? [
                ...$paper->toArray(),
                'is_returned' => $paper->isRicReviewReturned(),
                'last_update' => $paper->trackingRecords->first() ? [
                    'step' => $paper->trackingRecords->first()->step,
                    'action' => $paper->trackingRecords->first()->action,
                    'status' => $paper->trackingRecords->first()->status,
                    'notes' => $paper->trackingRecords->first()->notes,
                    'at' => ($paper->trackingRecords->first()->status_changed_at
                        ?? $paper->trackingRecords->first()->created_at)?->toISOString(),
                ] : null,
                'upcoming_defense' => $this->upcomingDefense($paper),
            ] : null,
            'stepLabels' => ResearchPaper::STEP_LABELS,
            'steps' => ResearchPaper::STEPS,
            'hasClass' => $classes->isNotEmpty(),
            'attention' => [
                'unread_notifications' => $recentNotifications,
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * @return array{type: string, at: string}|null
     */
    private function upcomingDefense(ResearchPaper $paper): ?array
    {
        $candidates = collect([
            ['type' => 'outline', 'at' => $paper->outline_defense_schedule],
            ['type' => 'final', 'at' => $paper->final_defense_schedule],
        ])
            ->filter(fn (array $item) => $item['at'] !== null && $item['at']->isFuture())
            ->sortBy('at')
            ->first();

        if (! $candidates) {
            return null;
        }

        return [
            'type' => $candidates['type'],
            'at' => $candidates['at']->toISOString(),
        ];
    }
}
