<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->boolean('backup_schedule_enabled')->default(true);
            $table->string('backup_schedule_frequency', 20)->default('daily');
            $table->timestamp('backup_schedule_last_ran_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'backup_schedule_enabled',
                'backup_schedule_frequency',
                'backup_schedule_last_ran_at',
            ]);
        });
    }
};
