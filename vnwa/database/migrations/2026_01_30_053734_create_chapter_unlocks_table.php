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
        Schema::create('chapter_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manga_chapter_id')->constrained('manga_chapters')->cascadeOnDelete();
            $table->unsignedBigInteger('coin_spent'); // Số coin đã tiêu
            $table->timestamps();

            // Một user chỉ có thể unlock một chapter một lần
            $table->unique(['user_id', 'manga_chapter_id']);
            $table->index('user_id');
            $table->index('manga_chapter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapter_unlocks');
    }
};
