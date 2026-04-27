<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_brandings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('tagline', 500)->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_brandings');
    }
};
