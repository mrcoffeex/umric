<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Storage
    |--------------------------------------------------------------------------
    |
    | Archives are stored on this disk under the given path. The local disk
    | keeps files outside the public web root.
    |
    */

    'disk' => env('BACKUP_DISK', 'local'),

    'path' => 'backups',

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | How many completed archives to keep. Older archives are deleted after
    | each successful create.
    |
    */

    'keep' => (int) env('BACKUP_RETENTION', 14),

    /*
    |--------------------------------------------------------------------------
    | Uploaded Files
    |--------------------------------------------------------------------------
    |
    | Application files from these disks are packed into the archive. The
    | backups directory itself is always skipped.
    |
    */

    'file_disks' => ['public', 'local'],

    /*
    |--------------------------------------------------------------------------
    | Upload Limits
    |--------------------------------------------------------------------------
    |
    | Maximum size of an uploaded restore archive, in kilobytes.
    |
    */

    'max_upload_kilobytes' => (int) env('BACKUP_MAX_UPLOAD_KB', 262144),

    /*
    |--------------------------------------------------------------------------
    | Excluded Tables
    |--------------------------------------------------------------------------
    |
    | Ephemeral or engine-specific tables that should not be dumped.
    |
    */

    'exclude_tables' => [
        'cache',
        'cache_locks',
        'sessions',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'migrations',
        'sqlite_sequence',
    ],

];
