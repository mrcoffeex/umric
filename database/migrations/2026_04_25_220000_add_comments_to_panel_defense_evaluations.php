<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('panel_defense_evaluations', function (Blueprint $table) {
            $table->text('comments')->nullable()->after('final_score');
        });
    }

    public function down(): void
    {
        Schema::table('panel_defense_evaluations', function (Blueprint $table) {
            $table->dropColumn('comments');
        });
    }
};
