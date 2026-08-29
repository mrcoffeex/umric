<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\NewAnnouncementNotification;
use Illuminate\Support\Facades\Notification;

class AnnouncementNotifier
{
    public function notifyIfVisible(Announcement $announcement): void
    {
        if (! $this->isVisible($announcement)) {
            return;
        }

        $roles = $announcement->target_roles;

        if (! is_array($roles) || $roles === []) {
            $roles = ['student', 'faculty', 'staff', 'admin'];
        }

        $query = User::query()
            ->whereNull('blocked_at')
            ->whereHas('profile', function ($q) use ($roles): void {
                $q->whereIn('role', $roles)
                    ->where(function ($inner): void {
                        // Pending faculty should not receive product notifications.
                        $inner->where('role', '!=', 'faculty')
                            ->orWhereNotNull('approved_at');
                    });
            });

        if ($announcement->created_by) {
            $query->whereKeyNot($announcement->created_by);
        }

        $query->chunkById(100, function ($users) use ($announcement): void {
            Notification::send($users, new NewAnnouncementNotification($announcement));
        });
    }

    public function isVisible(Announcement $announcement): bool
    {
        if (! $announcement->is_active || $announcement->published_at === null) {
            return false;
        }

        if ($announcement->published_at->isFuture()) {
            return false;
        }

        if ($announcement->expires_at !== null && $announcement->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
