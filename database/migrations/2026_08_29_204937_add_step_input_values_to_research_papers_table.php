<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_papers', function (Blueprint $table) {
            $table->json('step_input_values')->nullable()->after('custom_step_statuses');
        });
    }

    public function down(): void
    {
        Schema::table('research_papers', function (Blueprint $table) {
            $table->dropColumn('step_input_values');
        });
    }
};
