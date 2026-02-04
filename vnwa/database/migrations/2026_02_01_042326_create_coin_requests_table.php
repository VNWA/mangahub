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
        Schema::create('coin_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('amount'); // Số coin yêu cầu
            $table->enum('payment_method', ['bank_transfer', 'momo', 'zalopay', 'vnpay', 'other'])->default('bank_transfer');
            $table->string('payment_proof')->nullable(); // Ảnh chứng từ thanh toán
            $table->text('note')->nullable(); // Ghi chú từ user
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable(); // Ghi chú từ admin
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null'); // Admin xử lý
            $table->timestamp('processed_at')->nullable(); // Thời gian xử lý
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_requests');
    }
};
