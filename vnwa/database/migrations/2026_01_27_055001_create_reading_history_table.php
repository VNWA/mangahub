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
        Schema::create('reading_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('manga_id')->constrained('mangas')->onDelete('cascade');
            $table->foreignId('manga_chapter_id')->nullable()->constrained('manga_chapters')->onDelete('set null');
            $table->integer('chapter_order')->nullable();
            $table->string('chapter_name')->nullable();
            $table->timestamp('last_read_at')->useCurrent();
            $table->timestamps();

            $table->index('user_id');
            $table->index('manga_id');
            $table->index('last_read_at');
            // One reading history per user per manga (latest chapter)
            $table->unique(['user_id', 'manga_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_history');
    }
};
