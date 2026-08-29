<?php

namespace App\Services;

use App\Models\SystemSetting;

class MaintenanceService
{
    public const DEFAULT_MESSAGE = 'The system is temporarily under maintenance. Please try again later.';

    public function record(): SystemSetting
    {
        $first = SystemSetting::query()->first();
        if ($first !== null) {
            return $first;
        }

        return SystemSetting::query()->create([
            'maintenance_mode' => false,
            'maintenance_message' => null,
        ]);
    }

    public function enabled(): bool
    {
        return $this->record()->maintenance_mode;
    }

    public function message(): string
    {
        $message = $this->record()->maintenance_message;

        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }

        return self::DEFAULT_MESSAGE;
    }

    /**
     * @return array{enabled: bool, message: string}
     */
    public function inertiaProps(): array
    {
        return [
            'enabled' => $this->enabled(),
            'message' => $this->message(),
        ];
    }

    /**
     * @param  array{maintenance_mode: bool, maintenance_message?: string|null}  $data
     */
    public function update(array $data): SystemSetting
    {
        $record = $this->record();
        $record->maintenance_mode = $data['maintenance_mode'];
        $record->maintenance_message = $data['maintenance_message'] ?? null;
        $record->save();

        return $record;
    }
}
