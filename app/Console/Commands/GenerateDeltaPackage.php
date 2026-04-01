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
                
                $baseZipPath = $tempBasePath . DIRECTORY_SEPARATOR . 'base.zip';
                
                $this->line("Downloading base version ({$base}) to {$baseZipPath}...");
                $githubService->downloadZip($owner, $repoName, $base, $baseZipPath);
                
                $headZipPath = $tempBasePath . DIRECTORY_SEPARATOR . 'head.zip';
                $this->line("Downloading head version ({$head}) to {$headZipPath}...");
                $githubService->downloadZip($owner, $repoName, $head, $headZipPath);
                
                $this->line("Comparing zip manifests...");
                $changedFiles = $this->compareZipFiles($baseZipPath, $headZipPath);
                $totalChanges = count($changedFiles);
                $this->line("Found {$totalChanges} changes.");
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

    private function compareZipFiles(string $baseZipPath, string $headZipPath): array
    {
        $baseFiles = $this->getZipManifest($baseZipPath);
        $headFiles = $this->getZipManifest($headZipPath);

        $changed = [];

        // Identify Added and Modified
        foreach ($headFiles as $path => $headInfo) {
            if (!isset($baseFiles[$path])) {
                $changed[] = [
                    'filename' => $path,
                    'status' => 'added',
                    'head_internal_path' => $headInfo['internal_path'],
                ];
            } elseif ($baseFiles[$path]['crc'] !== $headInfo['crc'] || $baseFiles[$path]['size'] !== $headInfo['size']) {
                $changed[] = [
                    'filename' => $path,
                    'status' => 'modified',
                    'base_internal_path' => $baseFiles[$path]['internal_path'],
                    'head_internal_path' => $headInfo['internal_path'],
                ];
            }
        }

        // Identify Deleted
        foreach ($baseFiles as $path => $baseInfo) {
            if (!isset($headFiles[$path])) {
                $changed[] = [
                    'filename' => $path,
                    'status' => 'deleted',
                    'base_internal_path' => $baseInfo['internal_path'],
                ];
            }
        }

        return $changed;
    }

    private function getZipManifest(string $zipPath): array
    {
        $manifest = [];
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $internalPath = $stat['name'];
                
                // Exclude directory entries
                if (substr($internalPath, -1) === '/') {
                    continue;
                }
                
                // GitHub wraps the repo in owner-repo-commitHash/
                // We need to strip this prefix to get the normalized path
                $parts = explode('/', $internalPath, 2);
                if (count($parts) === 2) {
                    $normalizedPath = $parts[1];
                    $manifest[$normalizedPath] = [
                        'crc' => $stat['crc'],
                        'size' => $stat['size'],
                        'internal_path' => $internalPath,
                    ];
                }
            }
            $zip->close();
        }
        return $manifest;
    }
}