<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_formats', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 255);
            $table->timestamps();
        });

        $defaultId = (string) Str::ulid();
        $now = now();
        DB::table('evaluation_formats')->insert([
            'id' => $defaultId,
            'name' => 'Default',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->foreignUlid('evaluation_format_id')
                ->nullable()
                ->after('id')
                ->constrained('evaluation_formats')
                ->cascadeOnDelete();
        });

        DB::table('evaluation_criteria')->update(['evaluation_format_id' => $defaultId]);

        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->foreignUlid('evaluation_format_id')->nullable(false)->change();
        });

        Schema::table('panel_defenses', function (Blueprint $table) {
            $table->foreignUlid('evaluation_format_id')
                ->nullable()
                ->after('research_paper_id')
                ->constrained('evaluation_formats');
        });

        DB::table('panel_defenses')->update(['evaluation_format_id' => $defaultId]);

        Schema::table('panel_defenses', function (Blueprint $table) {
            $table->foreignUlid('evaluation_format_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('panel_defenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evaluation_format_id');
        });

        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evaluation_format_id');
        });

        Schema::dropIfExists('evaluation_formats');
    }
};
