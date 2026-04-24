<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_transmissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->text('purpose');
            $table->string('share_token', 64)->unique();
            $table->string('status', 32)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['receiver_id', 'status']);
            $table->index(['sender_id', 'status']);
        });

        Schema::create('document_transmission_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('document_transmission_id')
                ->constrained('document_transmissions')
                ->cascadeOnDelete();
            $table->string('label');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_transmission_items');
        Schema::dropIfExists('document_transmissions');
    }
};
