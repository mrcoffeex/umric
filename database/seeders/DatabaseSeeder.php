<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Default `php artisan db:seed` entrypoint.
 *
 * - ProductionSeeder: lean baseline (admin, depts, SDG, agendas, formats, 1 class)
 * - DemoSeeder: ~1000 students/papers for demos (opt out with --class=ProductionSeeder)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ProductionSeeder::class,
            // DemoSeeder::class,
        ]);
    }
}
