<?php

use App\Models\Agenda;
use App\Models\Department;
use App\Models\EvaluationFormat;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Sdg;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->withoutVite();
});

test('production seeder creates admin reference data evaluation formats and one class', function () {
    config([
        'app.env' => 'testing',
        'seeding.admin.name' => 'Production Admin',
        'seeding.admin.email' => 'admin@umdcric.com',
        'seeding.admin.password' => 'SecureAdminPass1!',
        'seeding.class.program' => 'BSIT',
        'seeding.class.year_level' => 4,
        'seeding.class.section' => 'A',
        'seeding.class.school_year' => '2025-2026',
        'seeding.class.semester' => 1,
    ]);

    $this->seed(ProductionSeeder::class);

    $admin = User::query()->where('email', 'admin@umdcric.com')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->isAdmin())->toBeTrue()
        ->and(Hash::check('SecureAdminPass1!', $admin->password))->toBeTrue();

    expect(Department::query()->count())->toBeGreaterThan(0)
        ->and(Program::query()->count())->toBeGreaterThan(0)
        ->and(Sdg::query()->where('is_active', true)->count())->toBe(17)
        ->and(Agenda::query()->where('is_active', true)->count())->toBe(15);

    $formatNames = ['Outline Defense', 'Final Defense', 'Title Proposal Checklist'];
    foreach ($formatNames as $name) {
        $format = EvaluationFormat::query()->where('name', $name)->first();
        expect($format)->not->toBeNull()
            ->and($format->isReady())->toBeTrue();
    }

    $class = SchoolClass::query()->where('class_code', 'BSIT4A-S1-2526')->first();
    expect($class)->not->toBeNull()
        ->and($class->name)->toBe('BSIT 4-A')
        ->and($class->join_code)->not->toBeEmpty();

    // Idempotent on second run.
    $this->seed(ProductionSeeder::class);

    expect(User::query()->where('email', 'admin@umdcric.com')->count())->toBe(1)
        ->and(SchoolClass::query()->where('class_code', 'BSIT4A-S1-2526')->count())->toBe(1)
        ->and(EvaluationFormat::query()->whereIn('name', $formatNames)->count())->toBe(3);
});
