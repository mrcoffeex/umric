<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('name');
        });

        DB::table('evaluation_criteria')->orderBy('id')->each(function (object $row): void {
            $name = trim((string) $row->name);
            $html = $name === ''
                ? '<p></p>'
                : '<p>'.e($name, false).'</p>';

            DB::table('evaluation_criteria')->where('id', $row->id)->update(['content' => $html]);
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }
};
