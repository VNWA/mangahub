<?php

namespace App\Console\Commands;

use App\Models\TemporaryUpload;
use Illuminate\Console\Command;
use Storage;

class TemporaryClear extends Command
{
    protected $signature = 'temporary:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Temporary uploads clear';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $uploads = TemporaryUpload::where('created_at', '<', now()->subHour())->get();

        foreach ($uploads as $upload) {
            Storage::delete($upload->path);
            $upload->delete();
        }
    }
}
