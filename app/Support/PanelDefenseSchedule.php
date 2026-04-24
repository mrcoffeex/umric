<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class PanelDefenseSchedule
{
    /**
     * @return list<string>
     */
    public static function allowedTimeValues(): array
    {
        return array_values(config('panel_defense.time_slots', []));
    }

    public static function isAllowedTimeSlot(string $time): bool
    {
        return in_array($time, self::allowedTimeValues(), true);
    }

    public static function combineToCarbon(string $dateYmd, string $timeHi): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $dateYmd.' '.$timeHi, config('app.timezone'));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function timeOptionsForInertia(): array
    {
        return collect(self::allowedTimeValues())
            ->map(fn (string $hm) => [
                'value' => $hm,
                'label' => Carbon::parse('2000-01-01 '.$hm)->format('g:i A'),
            ])
            ->values()
            ->all();
    }
}
