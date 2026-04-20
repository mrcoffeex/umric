<?php

namespace Database\Seeders;

use App\Models\PanelDefense;
use App\Models\ResearchPaper;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class PanelDefenseSeeder extends Seeder
{
    public function run(): void
    {
        $papers = ResearchPaper::query()->get();
        if ($papers->isEmpty()) {
            return;
        }

        $panelists = User::query()
            ->whereHas('profile', fn ($q) => $q->whereIn('role', ['admin', 'staff', 'faculty']))
            ->get(['id', 'name']);

        if ($panelists->isEmpty()) {
            return;
        }

        foreach ($papers as $paper) {
            if (! fake()->boolean(55)) {
                continue;
            }

            $this->upsertDefense($paper, 'title', $panelists, null);

            if (fake()->boolean(60)) {
                $schedule = now()->addDays(fake()->numberBetween(3, 30))
                    ->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]));

                $this->upsertDefense($paper, 'outline', $panelists, $schedule);

                $paper->update([
                    'step_outline_defense' => $paper->step_outline_defense ?? 'pending',
                    'outline_defense_schedule' => $schedule,
                ]);
            }

            if (fake()->boolean(35)) {
                $schedule = now()->addDays(fake()->numberBetween(31, 90))
                    ->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]));

                $this->upsertDefense($paper, 'final', $panelists, $schedule);

                $paper->update([
                    'step_final_defense' => $paper->step_final_defense ?? 'pending',
                    'final_defense_schedule' => $schedule,
                ]);
            }
        }
    }

    private function upsertDefense(ResearchPaper $paper, string $type, Collection $panelists, $schedule): void
    {
        $memberCount = min($panelists->count(), fake()->numberBetween(2, 3));
        $members = $panelists->shuffle()->take($memberCount)->pluck('name')->values()->all();

        $creator = $panelists->shuffle()->first();

        PanelDefense::updateOrCreate(
            [
                'research_paper_id' => $paper->id,
                'defense_type' => $type,
            ],
            [
                'panel_members' => $members,
                'schedule' => $schedule,
                'notes' => fake()->optional()->sentence(),
                'created_by' => $creator?->id,
            ],
        );
    }
}
