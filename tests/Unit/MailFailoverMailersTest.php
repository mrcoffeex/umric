<?php

use App\Support\MailFailoverMailers;

test('failover mailers include smtp and optionally resend', function () {
    expect(MailFailoverMailers::names(false))->toBe(['smtp'])
        ->and(MailFailoverMailers::names(true))->toBe(['smtp', 'resend']);
});
