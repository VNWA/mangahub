<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteOldGuests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guests:delete-old {--days=30 : Number of days old to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete guest users older than specified days (default: 30 days)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Deleting guest users created before {$cutoffDate->toDateString()}...");

        $deleted = DB::transaction(function () use ($cutoffDate) {
            // Delete related data first (cascade should handle this, but being explicit)
            $guestIds = User::where('is_guest', true)
                ->where('created_at', '<', $cutoffDate)
                ->pluck('id');

            if ($guestIds->isEmpty()) {
                return 0;
            }

            // Delete favorites
            DB::table('favorites')->whereIn('user_id', $guestIds)->delete();
            
            // Delete reading history
            DB::table('reading_history')->whereIn('user_id', $guestIds)->delete();
            
            // Delete personal access tokens
            DB::table('personal_access_tokens')->whereIn('tokenable_id', $guestIds)
                ->where('tokenable_type', User::class)
                ->delete();

            // Delete users
            $deleted = User::where('is_guest', true)
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            return $deleted;
        });

        if ($deleted > 0) {
            $this->info("Successfully deleted {$deleted} guest user(s).");
        } else {
            $this->info('No guest users found to delete.');
        }

        return Command::SUCCESS;
    }
}
