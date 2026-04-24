<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupTempFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:temp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up temporary files older than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tempPath = storage_path('app/temp');

        if (! File::exists($tempPath)) {
            $this->info('Temp directory does not exist. Nothing to clean.');

            return;
        }

        $directories = File::directories($tempPath);
        $count = 0;

        foreach ($directories as $directory) {
            if (File::lastModified($directory) < now()->subHours(24)->timestamp) {
                File::deleteDirectory($directory);
                $count++;
            }
        }

        $files = File::files($tempPath);
        foreach ($files as $file) {
            if (File::lastModified($file) < now()->subHours(24)->timestamp) {
                File::delete($file);
                $count++;
            }
        }

        $this->info("Cleaned up {$count} old temporary items from temp storage.");
    }
}
