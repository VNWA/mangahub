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
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', ['deposit', 'spend']); // deposit: nạp, spend: tiêu
            $table->unsignedBigInteger('amount'); // Số coin
            $table->string('description')->nullable(); // Mô tả: "Nạp coin", "Mở khóa chapter X"
            $table->string('reference_type')->nullable(); // Model type (MangaChapter, etc.)
            $table->unsignedBigInteger('reference_id')->nullable(); // ID của model liên quan
            $table->unsignedBigInteger('balance_after')->nullable(); // Số coin sau giao dịch
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('admin_id');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
