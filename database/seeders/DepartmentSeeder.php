<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Seed departments and programs sourced from https://digos.umindanao.edu.ph/academics
     */
    public function run(): void
    {
        $data = [
            [
                'code' => 'DAE',
                'name' => 'Department of Accounting Education',
                'programs' => [
                    ['code' => 'BSA',  'name' => 'Bachelor of Science in Accountancy'],
                    ['code' => 'BSMA', 'name' => 'Bachelor of Science in Management Accounting'],
                ],
            ],
            [
                'code' => 'DASE',
                'name' => 'Department of Arts and Sciences Education',
                'programs' => [
                    ['code' => 'BSPSY',  'name' => 'Bachelor of Science in Psychology'],
                    ['code' => 'BSSW',   'name' => 'Bachelor of Science in Social Work'],
                    ['code' => 'ABPOLSCI', 'name' => 'Bachelor of Arts in Political Science'],
                    ['code' => 'ABCOMM', 'name' => 'Bachelor of Arts in Communication'],
                ],
            ],
            [
                'code' => 'DBAE',
                'name' => 'Department of Business Administration Education',
                'programs' => [
                    ['code' => 'BSBA-FM', 'name' => 'Bachelor of Science in Business Administration - Financial Management'],
                    ['code' => 'BSBA-HRM', 'name' => 'Bachelor of Science in Business Administration - Human Resource Management'],
                    ['code' => 'BSBA-MM', 'name' => 'Bachelor of Science in Business Administration - Marketing Management'],
                    ['code' => 'BSTM',   'name' => 'Bachelor of Science in Tourism Management'],
                ],
            ],
            [
                'code' => 'DCJE',
                'name' => 'Department of Criminal Justice Education',
                'programs' => [
                    ['code' => 'BSCRIM', 'name' => 'Bachelor of Science in Criminology'],
                ],
            ],
            [
                'code' => 'DTE',
                'name' => 'Department of Teaching Education',
                'programs' => [
                    ['code' => 'BEED',          'name' => 'Bachelor in Elementary Education'],
                    ['code' => 'BSNED',          'name' => 'Bachelor of Special Needs Education major in Elementary School Teaching'],
                    ['code' => 'BPED',           'name' => 'Bachelor of Physical Education'],
                    ['code' => 'BSED-SCI',       'name' => 'Bachelor in Secondary Education - Science'],
                    ['code' => 'BSED-ENG',       'name' => 'Bachelor in Secondary Education - English'],
                    ['code' => 'BSED-FIL',       'name' => 'Bachelor in Secondary Education - Filipino'],
                    ['code' => 'BSED-SS',        'name' => 'Bachelor in Secondary Education - Social Studies'],
                    ['code' => 'BSED-MATH',      'name' => 'Bachelor in Secondary Education - Mathematics'],
                    ['code' => 'BTVTED-FSM',     'name' => 'Bachelor of Technical Vocational Teacher Education - Food Service Management'],
                    ['code' => 'BTVTED-AT',      'name' => 'Bachelor of Technical Vocational Teacher Education - Automotive Technology'],
                ],
            ],
            [
                'code' => 'DHE',
                'name' => 'Department of Hospitality Education',
                'programs' => [
                    ['code' => 'BSHRM', 'name' => 'Bachelor of Science in Hotel and Restaurant Management'],
                ],
            ],
            [
                'code' => 'DTP',
                'name' => 'Department of Technical Programs',
                'programs' => [
                    ['code' => 'BSCOMP', 'name' => 'Bachelor of Science in Computer Engineering'],
                    ['code' => 'BSIT',   'name' => 'Bachelor of Science in Information Technology'],
                ],
            ],
            [
                'code' => 'PS',
                'name' => 'Professional Schools',
                'programs' => [
                    ['code' => 'PHD-AL',   'name' => 'Ph.D. in Applied Linguistics'],
                    ['code' => 'DOED-EM',  'name' => 'Doctor of Education - Educational Management'],
                    ['code' => 'DBA',      'name' => 'Doctor in Business Administration'],
                    ['code' => 'MAED-EM',  'name' => 'Master of Arts in Education - Educational Management'],
                    ['code' => 'MAED-MATH','name' => 'Master of Arts in Education - Mathematics'],
                    ['code' => 'MAED-SCI', 'name' => 'Master of Arts in Education - Science'],
                    ['code' => 'MAED-ENG', 'name' => 'Master of Arts in Education - English'],
                    ['code' => 'MAED-FIL', 'name' => 'Master of Arts in Education - Filipino'],
                    ['code' => 'MAED-TLE', 'name' => 'Master of Arts in Education - TLE'],
                    ['code' => 'MAED-PE',  'name' => 'Master of Arts in Education - Physical Education'],
                    ['code' => 'MBA',      'name' => 'Master in Business Administration'],
                    ['code' => 'MIS',      'name' => 'Master in Information System'],
                    ['code' => 'MIT',      'name' => 'Master in Information Technology'],
                    ['code' => 'MSCJ',     'name' => 'Master in Science in Criminal Justice'],
                    ['code' => 'MSPSY',    'name' => 'Master in Science in Psychology'],
                    ['code' => 'MPA',      'name' => 'Master in Public Administration'],
                ],
            ],
        ];

        foreach ($data as $dept) {
            $programs = $dept['programs'];
            unset($dept['programs']);

            $department = Department::updateOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name'], 'is_active' => true],
            );

            foreach ($programs as $program) {
                Program::updateOrCreate(
                    ['code' => $program['code']],
                    ['department_id' => $department->id, 'name' => $program['name'], 'is_active' => true],
                );
            }
        }
    }
}

