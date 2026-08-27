<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewAnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Announcement $announcement) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'announcement',
            'title' => 'New announcement',
            'body' => $this->announcement->title,
            'url' => $this->urlFor($notifiable),
            'announcement_id' => $this->announcement->id,
            'announcement_type' => $this->announcement->type,
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'announcement';
    }

    private function urlFor(object $notifiable): string
    {
        if ($notifiable instanceof User && $notifiable->isStudent()) {
            return route('student.home', absolute: false);
        }

        return route('dashboard', absolute: false);
    }
}
