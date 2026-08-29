<?php

use App\Console\Commands\CreateBackupCommand;
use App\Services\BackupScheduleService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CreateBackupCommand::class)
    ->hourly()
    ->when(fn (): bool => app(BackupScheduleService::class)->shouldRun())
    ->withoutOverlapping();
