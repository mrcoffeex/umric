<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactFormMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $to = config('contact.mail_to');

        if (! is_string($to) || $to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            report(new \RuntimeException('Contact form: set MAIL_CONTACT_TO (or MAIL_FROM_ADDRESS) to a valid email.'));

            throw ValidationException::withMessages([
                'email' => 'The contact form is temporarily unavailable. Please try again later.',
            ]);
        }

        $validated = $request->validated();
        $ip = (string) $request->ip();

        $queueContactMail = function () use ($validated, $ip, $to): void {
            Mail::queue(new ContactFormMail(
                inboxEmail: $to,
                fromName: $validated['name'],
                visitorEmail: (string) $validated['email'],
                role: $validated['role'] ?? null,
                bodyText: $validated['message'],
                submitterIp: $ip,
            ));
        };

        if (app()->isProduction()) {
            $executed = RateLimiter::attempt(
                'contact-form:'.$ip,
                3,
                $queueContactMail,
                86_400,
            );

            if (! $executed) {
                throw ValidationException::withMessages([
                    'email' => 'You can send up to 3 messages per day from this network. Please try again tomorrow.',
                ]);
            }
        } else {
            $queueContactMail();
        }

        return redirect()->back();
    }
}
