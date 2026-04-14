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
        Schema::table('tracking_records', function (Blueprint $table) {
            $table->string('step')->nullable()->after('research_paper_id');
            $table->string('action')->nullable()->after('step');
            $table->string('old_status')->nullable()->after('action');
            $table->json('metadata')->nullable()->after('notes'); // stores grade, schedule, attempt, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracking_records', function (Blueprint $table) {
            $table->dropColumn(['step', 'action', 'old_status', 'metadata']);
        });
    }
};
