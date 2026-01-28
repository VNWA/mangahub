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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->morphs('pageable'); // pageable_id, pageable_type (Manga or MangaChapter)
            $table->integer('views_count')->default(0);
            $table->float('rating')->default(0);
            $table->integer('total_ratings')->default(0);
            $table->integer('comments_count')->default(0);
            $table->timestamps();

            $table->index(['pageable_id', 'pageable_type']);
            $table->unique(['pageable_id', 'pageable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
