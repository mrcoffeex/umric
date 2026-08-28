<?php

use App\Support\MailScheme;

test('known resend starttls ports force smtp scheme', function (int $port) {
    expect(MailScheme::fromSettings('smtps', $port, 'ssl'))->toBe('smtp');
})->with([25, 587, 2587]);

test('implicit tls ports force smtps scheme', function (int $port) {
    expect(MailScheme::fromSettings('smtp', $port, 'tls'))->toBe('smtps');
})->with([465, 2465]);

test('unknown ports respect explicit scheme or encryption', function () {
    expect(MailScheme::fromSettings('smtps', 2525, null))->toBe('smtps')
        ->and(MailScheme::fromSettings(null, 2525, 'ssl'))->toBe('smtps')
        ->and(MailScheme::fromSettings(null, 2525, 'tls'))->toBe('smtp');
});
