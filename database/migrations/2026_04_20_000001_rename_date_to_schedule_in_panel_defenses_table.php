<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('panel_defenses', function (Blueprint $table) {
            $table->renameColumn('date', 'schedule');
        });

        Schema::table('panel_defenses', function (Blueprint $table) {
            $table->dateTime('schedule')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('panel_defenses', function (Blueprint $table) {
            $table->date('schedule')->nullable()->change();
        });

        Schema::table('panel_defenses', function (Blueprint $table) {
            $table->renameColumn('schedule', 'date');
        });
    }
};
