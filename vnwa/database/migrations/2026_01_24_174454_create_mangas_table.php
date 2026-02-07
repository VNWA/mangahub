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
        Schema::create('mangas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_author_id')->nullable()->constrained('manga_authors')->onDelete('set null');
            $table->foreignId('manga_badge_id')->nullable()->constrained('manga_badges')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('avatar')->nullable();
            $table->string('name');
            $table->string('other_name')->nullable();
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->enum('status', ['ongoing', 'completed', 'hiatus', 'cancelled'])->default('ongoing');
            $table->integer('total_views')->default(0);
            $table->integer('monthly_views')->default(0);
            $table->integer('weekly_views')->default(0);
            $table->integer('daily_views')->default(0);
            $table->integer('total_follow')->default(0);
            $table->float('rating')->default(0);
            $table->integer('total_ratings')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mangas');
    }
};
