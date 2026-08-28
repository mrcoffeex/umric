<?php

namespace App\Support;

/**
 * Derives Symfony Mailer scheme from port / host-panel encryption so leftover
 * MAIL_SCHEME=smtps cannot break Resend STARTTLS ports (587 / 2587).
 */
class MailScheme
{
    /**
     * @return 'smtp'|'smtps'
     */
    public static function fromSettings(?string $scheme, int $port, ?string $encryption): string
    {
        if (in_array($port, [465, 2465], true)) {
            return 'smtps';
        }

        if (in_array($port, [25, 587, 2587], true)) {
            return 'smtp';
        }

        if (filled($scheme)) {
            return strtolower($scheme) === 'smtps' ? 'smtps' : 'smtp';
        }

        return strtolower((string) $encryption) === 'ssl' ? 'smtps' : 'smtp';
    }
}
