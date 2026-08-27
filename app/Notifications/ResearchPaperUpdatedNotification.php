<?php

namespace App\Notifications;

use App\Models\ResearchPaper;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ResearchPaperUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ResearchPaper $paper,
        public string $step,
        public string $stepLabel,
        public string $status,
        public ?string $notes = null,
    ) {}

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
        $statusLabel = ucfirst(str_replace('_', ' ', $this->status));

        return [
            'category' => 'research',
            'title' => 'Research paper update',
            'body' => "{$this->paper->title}: {$this->stepLabel} is now {$statusLabel}.",
            'url' => $this->urlFor($notifiable),
            'paper_id' => $this->paper->id,
            'tracking_id' => $this->paper->tracking_id,
            'step' => $this->step,
            'step_label' => $this->stepLabel,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'research';
    }

    private function urlFor(object $notifiable): string
    {
        if (! $notifiable instanceof User) {
            return route('dashboard', absolute: false);
        }

        return match (true) {
            $notifiable->isStudent() => route('student.research.show', $this->paper, absolute: false),
            $notifiable->isFaculty() => route('faculty.research.show', $this->paper, absolute: false),
            $notifiable->hasRole('admin', 'staff') => route('admin.research.show', $this->paper, absolute: false),
            default => route('dashboard', absolute: false),
        };
    }
}
