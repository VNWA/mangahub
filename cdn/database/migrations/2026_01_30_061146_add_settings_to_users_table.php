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
        Schema::table('users', function (Blueprint $table) {
            // Notification settings
            $table->boolean('notify_email_new_chapter')->default(true)->after('coin');
            $table->boolean('notify_email_comment_reply')->default(true)->after('notify_email_new_chapter');
            $table->boolean('notify_email_recommendations')->default(true)->after('notify_email_comment_reply');
            $table->boolean('notify_push_new_chapter')->default(true)->after('notify_email_recommendations');
            $table->boolean('notify_push_comment_reply')->default(true)->after('notify_push_new_chapter');

            // Privacy settings
            $table->boolean('privacy_public_profile')->default(false)->after('notify_push_comment_reply');
            $table->boolean('privacy_show_reading_history')->default(false)->after('privacy_public_profile');
            $table->boolean('privacy_show_favorites')->default(false)->after('privacy_show_reading_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_email_new_chapter',
                'notify_email_comment_reply',
                'notify_email_recommendations',
                'notify_push_new_chapter',
                'notify_push_comment_reply',
                'privacy_public_profile',
                'privacy_show_reading_history',
                'privacy_show_favorites',
            ]);
        });
    }
};
