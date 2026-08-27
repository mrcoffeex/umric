<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_transmission_histories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('document_transmission_id');
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 64);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('document_transmission_id', 'dth_transmission_id_fk')
                ->references('id')
                ->on('document_transmissions')
                ->cascadeOnDelete();
            $table->index(
                ['document_transmission_id', 'created_at'],
                'dth_transmission_created_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_transmission_histories');
    }
};
