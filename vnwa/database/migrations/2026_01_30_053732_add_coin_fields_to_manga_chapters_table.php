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
        Schema::table('manga_chapters', function (Blueprint $table) {
            $table->unsignedBigInteger('coin_cost')->default(0)->after('description');
            $table->boolean('is_locked')->default(false)->after('coin_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manga_chapters', function (Blueprint $table) {
            $table->dropColumn(['coin_cost', 'is_locked']);
        });
    }
};
