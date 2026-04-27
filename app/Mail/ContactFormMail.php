<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email as SymfonyEmail;

class ContactFormMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $inboxEmail,
        public string $fromName,
        public string $visitorEmail,
        public ?string $role,
        public string $bodyText,
        public string $submitterIp,
    ) {}

    public function envelope(): Envelope
    {
        $fromConfig = (string) config('mail.from.address', '');
        $fromName = config('mail.from.name');

        return new Envelope(
            from: $fromConfig !== '' ? new Address(
                $fromConfig,
                is_string($fromName) ? $fromName : null,
            ) : null,
            to: [new Address($this->inboxEmail)],
            subject: 'Website contact: '.$this->fromName,
            replyTo: [],
            // Do not set replyTo via Mailable/Envelope: Laravel can mis-map name ↔ email
            // into Symfony’s Address (see RfcComplianceException on the display name).
            // Set Reply-To on the Symfony message with a filter-validated address only.
            using: [fn (SymfonyEmail $message) => $this->applyReplyToHeader($message)],
        );
    }

    private function applyReplyToHeader(SymfonyEmail $message): void
    {
        $valid = filter_var(trim($this->visitorEmail), FILTER_VALIDATE_EMAIL);
        if ($valid === false) {
            return;
        }

        $name = trim($this->fromName);
        if ($name !== '') {
            $message->replyTo(new SymfonyAddress($valid, $name));
        } else {
            $message->replyTo($valid);
        }
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.contact-form-text',
        );
    }

    /**
     * Drop any reply-to list Laravel merged from a queued payload or legacy builds.
     * Reply-To is set only in applyReplyToHeader() (Symfony message) with visitorEmail.
     */
    protected function buildRecipients($message)
    {
        $this->replyTo = [];

        return parent::buildRecipients($message);
    }
}
