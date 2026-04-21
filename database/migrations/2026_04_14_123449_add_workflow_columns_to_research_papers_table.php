<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('research_papers', function (Blueprint $table) {
            // Link to class
            $table->foreignUlid('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete()->after('user_id');
            // Assigned members
            $table->foreignUlid('adviser_id')->nullable()->constrained('users')->nullOnDelete()->after('school_class_id');
            $table->foreignUlid('statistician_id')->nullable()->constrained('users')->nullOnDelete()->after('adviser_id');
            // Workflow step tracking
            $table->string('current_step')->default('title_proposal')->after('status');
            // Step statuses
            $table->string('step_ric_review')->nullable()->after('current_step');
            $table->string('step_plagiarism')->nullable()->after('step_ric_review');
            $table->unsignedTinyInteger('plagiarism_attempts')->default(0)->after('step_plagiarism');
            $table->decimal('plagiarism_score', 5, 2)->nullable()->after('plagiarism_attempts');
            $table->string('step_outline_defense')->nullable()->after('plagiarism_score');
            $table->dateTime('outline_defense_schedule')->nullable()->after('step_outline_defense');
            $table->string('step_rating')->nullable()->after('outline_defense_schedule');
            $table->decimal('grade', 5, 2)->nullable()->after('step_rating');
            $table->string('step_final_manuscript')->nullable()->after('grade');
            $table->string('step_final_defense')->nullable()->after('step_final_manuscript');
            $table->dateTime('final_defense_schedule')->nullable()->after('step_final_defense');
            $table->string('step_hard_bound')->nullable()->after('final_defense_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('research_papers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_class_id');
            $table->dropConstrainedForeignId('adviser_id');
            $table->dropConstrainedForeignId('statistician_id');
            $table->dropColumn([
                'current_step', 'step_ric_review', 'step_plagiarism', 'plagiarism_attempts',
                'plagiarism_score', 'step_outline_defense', 'outline_defense_schedule',
                'step_rating', 'grade', 'step_final_manuscript', 'step_final_defense',
                'final_defense_schedule', 'step_hard_bound',
            ]);
        });
    }
};
