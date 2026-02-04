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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('reportable'); // reportable_type, reportable_id (Manga hoặc MangaChapter)
            $table->string('reason'); // Lý do report: spam, inappropriate, copyright, other
            $table->text('description')->nullable(); // Mô tả chi tiết
            $table->enum('status', ['pending', 'reviewed', 'resolved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null'); // Admin xử lý
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable(); // Ghi chú từ admin
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id'], 'reports_reportable_index');
            $table->index(['user_id', 'status'], 'reports_user_status_index');
            $table->index('status', 'reports_status_index');
            $table->index('created_at', 'reports_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
