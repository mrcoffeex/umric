<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_transmission_items', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('label');
            $table->string('file_name')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
            $table->string('disk', 32)->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('document_transmission_items', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'file_name', 'file_size', 'disk']);
        });
    }
};
