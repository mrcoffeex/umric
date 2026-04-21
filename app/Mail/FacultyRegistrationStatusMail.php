<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FacultyRegistrationStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $status, // 'approved' | 'rejected'
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->status === 'approved'
            ? 'Your Faculty Registration Has Been Approved'
            : 'Update on Your Faculty Registration';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.faculty-registration-status',
        );
    }
}
