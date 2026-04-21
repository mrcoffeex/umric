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
            $table->dropConstrainedForeignId('sdg_id');
            $table->dropConstrainedForeignId('agenda_id');
            $table->json('sdg_ids')->nullable()->after('category_id');
            $table->json('agenda_ids')->nullable()->after('sdg_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('research_papers', function (Blueprint $table) {
            $table->dropColumn(['sdg_ids', 'agenda_ids']);
            $table->foreignUlid('sdg_id')->nullable()->constrained('sdgs')->nullOnDelete();
            $table->foreignUlid('agenda_id')->nullable()->constrained('agendas')->nullOnDelete();
        });
    }
};
