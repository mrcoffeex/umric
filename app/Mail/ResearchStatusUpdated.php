<?php

namespace App\Mail;

use App\Models\ResearchPaper;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ResearchStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ResearchPaper $paper,
        public string $step,
        public string $stepLabel,
        public string $status,
        public ?string $notes = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Research Paper Status Update: '.$this->paper->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.research-status-updated',
        );
    }

    /**
     * Send status update email to the research paper's student and proponents.
     * - To:  main proponent (first in the proponents JSON, i.e. the lead student)
     *        Falls back to paper->user_id if proponents list is empty.
     * - CC:  remaining proponents + paper->user_id (if not already included).
     * The admin/faculty triggering the action is never emailed.
     */
    public static function dispatch(
        ResearchPaper $paper,
        string $step,
        string $stepLabel,
        string $status,
        ?string $notes = null,
    ): void {
        $proponents = collect($paper->proponents ?? []);

        // Build ordered list of IDs: proponents first, then owner as fallback
        $orderedIds = $proponents
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        // Ensure the paper creator is always included
        if (! $orderedIds->contains($paper->user_id)) {
            $orderedIds->push($paper->user_id);
        }

        if ($orderedIds->isEmpty()) {
            Log::warning('ResearchStatusUpdated: no recipients found', ['paper_id' => $paper->id]);

            return;
        }

        $users = User::whereIn('id', $orderedIds->all())
            ->get(['id', 'email', 'name'])
            ->keyBy('id');

        $mainUser = $users->get($orderedIds->first());

        if (! $mainUser) {
            Log::warning('ResearchStatusUpdated: main recipient user not found', [
                'paper_id' => $paper->id,
                'main_id' => $orderedIds->first(),
            ]);

            return;
        }

        $ccUsers = $orderedIds->skip(1)
            ->map(fn ($id) => $users->get($id))
            ->filter()
            ->map(fn (User $u) => new Address($u->email, $u->name))
            ->values()
            ->all();

        Log::info('ResearchStatusUpdated: sending', [
            'paper_id' => $paper->id,
            'to' => $mainUser->email,
            'cc' => array_map(fn ($a) => $a->address, $ccUsers),
            'step' => $step,
            'status' => $status,
        ]);

        try {
            $mailable = new self($paper, $step, $stepLabel, $status, $notes);

            $mailer = Mail::to(new Address($mainUser->email, $mainUser->name));

            if (! empty($ccUsers)) {
                $mailer->cc($ccUsers);
            }

            $mailer->send($mailable);

            Log::info('ResearchStatusUpdated: sent successfully to '.$mainUser->email, ['paper_id' => $paper->id]);
        } catch (\Throwable $e) {
            Log::error('ResearchStatusUpdated: failed to send', [
                'paper_id' => $paper->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
