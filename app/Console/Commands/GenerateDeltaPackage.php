<?php

namespace App\Console\Commands;

use App\Services\DeploymentPackageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * GenerateDeltaPackage (Artisan command)
 *
 * This command is now a thin wrapper around DeploymentPackageService.
 * All heavy logic lives in the service; this command only handles
 * argument parsing, cache-keyed progress (for V1/V2 backward compat),
 * and JSON output to stdout.
 *
 * V1 and V2 pages continue to use this command via the old
 * /deployments/generate-delta route — they are NOT affected by the
 * queue architecture introduced for V3.
 */
class GenerateDeltaPackage extends Command
{
    protected $signature = 'deploy:delta
        {environment : Target environment, e.g. PROD or STG}
        {project : Project name, e.g. Test1}
        {base : Current live Git tag/commit, e.g. v1.1.2}
        {head : Target Git tag/commit, e.g. v1.1.3}
        {--repo= : Full repo id like owner/repo}
        {--name= : Custom folder name}
        {--output= : Optional output directory relative to project root}';

    protected $description = 'Generate update and rollback delta deployment packages from two Git versions';

    public function handle(DeploymentPackageService $service): int
    {
        $environment = strtoupper(trim($this->argument('environment')));
        $project = trim($this->argument('project'));
        $base = trim($this->argument('base'));
        $head = trim($this->argument('head'));
        $repo = $this->option('repo') ?? '';

        $timestamp = now()->format('Ymd-Hi');
        $folderName = $this->option('name') ?: "{$environment}-{$project}-{$this->safe($base)}-to-{$this->safe($head)}-{$timestamp}";

        // Progress callback — writes to cache keyed by folder name (V1/V2 compat)
        $progressCallback = function (array $data, string $message) use ($folderName) {
            $cacheKey = "packaging_progress_{$folderName}";
            $current = Cache::get($cacheKey, [
                'fileDownloadProgress' => 0,
                'headFileExtraction' => 0,
                'baseFileExtraction' => 0,
                'compareFilesProgress' => 0,
                'packageGenProgress' => 0,
                'compressionProgress' => 0,
                'packagingProgress' => 0,
                'packagingMessage' => 'Initializing...',
            ]);

            $merged = array_merge($current, $data);
            if ($message !== '') {
                $merged['packagingMessage'] = $message;
            }

            // Derive overall packagingProgress from weighted stage values
            if (! isset($data['packagingProgress'])) {
                $merged['packagingProgress'] = (int) round(
                    ($merged['fileDownloadProgress'] / 100) * 10 +
                    ($merged['headFileExtraction'] / 100) * 20 +
                    ($merged['baseFileExtraction'] / 100) * 20 +
                    ($merged['compareFilesProgress'] / 100) * 10 +
                    ($merged['packageGenProgress'] / 100) * 20 +
                    ($merged['compressionProgress'] / 100) * 20
                );
            }

            Cache::put($cacheKey, $merged, 600);
        };

        try {
            $result = $service->generate(
                $environment,
                $project,
                $base,
                $head,
                $repo,
                $folderName,
                $progressCallback
            );

            // Output JSON on the last line so the controller can parse it
            $this->line(json_encode($result));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $progressCallback(['packagingMessage' => 'Error: '.$e->getMessage()], 'Error: '.$e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    protected function safe(string $value): string
    {
        return preg_replace('/[^\w.\-]+/', '_', $value);
    }
}
