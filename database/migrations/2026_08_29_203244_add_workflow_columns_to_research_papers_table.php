<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_papers', function (Blueprint $table) {
            $table->foreignUlid('workflow_version_id')
                ->nullable()
                ->after('status')
                ->constrained('workflow_versions')
                ->nullOnDelete();
            $table->json('custom_step_statuses')->nullable()->after('step_hard_bound');
        });
    }

    public function down(): void
    {
        Schema::table('research_papers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workflow_version_id');
            $table->dropColumn('custom_step_statuses');
        });
    }
};
