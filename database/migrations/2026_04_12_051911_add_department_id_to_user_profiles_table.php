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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->foreignUlid('department_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignUlid('program_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->string('avatar_disk', 20)->default('local')->after('profile_photo');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['program_id']);
            $table->dropColumn(['department_id', 'program_id', 'avatar_disk']);
        });
    }
};
