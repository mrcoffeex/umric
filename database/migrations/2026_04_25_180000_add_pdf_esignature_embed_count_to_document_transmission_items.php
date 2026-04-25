<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_transmission_items', function (Blueprint $table) {
            $table->unsignedInteger('pdf_esignature_embed_count')->default(0)->after('received_at');
        });
    }

    public function down(): void
    {
        Schema::table('document_transmission_items', function (Blueprint $table) {
            $table->dropColumn('pdf_esignature_embed_count');
        });
    }
};
