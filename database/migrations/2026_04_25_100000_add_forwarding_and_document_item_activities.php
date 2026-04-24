<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_transmissions', function (Blueprint $table) {
            $table->foreignUlid('forwarded_from_id')
                ->nullable()
                ->after('receiver_id')
                ->constrained('document_transmissions')
                ->nullOnDelete();
        });

        Schema::table('document_transmission_items', function (Blueprint $table) {
            $table->foreignUlid('source_item_id')
                ->nullable()
                ->after('document_transmission_id')
                ->constrained('document_transmission_items')
                ->nullOnDelete();
        });

        Schema::create('document_transmission_item_activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('document_transmission_item_id')
                ->constrained('document_transmission_items')
                ->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 64);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['document_transmission_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_transmission_item_activities');

        Schema::table('document_transmission_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_item_id');
        });

        Schema::table('document_transmissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('forwarded_from_id');
        });
    }
};
