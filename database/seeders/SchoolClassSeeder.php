<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SchoolClassSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = '2025-2026';
        $semester = 1;
        $shortYear = '2526';

        // Structure: 'PROGRAM_CODE' => [[year_level, section], ...]
        $data = [
            'BSIT' => [
                [1, 'A'], [1, 'B'],
                [2, 'A'], [2, 'B'],
                [3, 'A'], [3, 'B'],
                [4, 'A'],
            ],
            'BSCOMP' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSA' => [
                [1, 'A'], [1, 'B'],
                [2, 'A'], [2, 'B'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSMA' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSBA-FM' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSBA-HRM' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSBA-MM' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSTM' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSCRIM' => [
                [1, 'A'], [1, 'B'],
                [2, 'A'], [2, 'B'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BEED' => [
                [1, 'A'], [1, 'B'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSED-SCI' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSED-ENG' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSED-MATH' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSED-FIL' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSED-SS' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BPED' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSHRM' => [
                [1, 'A'], [1, 'B'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSPSY' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
            'BSSW' => [
                [1, 'A'],
                [2, 'A'],
                [3, 'A'],
                [4, 'A'],
            ],
        ];

        foreach ($data as $programCode => $sections) {
            $program = Program::where('code', $programCode)->first();

            if (! $program) {
                continue;
            }

            $subjectsByYear = Subject::query()
                ->where('program_id', $program->id)
                ->where('is_active', true)
                ->orderBy('code')
                ->get()
                ->groupBy(fn (Subject $subject) => $subject->year_level ?? 0);

            $allProgramSubjects = $subjectsByYear->flatten(1);

            foreach ($sections as [$yearLevel, $section]) {
                $name = "{$program->code} {$yearLevel}-{$section}";
                $classCode = "{$program->code}{$yearLevel}{$section}-S{$semester}-{$shortYear}";

                $class = SchoolClass::updateOrCreate(
                    ['class_code' => $classCode],
                    [
                        'name' => $name,
                        'class_code' => $classCode,
                        'school_year' => $schoolYear,
                        'semester' => $semester,
                        'term' => null,
                        'section' => $section,
                        'description' => null,
                        'is_active' => true,
                    ],
                );

                $subject = $subjectsByYear->get($yearLevel)?->first() ?? $allProgramSubjects->first();
                if ($subject) {
                    $class->subjects()->sync([$subject->id]);
                }
            }
        }
    }
}
