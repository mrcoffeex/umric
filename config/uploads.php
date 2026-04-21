<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum Upload File Size
    |--------------------------------------------------------------------------
    |
    | The maximum allowed size for uploaded research paper files, in megabytes.
    | Set UPLOAD_MAX_SIZE_MB in your .env file to change the limit.
    |
    | Note: this value must not exceed the PHP-level limits set in
    | public/.htaccess (upload_max_filesize) and public/.user.ini.
    | Update those files to match whenever you raise this value.
    |
    */

    'max_size_mb' => (int) env('UPLOAD_MAX_SIZE_MB', 25),

    /**
     * Derived kilobyte value used directly in Laravel's 'max:N' validation rule.
     * Laravel's file validator expects kilobytes.
     */
    'max_size_kb' => (int) env('UPLOAD_MAX_SIZE_MB', 25) * 1024,

];
