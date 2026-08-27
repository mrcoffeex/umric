<?php

namespace Database\Seeders;

use App\Models\EvaluationCriterion;
use App\Models\EvaluationFormat;
use Illuminate\Database\Seeder;

class EvaluationFormatSeeder extends Seeder
{
    /**
     * Seed production-ready defense evaluation formats with criteria.
     */
    public function run(): void
    {
        $this->seedFormat(
            name: 'Outline Defense',
            evaluationType: EvaluationFormat::TYPE_SCORING,
            useWeights: false,
            criteria: [
                ['name' => 'Research problem and objectives', 'max_points' => 100, 'sort_order' => 1],
                ['name' => 'Review of related literature', 'max_points' => 100, 'sort_order' => 2],
                ['name' => 'Methodology and design', 'max_points' => 100, 'sort_order' => 3],
                ['name' => 'Significance and feasibility', 'max_points' => 100, 'sort_order' => 4],
                ['name' => 'Presentation and defense', 'max_points' => 100, 'sort_order' => 5],
            ],
        );

        $this->seedFormat(
            name: 'Final Defense',
            evaluationType: EvaluationFormat::TYPE_SCORING,
            useWeights: true,
            criteria: [
                ['name' => 'Introduction and research problem', 'max_points' => 15, 'sort_order' => 1],
                ['name' => 'Review of related literature', 'max_points' => 15, 'sort_order' => 2],
                ['name' => 'Methodology', 'max_points' => 20, 'sort_order' => 3],
                ['name' => 'Results and discussion', 'max_points' => 25, 'sort_order' => 4],
                ['name' => 'Conclusions and recommendations', 'max_points' => 15, 'sort_order' => 5],
                ['name' => 'Presentation and defense', 'max_points' => 10, 'sort_order' => 6],
            ],
        );

        $this->seedFormat(
            name: 'Title Proposal Checklist',
            evaluationType: EvaluationFormat::TYPE_CHECKLIST,
            useWeights: false,
            criteria: [
                ['name' => 'Title is clear and researchable', 'max_points' => 1, 'sort_order' => 1],
                ['name' => 'Objectives are specific and measurable', 'max_points' => 1, 'sort_order' => 2],
                ['name' => 'Aligned with department research agenda', 'max_points' => 1, 'sort_order' => 3],
                ['name' => 'Ethical considerations identified', 'max_points' => 1, 'sort_order' => 4],
                ['name' => 'Proposed methodology is appropriate', 'max_points' => 1, 'sort_order' => 5],
            ],
        );
    }

    /**
     * @param  list<array{name: string, max_points: int, sort_order: int}>  $criteria
     */
    private function seedFormat(
        string $name,
        string $evaluationType,
        bool $useWeights,
        array $criteria,
    ): void {
        $format = EvaluationFormat::query()->updateOrCreate(
            ['name' => $name],
            [
                'evaluation_type' => $evaluationType,
                'use_weights' => $useWeights,
            ],
        );

        foreach ($criteria as $row) {
            $criterion = EvaluationCriterion::query()
                ->where('evaluation_format_id', $format->id)
                ->where('sort_order', $row['sort_order'])
                ->first();

            $payload = [
                'evaluation_format_id' => $format->id,
                'content' => '<p>'.e($row['name']).'</p>',
                'section_heading' => null,
                'max_points' => $row['max_points'],
                'sort_order' => $row['sort_order'],
            ];

            if ($criterion === null) {
                EvaluationCriterion::query()->create($payload);
            } else {
                $criterion->update($payload);
            }
        }

        $this->command?->info("Evaluation format ready: {$name}");
    }
}
