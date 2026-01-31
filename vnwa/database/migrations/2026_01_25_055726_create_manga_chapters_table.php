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
        Schema::create('manga_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained('mangas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unique(['manga_id', 'slug']); // Slug unique per manga
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manga_chapters');
    }
};
