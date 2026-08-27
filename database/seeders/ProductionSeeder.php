<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Idempotent baseline data for production / staging.
 *
 * Run:
 *   php artisan db:seed --class=ProductionSeeder --force
 *
 * Configure admin credentials with SEED_ADMIN_* env vars (see .env.example).
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            DepartmentSeeder::class,
            SubjectSeeder::class,
            SdgSeeder::class,
            AgendaSeeder::class,
            EvaluationFormatSeeder::class,
            ProductionSchoolClassSeeder::class,
        ]);

        $this->command?->info('Production seed complete.');
    }
}
