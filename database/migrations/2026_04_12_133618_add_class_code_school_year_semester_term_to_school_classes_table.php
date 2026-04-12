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
        Schema::table('school_classes', function (Blueprint $table) {
            $table->string('class_code', 30)->nullable()->after('name');
            $table->string('school_year', 9)->nullable()->after('class_code');
            $table->tinyInteger('semester')->unsigned()->nullable()->after('school_year');
            $table->string('term', 20)->nullable()->after('semester');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropColumn(['class_code', 'school_year', 'semester', 'term']);
        });
    }
};
