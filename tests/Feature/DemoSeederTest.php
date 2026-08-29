<?php

use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\DemoSeeder;

beforeEach(function () {
    $this->withoutVite();
});

test('demo seeder creates sample students classes and papers', function () {
    putenv('DEMO_STUDENT_COUNT=5');
    putenv('DEMO_FACULTY_COUNT=2');
    putenv('DEMO_CLASSES_PER_FACULTY=1');

    $this->seed(DemoSeeder::class);

    expect(User::query()->where('email', 'admin@demo.umric.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'like', '%@demo.umric.test')->whereHas('profile', fn ($q) => $q->where('role', 'student'))->count())->toBe(5)
        ->and(UserProfile::query()->where('role', 'faculty')->whereHas('user', fn ($q) => $q->where('email', 'like', '%@demo.umric.test'))->count())->toBe(2)
        ->and(SchoolClass::query()->where('class_code', 'like', 'DEMO-%')->count())->toBeGreaterThan(0)
        ->and(ResearchPaper::query()->count())->toBe(5);

    // Second run is a no-op when demo marker student exists.
    $this->seed(DemoSeeder::class);

    expect(User::query()->where('email', 'like', 'student%@demo.umric.test')->count())->toBe(5);
});
