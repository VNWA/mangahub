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
        Schema::create('server_chapter_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_server_id')->constrained('manga_servers')->cascadeOnDelete();
            $table->foreignId('manga_chapter_id')->constrained('manga_chapters')->cascadeOnDelete();
            $table->json('urls');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_chapter_contents');
    }
};
