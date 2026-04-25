<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_formats', function (Blueprint $table) {
            $table->string('evaluation_type', 20)->default('scoring')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_formats', function (Blueprint $table) {
            $table->dropColumn('evaluation_type');
        });
    }
};
