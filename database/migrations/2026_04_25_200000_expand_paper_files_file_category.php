<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow defense-specific document categories (was DB enum / check).
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE paper_files DROP CONSTRAINT IF EXISTS paper_files_file_category_check');
        }

        Schema::table('paper_files', function (Blueprint $table) {
            $table->string('file_category', 64)->default('paper')->change();
        });
    }

    public function down(): void
    {
        Schema::table('paper_files', function (Blueprint $table) {
            $table->enum('file_category', ['paper', 'presentation', 'supplementary', 'data', 'outline_defense', 'final_defense'])->default('paper')->change();
        });
    }
};
