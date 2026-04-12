<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ResearchPaper;
use App\Models\TrackingRecord;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DepartmentSeeder::class);
        $this->call(SubjectSeeder::class);
        $this->call(SchoolClassSeeder::class);
        $this->call(SdgSeeder::class);
        $this->call(AgendaSeeder::class);

        // Create admin/test user
        $admin = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        UserProfile::factory()->admin()->create(['user_id' => $admin->id]);

        // Create additional users with various roles
        $users = User::factory(5)->create()->each(function (User $user) {
            UserProfile::factory()->create(['user_id' => $user->id]);
        });

        // Create categories with unique names
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

        $categories = collect();
        foreach ($categoryNames as $name => $description) {
            $categories->push(Category::create([
                'name' => $name,
                'slug' => str()->slug($name),
                'description' => $description,
            ]));
        }

        // Create research papers for the admin user
        $statuses = ['submitted', 'under_review', 'approved', 'presented', 'published'];
        $allUsers = $users->push($admin);

        foreach ($allUsers as $user) {
            $paperCount = $user->id === $admin->id ? 8 : fake()->numberBetween(1, 4);

            ResearchPaper::factory($paperCount)
                ->recycle($categories->random(3))
                ->create(['user_id' => $user->id])
                ->each(function (ResearchPaper $paper) use ($allUsers, $statuses) {
                    // Add co-authors
                    $coAuthors = $allUsers->except($paper->user_id)->random(fake()->numberBetween(0, 2));
                    foreach ($coAuthors as $index => $author) {
                        $paper->authors()->attach($author->id, ['author_order' => $index + 1]);
                    }

                    // Create tracking records for status history
                    $currentStatusIndex = array_search($paper->status, $statuses);
                    $statusDate = $paper->submission_date ?? now()->subMonths(6);

                    for ($i = 0; $i <= $currentStatusIndex; $i++) {
                        TrackingRecord::create([
                            'research_paper_id' => $paper->id,
                            'status' => $statuses[$i],
                            'notes' => match ($statuses[$i]) {
                                'submitted' => 'Paper submitted for review',
                                'under_review' => 'Paper assigned to reviewers',
                                'approved' => 'Paper approved after peer review',
                                'presented' => 'Paper presented at conference',
                                'published' => 'Paper published in journal',
                                default => 'Status updated',
                            },
                            'updated_by' => $paper->user_id,
                            'status_changed_at' => $statusDate->copy()->addDays($i * fake()->numberBetween(7, 30)),
                        ]);
                    }
                });
        }
    }
}
