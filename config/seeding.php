<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Production admin account
    |--------------------------------------------------------------------------
    |
    | Used by ProductionSeeder / AdminUserSeeder. Override in tests with
    | config(['seeding.admin.password' => '...']).
    |
    */

    'admin' => [
        'name' => env('SEED_ADMIN_NAME', 'System Administrator'),
        'email' => env('SEED_ADMIN_EMAIL', 'admin@umdcric.com'),
        'password' => env('SEED_ADMIN_PASSWORD', 'ChangeMe!Admin123'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Production starter class
    |--------------------------------------------------------------------------
    */

    'class' => [
        'program' => env('SEED_CLASS_PROGRAM', 'BSIT'),
        'year_level' => (int) env('SEED_CLASS_YEAR_LEVEL', 4),
        'section' => env('SEED_CLASS_SECTION', 'A'),
        'school_year' => env('SEED_CLASS_SCHOOL_YEAR', '2025-2026'),
        'semester' => (int) env('SEED_CLASS_SEMESTER', 1),
    ],

];
