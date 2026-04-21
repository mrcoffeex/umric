<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('research_papers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('tracking_id')->unique();
            $table->enum('status', ['submitted', 'under_review', 'approved', 'presented', 'published', 'archived'])->default('submitted');
            $table->date('submission_date');
            $table->date('publication_date')->nullable();
            $table->string('keywords')->nullable();
            $table->integer('views')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['status', 'user_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_papers');
    }
};
