<?php

namespace App\Support;

/**
 * Failover list for Resend: SMTP first, then HTTP API when the SDK is present.
 */
class MailFailoverMailers
{
    /**
     * @return list<string>
     */
    public static function names(?bool $resendSdkAvailable = null): array
    {
        $mailers = ['smtp'];

        if ($resendSdkAvailable ?? class_exists(\Resend::class)) {
            $mailers[] = 'resend';
        }

        return $mailers;
    }
}
