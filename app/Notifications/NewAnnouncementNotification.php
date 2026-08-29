<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewAnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Announcement $announcement)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Database notifications should appear immediately in the bell;
     * mail stays on the app queue so SMTP does not block the request.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return [
            'database' => 'sync',
            'mail' => (string) config('queue.default'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->announcement->title;
        $preview = Str::limit(trim(strip_tags((string) $this->announcement->content)), 200);

        $mail = (new MailMessage)
            ->subject('New announcement: '.$title)
            ->greeting('Hello!')
            ->line('A new announcement was posted.')
            ->line('**'.$title.'**');

        if ($preview !== '') {
            $mail->line($preview);
        }

        return $mail
            ->action('Open UMRIC', url($this->urlFor($notifiable)))
            ->line('Thank you for staying up to date.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'announcement',
            'title' => $this->announcement->title,
            'body' => Str::limit(trim(strip_tags((string) $this->announcement->content)), 160),
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
        return route('announcements.index', absolute: false);
    }
}
