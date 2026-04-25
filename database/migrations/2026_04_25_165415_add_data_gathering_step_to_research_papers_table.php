<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add data gathering step; remove plagiarism as an active pipeline step in favor of
     * outline_defense → data_gathering → rating. Existing rows stuck on plagiarism_check
     * are moved to outline_defense.
     */
    public function up(): void
    {
        Schema::table('research_papers', function (Blueprint $table) {
            $table->string('step_data_gathering')->nullable()->after('outline_defense_schedule');
        });

        DB::table('research_papers')
            ->where('current_step', 'plagiarism_check')
            ->update(['current_step' => 'outline_defense']);
    }

    public function down(): void
    {
        Schema::table('research_papers', function (Blueprint $table) {
            $table->dropColumn('step_data_gathering');
        });
    }
};
