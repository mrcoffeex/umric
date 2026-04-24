<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panel_defense_evaluations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('panel_defense_id')->constrained('panel_defenses')->cascadeOnDelete();
            $table->foreignUlid('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('criteria_1');
            $table->unsignedTinyInteger('criteria_2');
            $table->unsignedTinyInteger('criteria_3');
            $table->unsignedTinyInteger('criteria_4');
            $table->unsignedSmallInteger('final_score');
            $table->timestamps();

            $table->unique(['panel_defense_id', 'evaluator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_defense_evaluations');
    }
};
