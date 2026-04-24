<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 500);
            $table->unsignedTinyInteger('max_points');
            $table->unsignedSmallInteger('sort_order');
            $table->timestamps();
        });

        $labels = config('defense_evaluation.criteria_labels', [
            1 => 'Theoretical Rigor and Literature Foundation',
            2 => 'Methodology and Feasibility',
            3 => 'Organization and Clarity of Presentation',
            4 => 'Significance and Potential Contribution',
        ]);

        $ids = [];
        for ($i = 1; $i <= 4; $i++) {
            $ids[$i] = (string) Str::ulid();
        }

        $now = now();
        for ($i = 1; $i <= 4; $i++) {
            DB::table('evaluation_criteria')->insert([
                'id' => $ids[$i],
                'name' => (string) ($labels[$i] ?? "Criterion {$i}"),
                'max_points' => 25,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('panel_defense_evaluations', function (Blueprint $table) {
            $table->json('line_items')->nullable()->after('evaluator_id');
        });

        $ordered = array_values($ids);

        foreach (DB::table('panel_defense_evaluations')->orderBy('id')->get() as $row) {
            $lineItems = [];
            for ($i = 0; $i < 4; $i++) {
                $key = 'criteria_'.($i + 1);
                $raw = (int) ($row->{$key} ?? 0);
                $max = 25;
                $lineItems[] = [
                    'criterion_id' => $ordered[$i],
                    'name' => (string) ($labels[$i + 1] ?? 'Criterion '.($i + 1)),
                    'max_points' => $max,
                    'score' => min($max, max(0, (int) round($raw * 2.5))),
                ];
            }

            $sum = 0;
            foreach ($lineItems as $l) {
                $sum += $l['score'];
            }

            DB::table('panel_defense_evaluations')
                ->where('id', $row->id)
                ->update([
                    'line_items' => json_encode($lineItems),
                    'final_score' => $sum,
                ]);
        }

        Schema::table('panel_defense_evaluations', function (Blueprint $table) {
            $table->dropColumn(['criteria_1', 'criteria_2', 'criteria_3', 'criteria_4']);
        });
    }

    public function down(): void
    {
        Schema::table('panel_defense_evaluations', function (Blueprint $table) {
            $table->unsignedTinyInteger('criteria_1')->default(0);
            $table->unsignedTinyInteger('criteria_2')->default(0);
            $table->unsignedTinyInteger('criteria_3')->default(0);
            $table->unsignedTinyInteger('criteria_4')->default(0);
        });

        foreach (DB::table('panel_defense_evaluations')->orderBy('id')->get() as $row) {
            $lineItems = json_decode((string) $row->line_items, true);
            if (! is_array($lineItems)) {
                $lineItems = [];
            }
            $c = [0, 0, 0, 0];
            for ($i = 0; $i < 4; $i++) {
                if (isset($lineItems[$i]) && is_array($lineItems[$i])) {
                    $c[$i] = (int) round(((int) ($lineItems[$i]['score'] ?? 0) / 25) * 10);
                }
            }
            $sum = array_sum($c);

            DB::table('panel_defense_evaluations')
                ->where('id', $row->id)
                ->update([
                    'criteria_1' => $c[0],
                    'criteria_2' => $c[1],
                    'criteria_3' => $c[2],
                    'criteria_4' => $c[3],
                    'final_score' => $sum,
                ]);
        }

        Schema::table('panel_defense_evaluations', function (Blueprint $table) {
            $table->dropColumn('line_items');
        });

        Schema::dropIfExists('evaluation_criteria');
    }
};
