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
            $table->renameColumn('description', 'abstract');
            $table->foreignUlid('sdg_id')->nullable()->constrained('sdgs')->nullOnDelete();
            $table->foreignUlid('agenda_id')->nullable()->constrained('agendas')->nullOnDelete();
            $table->json('proponents')->nullable();
            $table->foreignUlid('category_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('research_papers', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
            $table->dropConstrainedForeignId('agenda_id');
            $table->dropConstrainedForeignId('sdg_id');
            $table->dropColumn('proponents');
            $table->renameColumn('abstract', 'description');
        });
    }
};
