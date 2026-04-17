<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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

        if (!\Illuminate\Support\Facades\File::exists($tempPath)) {
            $this->info('Temp directory does not exist. Nothing to clean.');
            return;
        }

        $directories = \Illuminate\Support\Facades\File::directories($tempPath);
        $count = 0;

        foreach ($directories as $directory) {
            if (\Illuminate\Support\Facades\File::lastModified($directory) < now()->subHours(24)->timestamp) {
                \Illuminate\Support\Facades\File::deleteDirectory($directory);
                $count++;
            }
        }

        $files = \Illuminate\Support\Facades\File::files($tempPath);
        foreach ($files as $file) {
            if (\Illuminate\Support\Facades\File::lastModified($file) < now()->subHours(24)->timestamp) {
                \Illuminate\Support\Facades\File::delete($file);
                $count++;
            }
        }

        $this->info("Cleaned up {$count} old temporary items from temp storage.");
    }
}
