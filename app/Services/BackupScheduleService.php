<?php

namespace App\Services;

use App\Models\SystemSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class BackupScheduleService
{
    public const FREQUENCY_HOURLY = 'hourly';

    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    /**
     * @var list<string>
     */
    public const FREQUENCIES = [
        self::FREQUENCY_HOURLY,
        self::FREQUENCY_DAILY,
        self::FREQUENCY_WEEKLY,
        self::FREQUENCY_MONTHLY,
    ];

    public function __construct(
        private MaintenanceService $maintenance,
    ) {}

    public function record(): SystemSetting
    {
        return $this->maintenance->record();
    }

    public function enabled(): bool
    {
        return (bool) $this->record()->backup_schedule_enabled;
    }

    public function frequency(): string
    {
        $frequency = (string) $this->record()->backup_schedule_frequency;

        return in_array($frequency, self::FREQUENCIES, true)
            ? $frequency
            : self::FREQUENCY_DAILY;
    }

    public function lastRanAt(): ?Carbon
    {
        $lastRanAt = $this->record()->backup_schedule_last_ran_at;

        return $lastRanAt === null ? null : Carbon::parse($lastRanAt);
    }

    public function shouldRun(?CarbonInterface $now = null): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $now = Carbon::parse($now ?? now());
        $lastRanAt = $this->lastRanAt();

        return match ($this->frequency()) {
            self::FREQUENCY_HOURLY => $lastRanAt === null || $lastRanAt->lte($now->copy()->subHour()),
            self::FREQUENCY_DAILY => $now->hour === 2 && ($lastRanAt === null || ! $lastRanAt->isSameDay($now)),
            self::FREQUENCY_WEEKLY => $now->isSunday() && $now->hour === 2 && ($lastRanAt === null || $lastRanAt->lt($now->copy()->startOfWeek(Carbon::SUNDAY))),
            self::FREQUENCY_MONTHLY => $now->day === 1 && $now->hour === 2 && ($lastRanAt === null || ! $lastRanAt->isSameMonth($now)),
            default => false,
        };
    }

    public function markRan(?CarbonInterface $at = null): void
    {
        $record = $this->record();
        $record->backup_schedule_last_ran_at = Carbon::parse($at ?? now());
        $record->save();
    }

    /**
     * @param  array{enabled: bool, frequency: string}  $data
     */
    public function update(array $data): SystemSetting
    {
        $record = $this->record();
        $record->backup_schedule_enabled = $data['enabled'];
        $record->backup_schedule_frequency = $data['frequency'];
        $record->save();

        return $record;
    }

    /**
     * @return array{
     *     enabled: bool,
     *     frequency: string,
     *     last_ran_at: string|null,
     *     last_ran_at_formatted: string|null,
     *     next_run_label: string,
     *     next_run_at_formatted: string|null,
     *     frequencies: list<array{value: string, label: string, description: string}>
     * }
     */
    public function inertiaProps(): array
    {
        $lastRanAt = $this->lastRanAt();
        $nextRunAt = $this->nextRunAt();

        return [
            'enabled' => $this->enabled(),
            'frequency' => $this->frequency(),
            'last_ran_at' => $lastRanAt?->toIso8601String(),
            'last_ran_at_formatted' => $lastRanAt
                ? $lastRanAt->timezone((string) config('app.timezone'))->format('M d, Y H:i')
                : null,
            'next_run_label' => $this->nextRunLabel(),
            'next_run_at_formatted' => $nextRunAt
                ? $nextRunAt->timezone((string) config('app.timezone'))->format('M d, Y H:i')
                : null,
            'frequencies' => [
                [
                    'value' => self::FREQUENCY_HOURLY,
                    'label' => 'Every hour',
                    'description' => 'Creates a downloadable archive at the start of every hour.',
                ],
                [
                    'value' => self::FREQUENCY_DAILY,
                    'label' => 'Every day',
                    'description' => 'Creates a downloadable archive every day at 2:00 AM.',
                ],
                [
                    'value' => self::FREQUENCY_WEEKLY,
                    'label' => 'Every week',
                    'description' => 'Creates a downloadable archive every Sunday at 2:00 AM.',
                ],
                [
                    'value' => self::FREQUENCY_MONTHLY,
                    'label' => 'Every month',
                    'description' => 'Creates a downloadable archive on the 1st of each month at 2:00 AM.',
                ],
            ],
        ];
    }

    public function nextRunLabel(): string
    {
        if (! $this->enabled()) {
            return 'Automatic backups are off.';
        }

        $nextRunAt = $this->nextRunAt();

        if ($nextRunAt === null) {
            return 'Automatic backups are off.';
        }

        return 'Next downloadable archive: '.$nextRunAt->timezone((string) config('app.timezone'))->format('M d, Y H:i');
    }

    public function nextRunAt(?CarbonInterface $now = null): ?Carbon
    {
        if (! $this->enabled()) {
            return null;
        }

        $now = Carbon::parse($now ?? now());

        return match ($this->frequency()) {
            self::FREQUENCY_HOURLY => $now->copy()->startOfHour()->addHour(),
            self::FREQUENCY_DAILY => $this->nextDailyAt($now),
            self::FREQUENCY_WEEKLY => $this->nextWeeklyAt($now),
            self::FREQUENCY_MONTHLY => $this->nextMonthlyAt($now),
            default => null,
        };
    }

    private function nextDailyAt(Carbon $now): Carbon
    {
        $candidate = $now->copy()->setTime(2, 0);

        if ($now->gte($candidate)) {
            return $candidate->addDay();
        }

        return $candidate;
    }

    private function nextWeeklyAt(Carbon $now): Carbon
    {
        $candidate = $now->copy()->startOfWeek(Carbon::SUNDAY)->setTime(2, 0);

        if ($now->gte($candidate)) {
            return $candidate->addWeek();
        }

        return $candidate;
    }

    private function nextMonthlyAt(Carbon $now): Carbon
    {
        $candidate = $now->copy()->startOfMonth()->setTime(2, 0);

        if ($now->gte($candidate)) {
            return $candidate->addMonth();
        }

        return $candidate;
    }
}
