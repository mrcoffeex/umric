<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * @var array<string, bool>
     */
    private array $usedCodes = [];

    public function run(): void
    {
        // Structure: 'PROGRAM_CODE' => [['code' => 'SUBJ101', 'name' => '...'], ...]
        $data = [
            // BSIT - Dept of Technical Programs
            'BSIT' => [
                ['code' => 'IT101', 'name' => 'Introduction to Computing'],
                ['code' => 'IT102', 'name' => 'Computer Programming 1'],
                ['code' => 'IT103', 'name' => 'Computer Programming 2'],
                ['code' => 'IT201', 'name' => 'Data Structures and Algorithms'],
                ['code' => 'IT202', 'name' => 'Database Management Systems'],
                ['code' => 'IT203', 'name' => 'Operating Systems'],
                ['code' => 'IT204', 'name' => 'Object-Oriented Programming'],
                ['code' => 'IT301', 'name' => 'Web Development'],
                ['code' => 'IT302', 'name' => 'Network Administration and Security'],
                ['code' => 'IT303', 'name' => 'Systems Analysis and Design'],
                ['code' => 'IT304', 'name' => 'Human-Computer Interaction'],
                ['code' => 'IT401', 'name' => 'Software Engineering'],
                ['code' => 'IT402', 'name' => 'Information Assurance and Security'],
                ['code' => 'IT501', 'name' => 'Research Methods in IT'],
                ['code' => 'IT601', 'name' => 'Capstone Project 1'],
                ['code' => 'IT602', 'name' => 'Capstone Project 2'],
            ],
            // BSCOMP - Dept of Technical Programs
            'BSCOMP' => [
                ['code' => 'CE101', 'name' => 'Engineering Mathematics 1'],
                ['code' => 'CE102', 'name' => 'Engineering Mathematics 2'],
                ['code' => 'CE201', 'name' => 'Digital Logic Design'],
                ['code' => 'CE202', 'name' => 'Computer Architecture and Organization'],
                ['code' => 'CE203', 'name' => 'Data Communications'],
                ['code' => 'CE301', 'name' => 'Embedded Systems'],
                ['code' => 'CE302', 'name' => 'VLSI Design'],
                ['code' => 'CE401', 'name' => 'Computer Engineering Design Project'],
                ['code' => 'CE501', 'name' => 'Research Methods in Engineering'],
            ],
            // BSA - Dept of Accounting Education
            'BSA' => [
                ['code' => 'ACC101', 'name' => 'Financial Accounting and Reporting 1'],
                ['code' => 'ACC102', 'name' => 'Financial Accounting and Reporting 2'],
                ['code' => 'ACC201', 'name' => 'Intermediate Accounting 1'],
                ['code' => 'ACC202', 'name' => 'Intermediate Accounting 2'],
                ['code' => 'ACC203', 'name' => 'Intermediate Accounting 3'],
                ['code' => 'ACC301', 'name' => 'Cost Accounting and Control'],
                ['code' => 'ACC302', 'name' => 'Taxation 1 - Income Tax'],
                ['code' => 'ACC303', 'name' => 'Taxation 2 - Business Taxes'],
                ['code' => 'ACC401', 'name' => 'Auditing Theory'],
                ['code' => 'ACC402', 'name' => 'Auditing Practice'],
                ['code' => 'ACC403', 'name' => 'Accounting Information Systems'],
                ['code' => 'ACC501', 'name' => 'CPA Review - Financial Accounting'],
                ['code' => 'ACC502', 'name' => 'CPA Review - Auditing'],
            ],
            // BSMA
            'BSMA' => [
                ['code' => 'MA101', 'name' => 'Management Accounting Concepts'],
                ['code' => 'MA201', 'name' => 'Cost Management'],
                ['code' => 'MA202', 'name' => 'Performance Management'],
                ['code' => 'MA301', 'name' => 'Strategic Management Accounting'],
                ['code' => 'MA401', 'name' => 'Accounting Research Methods'],
            ],
            // BSBA-FM
            'BSBA-FM' => [
                ['code' => 'BA101', 'name' => 'Principles of Management'],
                ['code' => 'BA102', 'name' => 'Business Communication'],
                ['code' => 'FM201', 'name' => 'Financial Management 1'],
                ['code' => 'FM202', 'name' => 'Financial Management 2'],
                ['code' => 'FM301', 'name' => 'Investment Management'],
                ['code' => 'FM302', 'name' => 'Credit and Banking'],
                ['code' => 'FM401', 'name' => 'Corporate Finance'],
                ['code' => 'FM501', 'name' => 'Business Research Methods'],
            ],
            // BSBA-HRM
            'BSBA-HRM' => [
                ['code' => 'BA101', 'name' => 'Principles of Management'],
                ['code' => 'HRM201', 'name' => 'Human Resource Management'],
                ['code' => 'HRM202', 'name' => 'Recruitment and Selection'],
                ['code' => 'HRM301', 'name' => 'Training and Development'],
                ['code' => 'HRM302', 'name' => 'Compensation and Benefits'],
                ['code' => 'HRM401', 'name' => 'Labor Relations and Legislation'],
                ['code' => 'HRM501', 'name' => 'Business Research Methods'],
            ],
            // BSBA-MM
            'BSBA-MM' => [
                ['code' => 'BA101', 'name' => 'Principles of Management'],
                ['code' => 'MM201', 'name' => 'Principles of Marketing'],
                ['code' => 'MM202', 'name' => 'Consumer Behavior'],
                ['code' => 'MM301', 'name' => 'Marketing Research'],
                ['code' => 'MM302', 'name' => 'Digital Marketing'],
                ['code' => 'MM401', 'name' => 'Brand Management'],
                ['code' => 'MM501', 'name' => 'Business Research Methods'],
            ],
            // BSTM
            'BSTM' => [
                ['code' => 'TM101', 'name' => 'Introduction to Tourism'],
                ['code' => 'TM102', 'name' => 'Philippine Tourism Geography'],
                ['code' => 'TM201', 'name' => 'Tour Operations Management'],
                ['code' => 'TM202', 'name' => 'Sustainable Tourism Development'],
                ['code' => 'TM301', 'name' => 'Tourism Marketing'],
                ['code' => 'TM401', 'name' => 'Tourism Research Methods'],
            ],
            // BSCRIM
            'BSCRIM' => [
                ['code' => 'CJ101', 'name' => 'Introduction to Criminology'],
                ['code' => 'CJ102', 'name' => 'Criminal Law 1'],
                ['code' => 'CJ103', 'name' => 'Criminal Law 2'],
                ['code' => 'CJ201', 'name' => 'Criminal Investigation'],
                ['code' => 'CJ202', 'name' => 'Criminalistics'],
                ['code' => 'CJ203', 'name' => 'Legal Medicine'],
                ['code' => 'CJ301', 'name' => 'Law Enforcement Administration'],
                ['code' => 'CJ302', 'name' => 'Juvenile Delinquency and Crimes'],
                ['code' => 'CJ401', 'name' => 'Correctional Administration'],
                ['code' => 'CJ501', 'name' => 'Criminology Research Methods'],
            ],
            // BEED
            'BEED' => [
                ['code' => 'ED101', 'name' => 'Child and Adolescent Development'],
                ['code' => 'ED102', 'name' => 'Facilitating Learner-Centered Teaching'],
                ['code' => 'ED103', 'name' => 'The Teacher and the Community'],
                ['code' => 'ED201', 'name' => 'The Teacher and the School Curriculum'],
                ['code' => 'ED202', 'name' => 'Building and Enhancing Literacy Skills'],
                ['code' => 'ED301', 'name' => 'Assessment in Learning 1'],
                ['code' => 'ED302', 'name' => 'Assessment in Learning 2'],
                ['code' => 'ED401', 'name' => 'Principles of Teaching 1'],
                ['code' => 'ED402', 'name' => 'Principles of Teaching 2'],
                ['code' => 'ED501', 'name' => 'Field Study 1'],
                ['code' => 'ED502', 'name' => 'Field Study 2'],
                ['code' => 'ED601', 'name' => 'Practice Teaching (Pre-School and Elementary)'],
                ['code' => 'ED701', 'name' => 'Action Research in Education'],
            ],
            // BSED-SCI
            'BSED-SCI' => [
                ['code' => 'SED101', 'name' => 'Teaching General Science'],
                ['code' => 'SED201', 'name' => 'Teaching Biology'],
                ['code' => 'SED202', 'name' => 'Teaching Chemistry'],
                ['code' => 'SED203', 'name' => 'Teaching Physics'],
                ['code' => 'SED301', 'name' => 'Teaching Earth Science'],
                ['code' => 'SED501', 'name' => 'Field Study - Secondary Science'],
                ['code' => 'SED601', 'name' => 'Practice Teaching - Secondary Science'],
                ['code' => 'SED701', 'name' => 'Action Research in Science Teaching'],
            ],
            // BSED-MATH
            'BSED-MATH' => [
                ['code' => 'MED101', 'name' => 'Teaching Mathematics in the Secondary School'],
                ['code' => 'MED201', 'name' => 'Number Theory'],
                ['code' => 'MED202', 'name' => 'Calculus for Teachers'],
                ['code' => 'MED301', 'name' => 'Modern Geometry'],
                ['code' => 'MED401', 'name' => 'Statistics for Teachers'],
                ['code' => 'MED701', 'name' => 'Action Research in Mathematics Teaching'],
            ],
            // BSHRM (Hotel & Restaurant)
            'BSHRM' => [
                ['code' => 'HR101', 'name' => 'Introduction to Hospitality Industry'],
                ['code' => 'HR102', 'name' => 'Food and Beverage Service'],
                ['code' => 'HR201', 'name' => 'Front Office Operations'],
                ['code' => 'HR202', 'name' => 'Housekeeping Operations'],
                ['code' => 'HR203', 'name' => 'Food Preparation 1'],
                ['code' => 'HR204', 'name' => 'Food Preparation 2'],
                ['code' => 'HR301', 'name' => 'Hospitality Marketing'],
                ['code' => 'HR302', 'name' => 'Restaurant Management'],
                ['code' => 'HR401', 'name' => 'Hospitality Financial Management'],
                ['code' => 'HR501', 'name' => 'Hospitality Research Methods'],
            ],
            // BSPSY
            'BSPSY' => [
                ['code' => 'PSY101', 'name' => 'General Psychology'],
                ['code' => 'PSY102', 'name' => 'Biological Bases of Behavior'],
                ['code' => 'PSY201', 'name' => 'Developmental Psychology'],
                ['code' => 'PSY202', 'name' => 'Social Psychology'],
                ['code' => 'PSY301', 'name' => 'Abnormal Psychology'],
                ['code' => 'PSY302', 'name' => 'Psychological Assessment'],
                ['code' => 'PSY401', 'name' => 'Counseling Psychology'],
                ['code' => 'PSY501', 'name' => 'Psychological Research Methods'],
            ],
            // BSSW
            'BSSW' => [
                ['code' => 'SW101', 'name' => 'Introduction to Social Work'],
                ['code' => 'SW201', 'name' => 'Social Work Practice 1 - Individuals'],
                ['code' => 'SW202', 'name' => 'Social Work Practice 2 - Families'],
                ['code' => 'SW301', 'name' => 'Community Organization'],
                ['code' => 'SW401', 'name' => 'Social Welfare Administration'],
                ['code' => 'SW501', 'name' => 'Social Work Research Methods'],
            ],
        ];

        foreach ($data as $programCode => $subjects) {
            $program = Program::where('code', $programCode)->first();

            if (! $program) {
                continue;
            }

            foreach ($subjects as $subject) {
                $subjectCode = $this->resolveSubjectCode($program->code, $subject['code']);

                Subject::updateOrCreate(
                    ['code' => $subjectCode],
                    [
                        'program_id' => $program->id,
                        'name' => $subject['name'],
                        'year_level' => $this->inferYearLevel($subject['code']),
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    private function inferYearLevel(string $code): int
    {
        preg_match('/(\d{3})/', $code, $matches);

        if (! isset($matches[1])) {
            return 1;
        }

        $hundreds = (int) floor(((int) $matches[1]) / 100);

        return max(1, min(4, $hundreds));
    }

    private function resolveSubjectCode(string $programCode, string $baseCode): string
    {
        if (! isset($this->usedCodes[$baseCode]) && ! Subject::query()->where('code', $baseCode)->exists()) {
            $this->usedCodes[$baseCode] = true;

            return $baseCode;
        }

        $namespacedCode = substr("{$programCode}-{$baseCode}", 0, 20);
        $this->usedCodes[$namespacedCode] = true;

        return $namespacedCode;
    }
}
