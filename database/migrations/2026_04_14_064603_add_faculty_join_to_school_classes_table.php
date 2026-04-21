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
            $table->foreignUlid('faculty_id')->nullable()->constrained('users')->nullOnDelete()->after('program_id');
            $table->string('join_code', 10)->nullable()->unique()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropForeign(['faculty_id']);
            $table->dropColumn(['faculty_id', 'join_code']);
        });
    }
};
