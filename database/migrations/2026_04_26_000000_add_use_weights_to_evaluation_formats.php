<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_formats', function (Blueprint $table) {
            $table->boolean('use_weights')
                ->default(false)
                ->after('evaluation_type');
        });

        // Existing scoring rubrics use per-criterion weights that sum to 100.
        DB::table('evaluation_formats')
            ->where('evaluation_type', 'scoring')
            ->update(['use_weights' => true]);
    }

    public function down(): void
    {
        Schema::table('evaluation_formats', function (Blueprint $table) {
            $table->dropColumn('use_weights');
        });
    }
};
