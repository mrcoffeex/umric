<?php

use App\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class);

test('mailable can be sent without symfony rfc error on display name in reply to', function () {
    config(['mail.default' => 'array']);

    $mailable = new ContactFormMail(
        'inbox@example.com',
        'kent john',
        'visitor@example.com', // must match the email form field, not the name
        null,
        'Hello from test.',
        '127.0.0.1',
    );

    Mail::mailer('array')->send($mailable);

    expect(true)->toBeTrue();
});
