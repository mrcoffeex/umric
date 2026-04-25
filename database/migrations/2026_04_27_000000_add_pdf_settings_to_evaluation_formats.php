<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_formats', function (Blueprint $table) {
            $table->json('pdf_settings')->nullable()->after('use_weights');
        });

        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->string('section_heading', 500)->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_formats', function (Blueprint $table) {
            $table->dropColumn('pdf_settings');
        });

        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->dropColumn('section_heading');
        });
    }
};
