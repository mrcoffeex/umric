<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Role lives in user_profiles; this migration is intentionally a no-op
        // kept for clarity and future direct-users-table role caching if needed.
    }

    public function down(): void
    {
        //
    }
};
