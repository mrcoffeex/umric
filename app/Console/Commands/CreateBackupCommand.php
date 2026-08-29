<?php

namespace App\Console\Commands;

use App\Exceptions\InvalidBackupException;
use App\Services\BackupScheduleService;
use App\Services\BackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('backup:create')]
#[Description('Create a full application backup of the database and uploaded files')]
class CreateBackupCommand extends Command
{
    public function handle(BackupService $backups, BackupScheduleService $schedule): int
    {
        try {
            $backup = $backups->create();
        } catch (InvalidBackupException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('The backup could not be created.');

            return self::FAILURE;
        }

        $schedule->markRan();
        $this->info("Created {$backup['filename']} ({$backup['size_label']}).");

        return self::SUCCESS;
    }
}
