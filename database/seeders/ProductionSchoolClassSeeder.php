<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class ProductionSchoolClassSeeder extends Seeder
{
    /**
     * Seed a single starter class for production (BSIT 4-A).
     */
    public function run(): void
    {
        $schoolYear = (string) env('SEED_CLASS_SCHOOL_YEAR', '2025-2026');
        $semester = (int) env('SEED_CLASS_SEMESTER', 1);
        $programCode = (string) env('SEED_CLASS_PROGRAM', 'BSIT');
        $yearLevel = (int) env('SEED_CLASS_YEAR_LEVEL', 4);
        $section = strtoupper((string) env('SEED_CLASS_SECTION', 'A'));

        $program = Program::query()->where('code', $programCode)->where('is_active', true)->first();

        if ($program === null) {
            $this->command?->warn("Program {$programCode} not found; skipping production class seed.");

            return;
        }

        [$startYear, $endYear] = array_pad(explode('-', $schoolYear, 2), 2, null);
        $shortYear = substr((string) $startYear, -2).substr((string) ($endYear ?? ((int) $startYear + 1)), -2);

        $name = "{$program->code} {$yearLevel}-{$section}";
        $classCode = "{$program->code}{$yearLevel}{$section}-S{$semester}-{$shortYear}";

        $class = SchoolClass::query()->updateOrCreate(
            ['class_code' => $classCode],
            [
                'faculty_id' => null,
                'name' => $name,
                'school_year' => $schoolYear,
                'semester' => $semester,
                'term' => null,
                'section' => $section,
                'description' => 'Production starter class. Assign a faculty adviser from the admin panel.',
                'is_active' => true,
            ],
        );

        if ($class->join_code === null || $class->join_code === '') {
            $class->generateAndSetJoinCode();
        }

        $subject = Subject::query()
            ->where('program_id', $program->id)
            ->where('is_active', true)
            ->where('year_level', $yearLevel)
            ->orderBy('code')
            ->first()
            ?? Subject::query()
                ->where('program_id', $program->id)
                ->where('is_active', true)
                ->orderBy('code')
                ->first();

        if ($subject !== null) {
            $class->subjects()->sync([$subject->id]);
        }

        $this->command?->info("Class ready: {$class->name} (join code: {$class->join_code})");
    }
}
