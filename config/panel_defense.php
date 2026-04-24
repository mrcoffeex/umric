<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed defense time slots (24h H:i, 30-minute steps)
    |--------------------------------------------------------------------------
    |
    | Panel management uses these fixed options combined with a date.
    | Schedules must match exactly to be valid and are checked for duplicates.
    | Default: 8:00 AM through 8:00 PM inclusive.
    |
    */
    'time_slots' => (static function (): array {
        $slots = [];
        for ($minutes = 8 * 60; $minutes <= 20 * 60; $minutes += 30) {
            $slots[] = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        }

        return $slots;
    })(),
];
