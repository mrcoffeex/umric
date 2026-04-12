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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('role', ['student', 'faculty', 'staff', 'admin'])->default('student');
            $table->enum('department', ['computer_science', 'engineering', 'business', 'science', 'humanities', 'other'])->nullable();
            $table->string('specialization')->nullable();
            $table->string('institution')->nullable();
            $table->string('degree')->nullable();
            $table->string('graduation_year')->nullable();
            $table->text('bio')->nullable();
            $table->string('profile_photo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
