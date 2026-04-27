<?php

use App\Mail\ContactFormMail;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    config(['contact.mail_to' => 'inbox@example.com']);
    Mail::fake();
    RateLimiter::clear('contact-form:127.0.0.1');
});

test('submits contact form and queues mail', function () {
    $payload = [
        'name' => 'Test User',
        'email' => 'visitor@example.com',
        'message' => 'Hello from a test.',
    ];

    $response = $this->from(route('home'))
        ->post(route('contact.store'), $payload);

    $response->assertRedirect();
    Mail::assertQueued(ContactFormMail::class, 1);
});

test('allows only three contact submissions per ip per day in production', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()['env'] = 'production';

    $payload = [
        'name' => 'Test User',
        'email' => 'visitor@example.com',
        'message' => 'Message body.',
    ];

    for ($i = 0; $i < 3; $i++) {
        $this->from(route('home'))
            ->post(route('contact.store'), $payload)
            ->assertRedirect();
    }

    $this->from(route('home'))
        ->post(route('contact.store'), $payload)
        ->assertSessionHasErrors('email');
});

test('does not apply contact rate limit outside production', function () {
    app()['env'] = 'testing';

    $payload = [
        'name' => 'Test User',
        'email' => 'visitor@example.com',
        'message' => 'Message body.',
    ];

    for ($i = 0; $i < 4; $i++) {
        $this->from(route('home'))
            ->post(route('contact.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    Mail::assertQueued(ContactFormMail::class, 4);
});
