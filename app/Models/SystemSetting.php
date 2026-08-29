<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'maintenance_mode',
        'maintenance_message',
        'backup_schedule_enabled',
        'backup_schedule_frequency',
        'backup_schedule_last_ran_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'maintenance_mode' => false,
        'backup_schedule_enabled' => true,
        'backup_schedule_frequency' => 'daily',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'maintenance_mode' => 'boolean',
            'backup_schedule_enabled' => 'boolean',
            'backup_schedule_last_ran_at' => 'datetime',
        ];
    }
}
