<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tracking_records DROP CONSTRAINT IF EXISTS tracking_records_status_check');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tracking_records ADD CONSTRAINT tracking_records_status_check CHECK (status IN ('submitted', 'under_review', 'approved', 'presented', 'published', 'archived'))");
    }
};
