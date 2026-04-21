<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Category;
use App\Models\Department;
use App\Models\PanelDefense;
use App\Models\PaperAuthor;
use App\Models\Program;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\Sdg;
use App\Models\Subject;
use App\Models\TrackingRecord;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    private const ADMIN_EMAIL = 'admin@example.com';

    private const STAFF_EMAIL = 'staff@example.com';

    private const FACULTY_COUNT = 10;

    private const CLASSES_PER_FACULTY = 3;

    private const STUDENT_COUNT = 1000;

    private const SCHOOL_YEAR = '2025-2026';

    private const SEMESTER = 1;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = $this->createUserWithProfile('System Administrator', self::ADMIN_EMAIL, 'admin');
        $this->createUserWithProfile('Research Staff', self::STAFF_EMAIL, 'staff');

        $this->call(DepartmentSeeder::class);
        $this->call(SubjectSeeder::class);
        $this->call(SdgSeeder::class);
        $this->call(AgendaSeeder::class);

        $categories = $this->seedCategories();
        $sdgIds = Sdg::query()->where('is_active', true)->pluck('id');
        $agendaIds = Agenda::query()->where('is_active', true)->pluck('id');

        $facultyContexts = $this->createFacultyContexts();
        $classContexts = $this->createFacultyClasses($facultyContexts);
        $studentContexts = $this->createStudentContexts($classContexts);

        $this->createResearchPaperLifecycle(
            $studentContexts,
            $facultyContexts->pluck('user')->values(),
            $categories,
            $sdgIds,
            $agendaIds,
            $admin,
        );
    }

    private function createUserWithProfile(
        string $name,
        string $email,
        string $role,
        ?Department $department = null,
        ?Program $program = null,
    ): User {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
        ]);

        $profileFactory = match ($role) {
            'admin' => UserProfile::factory()->admin(),
            'staff' => UserProfile::factory()->staff(),
            'faculty' => UserProfile::factory()->faculty(),
            default => UserProfile::factory()->student(),
        };

        $profileFactory
            ->forAcademicUnit($department, $program)
            ->create(['user_id' => $user->id]);

        return $user;
    }

    private function seedCategories(): Collection
    {
        $categoryNames = [
            'Computer Science' => 'Research in algorithms, AI, machine learning, and software engineering',
            'Engineering' => 'Applied engineering research across civil, mechanical, and electrical disciplines',
            'Business' => 'Business administration, management, and organizational studies',
            'Science' => 'Natural and physical sciences including biology, chemistry, and physics',
            'Humanities' => 'Literature, philosophy, history, and cultural studies',
            'Medicine' => 'Medical research, clinical trials, and healthcare innovation',
            'Law' => 'Legal research, jurisprudence, and policy analysis',
            'Education' => 'Educational theory, pedagogy, and learning science',
        ];

        return collect($categoryNames)->map(
            fn (string $description, string $name) => Category::query()->updateOrCreate(
                ['slug' => str()->slug($name)],
                [
                    'name' => $name,
                    'description' => $description,
                ],
            ),
        )->values();
    }

    private function createFacultyContexts(): Collection
    {
        $programIds = Subject::query()
            ->where('is_active', true)
            ->select('program_id')
            ->distinct()
            ->pluck('program_id');

        $programs = Program::query()
            ->with('department')
            ->whereIn('id', $programIds)
            ->orderBy('code')
            ->get();

        return collect(range(1, self::FACULTY_COUNT))->map(function (int $index) use ($programs) {
            /** @var Program $program */
            $program = $programs->get(($index - 1) % $programs->count());
            $faculty = $this->createUserWithProfile(
                sprintf('Faculty %02d', $index),
                sprintf('faculty%02d@example.com', $index),
                'faculty',
                $program->department,
                $program,
            );

            return [
                'user' => $faculty,
                'program' => $program,
                'department' => $program->department,
            ];
        })->values();
    }

    private function createFacultyClasses(Collection $facultyContexts): Collection
    {
        return $facultyContexts->flatMap(function (array $facultyContext, int $facultyIndex) {
            /** @var User $faculty */
            $faculty = $facultyContext['user'];
            /** @var Program $program */
            $program = $facultyContext['program'];
            /** @var Department $department */
            $department = $facultyContext['department'];

            $subjects = Subject::query()
                ->where('program_id', $program->id)
                ->where('is_active', true)
                ->orderBy('year_level')
                ->orderBy('code')
                ->get();

            $selectedSubjects = collect(range(1, self::CLASSES_PER_FACULTY))
                ->map(fn (int $yearLevel) => $subjects->firstWhere('year_level', $yearLevel))
                ->filter();

            if ($selectedSubjects->count() < self::CLASSES_PER_FACULTY) {
                $selectedSubjects = $selectedSubjects
                    ->concat($subjects->reject(fn (Subject $subject) => $selectedSubjects->contains('id', $subject->id)))
                    ->take(self::CLASSES_PER_FACULTY)
                    ->values();
            }

            return collect(range(1, self::CLASSES_PER_FACULTY))->map(function (int $slot) use ($faculty, $program, $department, $selectedSubjects, $facultyIndex) {
                /** @var Subject|null $subject */
                $subject = $selectedSubjects->get($slot - 1);
                $yearLevel = $subject?->year_level ?? $slot;
                $section = chr(64 + $slot);
                $classCode = sprintf(
                    '%s%s%s-S%d-%s',
                    $program->code,
                    $yearLevel,
                    $section,
                    self::SEMESTER,
                    $this->shortSchoolYear(),
                );

                $class = SchoolClass::factory()
                    ->forFaculty($faculty)
                    ->forTerm(self::SCHOOL_YEAR, self::SEMESTER)
                    ->identifiedAs("{$program->code} {$yearLevel}-{$section}", $classCode, $section)
                    ->create([
                        'description' => sprintf(
                            'Seeded class %d for %s under %s',
                            ($facultyIndex * self::CLASSES_PER_FACULTY) + $slot,
                            $program->name,
                            $faculty->name,
                        ),
                        'join_code' => null,
                    ]);

                $class->generateAndSetJoinCode();

                if ($subject) {
                    $class->subjects()->sync([$subject->id]);
                }

                return [
                    'class' => $class,
                    'faculty' => $faculty,
                    'program' => $program,
                    'department' => $department,
                    'subject' => $subject,
                ];
            });
        })->values();
    }

    private function createStudentContexts(Collection $classContexts): Collection
    {
        return collect(range(1, self::STUDENT_COUNT))->map(function (int $index) use ($classContexts) {
            $classContext = $classContexts->get(($index - 1) % $classContexts->count());

            /** @var Department $department */
            $department = $classContext['department'];
            /** @var Program $program */
            $program = $classContext['program'];
            /** @var SchoolClass $class */
            $class = $classContext['class'];

            $student = $this->createUserWithProfile(
                sprintf('Student %04d', $index),
                sprintf('student%04d@example.com', $index),
                'student',
                $department,
                $program,
            );

            $class->members()->attach($student->id, [
                'joined_at' => now()->subDays(fake()->numberBetween(1, 120)),
            ]);

            return [
                'user' => $student,
                'class' => $class,
                'faculty' => $classContext['faculty'],
                'program' => $program,
                'department' => $department,
            ];
        })->values();
    }

    private function createResearchPaperLifecycle(
        Collection $studentContexts,
        Collection $facultyUsers,
        Collection $categories,
        Collection $sdgIds,
        Collection $agendaIds,
        User $admin,
    ): void {
        $studentContexts->each(function (array $studentContext) use ($facultyUsers, $categories, $sdgIds, $agendaIds, $admin) {
            /** @var User $student */
            $student = $studentContext['user'];
            /** @var SchoolClass $class */
            $class = $studentContext['class'];
            /** @var User $classFaculty */
            $classFaculty = $studentContext['faculty'];
            /** @var Category $category */
            $category = $categories->random();

            $reviewers = $this->pickReviewers($facultyUsers, $classFaculty);
            $submittedAt = Carbon::instance(fake()->dateTimeBetween('-8 months', '-7 days'));

            $paper = ResearchPaper::factory()
                ->submittedBy($student)
                ->forClass($class)
                ->assignedTo($reviewers['adviser'], $reviewers['statistician'])
                ->create([
                    'category_id' => $category->id,
                    'sdg_ids' => $this->randomSubset($sdgIds, 3),
                    'agenda_ids' => $this->randomSubset($agendaIds, 2),
                    'status' => 'submitted',
                    'submission_date' => $submittedAt->toDateString(),
                    'publication_date' => null,
                    'keywords' => collect(fake()->words(fake()->numberBetween(3, 6)))->implode(', '),
                    'views' => fake()->numberBetween(0, 300),
                ]);

            PaperAuthor::create([
                'research_paper_id' => $paper->id,
                'user_id' => $student->id,
                'author_order' => 1,
            ]);

            $timeline = $submittedAt->copy()->setTime(9, 0);
            $this->recordTracking(
                $paper,
                'title_proposal',
                'Title Proposal Submitted',
                'submitted',
                null,
                $student,
                'Initial title proposal submitted by the student.',
                null,
                $timeline,
            );

            $this->progressPaper($paper, $admin, $facultyUsers, $timeline->copy()->addDays(fake()->numberBetween(2, 10)));
        });
    }

    private function progressPaper(ResearchPaper $paper, User $admin, Collection $facultyUsers, Carbon $timeline): void
    {
        $ricStatus = fake()->boolean(88) ? 'approved' : fake()->randomElement(['pending', 'rejected']);
        $this->applyAdminStep($paper, $admin, 'ric_review', $ricStatus, $timeline, 'RIC review updated by admin.');

        if ($ricStatus !== 'approved') {
            return;
        }

        $timeline = $timeline->copy()->addDays(fake()->numberBetween(3, 10));
        $plagiarismStatus = fake()->boolean(82) ? 'passed' : 'failed';
        $this->applyAdminStep(
            $paper,
            $admin,
            'plagiarism_check',
            $plagiarismStatus,
            $timeline,
            'Plagiarism screening completed.',
            plagiarismScore: fake()->randomFloat(2, 3, 28),
        );

        if ($plagiarismStatus !== 'passed') {
            return;
        }

        $timeline = $timeline->copy()->addDays(fake()->numberBetween(5, 14));
        $outlineStatus = fake()->boolean(74) ? 'passed' : 'pending';
        $outlineSchedule = $outlineStatus === 'passed'
            ? $timeline->copy()->subDays(fake()->numberBetween(1, 3))->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]))
            : now()->addDays(fake()->numberBetween(3, 45))->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]));

        $this->createDefensePanel($paper, $admin, $facultyUsers, 'outline', $outlineSchedule);
        $this->applyAdminStep(
            $paper,
            $admin,
            'outline_defense',
            $outlineStatus,
            $timeline,
            'Outline defense reviewed by admin.',
            schedule: $outlineSchedule,
        );

        if ($outlineStatus !== 'passed') {
            return;
        }

        $timeline = $timeline->copy()->addDays(fake()->numberBetween(3, 10));
        $ratingStatus = fake()->boolean(78) ? 'rated' : 'pending';
        $this->applyAdminStep(
            $paper,
            $admin,
            'rating',
            $ratingStatus,
            $timeline,
            'Research paper rating updated.',
            grade: $ratingStatus === 'rated' ? fake()->randomFloat(2, 1.00, 2.75) : null,
        );

        if ($ratingStatus !== 'rated') {
            return;
        }

        $timeline = $timeline->copy()->addDays(fake()->numberBetween(5, 12));
        $finalManuscriptStatus = fake()->boolean(76) ? 'submitted' : 'pending';
        $this->applyAdminStep(
            $paper,
            $admin,
            'final_manuscript',
            $finalManuscriptStatus,
            $timeline,
            'Final manuscript status updated.',
        );

        if ($finalManuscriptStatus !== 'submitted') {
            return;
        }

        $timeline = $timeline->copy()->addDays(fake()->numberBetween(4, 12));
        $finalDefenseStatus = fake()->boolean(68) ? 'passed' : 'pending';
        $finalDefenseSchedule = $finalDefenseStatus === 'passed'
            ? $timeline->copy()->subDays(fake()->numberBetween(1, 2))->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]))
            : now()->addDays(fake()->numberBetween(5, 60))->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]));

        $this->createDefensePanel($paper, $admin, $facultyUsers, 'final', $finalDefenseSchedule);
        $this->applyAdminStep(
            $paper,
            $admin,
            'final_defense',
            $finalDefenseStatus,
            $timeline,
            'Final defense status updated.',
            schedule: $finalDefenseSchedule,
        );

        if ($finalDefenseStatus !== 'passed') {
            return;
        }

        $timeline = $timeline->copy()->addDays(fake()->numberBetween(3, 9));
        $hardBoundStatus = fake()->boolean(72) ? 'submitted' : 'ongoing';
        $this->applyAdminStep(
            $paper,
            $admin,
            'hard_bound',
            $hardBoundStatus,
            $timeline,
            'Hard bound submission updated.',
        );

        if ($hardBoundStatus === 'submitted') {
            $paper->update([
                'publication_date' => $timeline->toDateString(),
            ]);
        }
    }

    private function applyAdminStep(
        ResearchPaper $paper,
        User $admin,
        string $step,
        string $status,
        CarbonInterface $occurredAt,
        ?string $notes = null,
        ?float $grade = null,
        ?CarbonInterface $schedule = null,
        ?float $plagiarismScore = null,
    ): void {
        $oldStatus = null;
        $updateData = [];
        $metadata = [];

        switch ($step) {
            case 'ric_review':
                $oldStatus = $paper->step_ric_review;
                $updateData['step_ric_review'] = $status;

                if ($status === 'approved') {
                    $updateData['current_step'] = 'plagiarism_check';
                    $updateData['step_plagiarism'] = 'pending';
                } else {
                    $updateData['current_step'] = 'ric_review';
                }
                break;

            case 'plagiarism_check':
                $oldStatus = $paper->step_plagiarism;
                $updateData['step_plagiarism'] = $status;

                if ($plagiarismScore !== null) {
                    $updateData['plagiarism_score'] = $plagiarismScore;
                    $metadata['plagiarism_score'] = $plagiarismScore;
                }

                if ($status === 'failed') {
                    $updateData['plagiarism_attempts'] = $paper->plagiarism_attempts + 1;
                    $metadata['attempt'] = $updateData['plagiarism_attempts'];
                    $updateData['current_step'] = 'plagiarism_check';
                }

                if ($status === 'passed') {
                    $updateData['current_step'] = 'outline_defense';
                    $updateData['step_outline_defense'] = 'pending';
                }
                break;

            case 'outline_defense':
                $oldStatus = $paper->step_outline_defense;
                $updateData['step_outline_defense'] = $status;

                if ($schedule) {
                    $updateData['outline_defense_schedule'] = $schedule;
                    $metadata['schedule'] = $schedule->toDateTimeString();
                }

                if ($status === 'passed') {
                    $updateData['current_step'] = 'rating';
                    $updateData['step_rating'] = 'pending';
                } else {
                    $updateData['current_step'] = 'outline_defense';
                }
                break;

            case 'rating':
                $oldStatus = $paper->step_rating;
                $updateData['step_rating'] = $status;

                if ($grade !== null) {
                    $updateData['grade'] = $grade;
                    $metadata['grade'] = $grade;
                }

                if ($status === 'rated') {
                    $updateData['current_step'] = 'final_manuscript';
                    $updateData['step_final_manuscript'] = 'pending';
                } else {
                    $updateData['current_step'] = 'rating';
                }
                break;

            case 'final_manuscript':
                $oldStatus = $paper->step_final_manuscript;
                $updateData['step_final_manuscript'] = $status;

                if ($status === 'submitted') {
                    $updateData['current_step'] = 'final_defense';
                    $updateData['step_final_defense'] = 'pending';
                } else {
                    $updateData['current_step'] = 'final_manuscript';
                }
                break;

            case 'final_defense':
                $oldStatus = $paper->step_final_defense;
                $updateData['step_final_defense'] = $status;

                if ($schedule) {
                    $updateData['final_defense_schedule'] = $schedule;
                    $metadata['schedule'] = $schedule->toDateTimeString();
                }

                if ($status === 'passed') {
                    $updateData['current_step'] = 'hard_bound';
                    $updateData['step_hard_bound'] = 'ongoing';
                } else {
                    $updateData['current_step'] = 'final_defense';
                }
                break;

            case 'hard_bound':
                $oldStatus = $paper->step_hard_bound;
                $updateData['step_hard_bound'] = $status;

                if ($status === 'submitted') {
                    $updateData['current_step'] = 'completed';
                    $updateData['status'] = 'published';
                } else {
                    $updateData['current_step'] = 'hard_bound';
                }
                break;
        }

        if (! array_key_exists('status', $updateData)) {
            $updateData['status'] = $this->legacyStatusForStep($updateData['current_step'] ?? $paper->current_step);
        }

        $paper->update($updateData);

        $this->recordTracking(
            $paper,
            $step,
            ucfirst(str_replace('_', ' ', $step)).': '.ucfirst($status),
            $status,
            $oldStatus,
            $admin,
            $notes,
            $metadata ?: null,
            $occurredAt,
        );
    }

    private function createDefensePanel(
        ResearchPaper $paper,
        User $admin,
        Collection $facultyUsers,
        string $type,
        CarbonInterface $schedule,
    ): void {
        $panelMembers = $facultyUsers
            ->shuffle()
            ->take(min(3, $facultyUsers->count()))
            ->pluck('name')
            ->values()
            ->all();

        PanelDefense::factory()
            ->forPaper($paper)
            ->createdBy($admin)
            ->ofType($type)
            ->withMembers($panelMembers)
            ->create([
                'schedule' => $schedule,
                'notes' => ucfirst($type).' defense panel scheduled by admin.',
            ]);
    }

    private function recordTracking(
        ResearchPaper $paper,
        string $step,
        string $action,
        string $status,
        ?string $oldStatus,
        User $performedBy,
        ?string $notes,
        ?array $metadata,
        CarbonInterface $occurredAt,
    ): void {
        TrackingRecord::create([
            'research_paper_id' => $paper->id,
            'step' => $step,
            'action' => $action,
            'status' => $status,
            'old_status' => $oldStatus,
            'notes' => $notes,
            'metadata' => $metadata,
            'updated_by' => $performedBy->id,
            'status_changed_at' => $occurredAt,
        ]);
    }

    private function pickReviewers(Collection $facultyUsers, User $classFaculty): array
    {
        $statistician = $facultyUsers
            ->reject(fn (User $faculty) => $faculty->id === $classFaculty->id)
            ->shuffle()
            ->first() ?? $classFaculty;

        return [
            'adviser' => $classFaculty,
            'statistician' => $statistician,
        ];
    }

    private function randomSubset(Collection $values, int $max): array
    {
        if ($values->isEmpty()) {
            return [];
        }

        $count = fake()->numberBetween(1, min($max, $values->count()));

        return $values->shuffle()->take($count)->values()->all();
    }

    private function legacyStatusForStep(string $step): string
    {
        return match ($step) {
            'title_proposal', 'ric_review' => 'submitted',
            'plagiarism_check' => 'under_review',
            'outline_defense', 'rating', 'final_manuscript' => 'approved',
            'final_defense', 'hard_bound' => 'presented',
            'completed' => 'published',
            default => 'submitted',
        };
    }

    private function shortSchoolYear(): string
    {
        [$startYear, $endYear] = explode('-', self::SCHOOL_YEAR);

        return substr($startYear, -2).substr($endYear, -2);
    }
}
