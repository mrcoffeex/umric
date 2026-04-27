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
     * Queue a status update email to every active proponent and the lead (paper owner).
     * Proponent ids are string ULIDs: never cast to int.
     * Each recipient gets their own queued message (independent To:).
     * The person triggering the change is not excluded (faculty/staff are rarely proponents).
     */
    public static function dispatch(
        ResearchPaper $paper,
        string $step,
        string $stepLabel,
        string $status,
        ?string $notes = null,
    ): void {
        $idOrder = collect($paper->proponents ?? [])
            ->pluck('id')
            ->map(fn (mixed $id) => (string) $id)
            ->filter()
            ->unique()
            ->values();

        $ownerId = (string) $paper->user_id;
        if (! $idOrder->contains($ownerId)) {
            $idOrder->push($ownerId);
        }

        if ($idOrder->isEmpty()) {
            Log::warning('ResearchStatusUpdated: no recipients found', ['paper_id' => $paper->id]);

            return;
        }

        $users = User::query()
            ->whereIn('id', $idOrder->all())
            ->get(['id', 'email', 'name'])
            ->keyBy(fn (User $u): string => (string) $u->id);

        $recipients = $idOrder
            ->map(fn (string $id) => $users->get($id))
            ->filter()
            ->filter(fn (User $u) => filled($u->email))
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            Log::warning('ResearchStatusUpdated: no recipients with an email', ['paper_id' => $paper->id]);

            return;
        }

        Log::info('ResearchStatusUpdated: queuing for proponents', [
            'paper_id' => $paper->id,
            'emails' => $recipients->pluck('email')->all(),
            'step' => $step,
            'status' => $status,
        ]);

        try {
            foreach ($recipients as $user) {
                Mail::to(new Address($user->email, $user->name))
                    ->queue(new self($paper, $step, $stepLabel, $status, $notes));
            }
        } catch (\Throwable $e) {
            Log::error('ResearchStatusUpdated: failed to queue', [
                'paper_id' => $paper->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
