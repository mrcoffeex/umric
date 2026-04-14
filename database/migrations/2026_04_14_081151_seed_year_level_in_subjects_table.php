<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $subjects = DB::table('subjects')->whereNull('year_level')->get(['id', 'code']);

        foreach ($subjects as $subject) {
            preg_match('/(\d)/', $subject->code, $matches);
            if (! empty($matches[1])) {
                $yearLevel = (int) $matches[1];
                if ($yearLevel >= 1 && $yearLevel <= 5) {
                    DB::table('subjects')->where('id', $subject->id)->update(['year_level' => $yearLevel]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('subjects')->update(['year_level' => null]);
    }
};
