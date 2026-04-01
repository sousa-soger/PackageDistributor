<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Symfony\Component\Process\Process;

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

    protected string $workingDir;

    public function handle(): int
    {
        $environment = strtoupper(trim($this->argument('environment')));
        $project     = trim($this->argument('project'));
        $base        = trim($this->argument('base'));
        $head        = trim($this->argument('head'));

        $timestamp  = now()->format('Ymd-Hi');
        $folderName = $this->option('name') ?: "{$environment}-{$project}-{$this->safe($base)}-to-{$this->safe($head)}-{$timestamp}";

        $outputBase = $this->option('output')
            ? base_path(trim($this->option('output'), '/\\'))
            : 'C:\xampp\htdocs\cyb-pack-dist\storage\app\deployment-packages';

        $packageRoot  = $outputBase . DIRECTORY_SEPARATOR . $folderName;

        try {
            // Simply create the folder for now
            File::ensureDirectoryExists($packageRoot);

            $repo = $this->option('repo');
            $changedFiles = [];
            $totalChanges = 0;

            if ($repo && str_contains($repo, '/')) {
                [$owner, $repoName] = explode('/', $repo, 2);
                $githubService = app(\App\Services\GitHubService::class);
                
                // Create temp yymmddhhmmss folder 
                $tempTimestamp = now()->format('YmdHis');
                $tempBasePath = storage_path("app/temp/{$tempTimestamp}");
                
                $baseFolder = $tempBasePath . DIRECTORY_SEPARATOR . 'base';
                
                $this->line("Downloading base version ({$base}) to {$baseFolder}...");
                $githubService->downloadZip($owner, $repoName, $base, $baseFolder);
                
                // For debugging: comment out head folder download for now
                $headFolder = $tempBasePath . DIRECTORY_SEPARATOR . 'head';
                $this->line("Downloading head version ({$head}) to {$headFolder}...");
                $githubService->downloadZip($owner, $repoName, $head, $headFolder);
                
                // The previous compare logic was removed as requested
                // But we still need to provide $changedFiles = [] to avoid breaking frontend
            }

            $result = [
                'status' => 'success',
                'folder_name' => $folderName,
                'package_root' => $packageRoot,
                'temp_path' => isset($tempBasePath) ? $tempBasePath : null,
                'message' => 'Created package folder successfully without packaging.',
                'changed_files' => $changedFiles,
                'summary' => [
                    'total_changes' => $totalChanges,
                    'update_delete_count' => 0,
                    'rollback_delete_count' => 0,
                ],
            ];

            $this->line(json_encode($result));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    protected function safe(string $value): string
    {
        return preg_replace('/[^\w.\-]+/', '_', $value);
    }
}