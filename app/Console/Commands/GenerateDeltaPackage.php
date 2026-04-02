<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
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

    private function updateProgress(string $folderName, array $data)
    {
        $cacheKey = "packaging_progress_{$folderName}";
        $current = Cache::get($cacheKey, [
            'fileDownloadProgress' => 0,
            'baseFileExtraction' => 0,
            'headFileExtraction' => 0,
            'packagingProgress' => 25,
            'packagingMessage' => 'Initializing...',
        ]);
        Cache::put($cacheKey, array_merge($current, $data), 600);
    }

    // Give admin permission to the folder
    private function grantWindowsPermissions(string $path)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = 'icacls ' . escapeshellarg($path) . ' /grant Everyone:(OI)(CI)F /T /C /Q 2>&1';
            shell_exec($cmd);
        }
    }

    //BIG DAWG PART
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
            $this->updateProgress($folderName, ['packagingMessage' => 'Setting up package folder...']);
            File::ensureDirectoryExists($packageRoot);
            $this->grantWindowsPermissions($packageRoot);

            $repo = $this->option('repo');
            $changedFiles = [];
            $totalChanges = 0;

            if ($repo && str_contains($repo, '/')) {
                [$owner, $repoName] = explode('/', $repo, 2);
                $githubService = app(\App\Services\GitHubService::class);
                
                $tempTimestamp = now()->format('YmdHis');
                $tempBasePath = storage_path("app/temp/{$tempTimestamp}");
                File::ensureDirectoryExists($tempBasePath);
                $this->grantWindowsPermissions($tempBasePath);
                
                $baseZipPath = $tempBasePath . DIRECTORY_SEPARATOR . 'base.zip';
                
                $this->line("Downloading base version ({$base}) to {$baseZipPath}...");
                $this->updateProgress($folderName, ['packagingMessage' => "Downloading base version...", 'packagingProgress' => 30, 'fileDownloadProgress' => 25]);
                if (!$githubService->downloadZip($owner, $repoName, $base, $baseZipPath)) {
                    throw new \Exception("Failed to download base version {$base}. Please check repository access or limits.");
                }
                
                $headZipPath = $tempBasePath . DIRECTORY_SEPARATOR . 'head.zip';
                $this->line("Downloading head version ({$head}) to {$headZipPath}...");
                $this->updateProgress($folderName, ['packagingMessage' => "Downloading head version...", 'packagingProgress' => 35, 'fileDownloadProgress' => 100]);
                if (!$githubService->downloadZip($owner, $repoName, $head, $headZipPath)) {
                    throw new \Exception("Failed to download head version {$head}. Please check repository access or limits.");
                }

                $this->grantWindowsPermissions($baseZipPath);
                $this->grantWindowsPermissions($headZipPath);

                $baseExtractPath = $tempBasePath . DIRECTORY_SEPARATOR . 'base_extract';
                $this->line("Extracting base version ({$base}) to {$baseExtractPath}...");
                $this->updateProgress($folderName, ['packagingMessage' => "Extracting base version...", 'packagingProgress' => 45, 'baseFileExtraction' => 50]);
                $this->extractZip($baseZipPath, $baseExtractPath);
                $this->grantWindowsPermissions($baseExtractPath);
                $this->updateProgress($folderName, ['baseFileExtraction' => 100]);
                
                $headExtractPath = $tempBasePath . DIRECTORY_SEPARATOR . 'head_extract';
                $this->line("Extracting head version ({$head}) to {$headExtractPath}...");
                $this->updateProgress($folderName, ['packagingMessage' => "Extracting head version...", 'packagingProgress' => 55, 'headFileExtraction' => 50]);
                $this->extractZip($headZipPath, $headExtractPath);
                $this->grantWindowsPermissions($headExtractPath);
                $this->updateProgress($folderName, ['headFileExtraction' => 100]);
                
                $this->line("Comparing zip manifests...");
                $this->updateProgress($folderName, ['packagingMessage' => "Computing delta packages...", 'packagingProgress' => 75]);
                
                $diffData = $this->compareFileSize($baseZipPath, $headZipPath);
                $changedFiles = $diffData['changed'];
                $totalChanges = count($changedFiles);
                $this->line("Found {$totalChanges} changes.");
                
                $this->versionFileDifferenceTxt($packageRoot, $diffData['modifiedFiles'], $diffData['deletedFiles'], $diffData['addedFiles'], $base, $head);
            }

            $this->updateProgress($folderName, ['packagingMessage' => "Finalizing...", 'packagingProgress' => 95]);

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
            $this->updateProgress($folderName, ['packagingMessage' => "Error: " . $e->getMessage()]);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    protected function safe(string $value): string
    {
        return preg_replace('/[^\w.\-]+/', '_', $value);
    }

    private function extractZip(string $zipPath, string $destinationPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($destinationPath);
            $zip->close();
        }
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
        $zip = new ZipArchive();
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

    private function compareFileSize(string $baseZipPath, string $headZipPath): array
    {
        $baseFiles = $this->getZipManifest($baseZipPath);
        $headFiles = $this->getZipManifest($headZipPath);

        $changed = [];
        $addedFiles = [];
        $modifiedFiles = [];
        $deletedFiles = [];

        // Identify Added and Modified
        foreach ($headFiles as $path => $headInfo) {
            if (!isset($baseFiles[$path])) {
                $change = [
                    'filename' => $path,
                    'status' => 'added',
                    'old_size' => 0,
                    'new_size' => $headInfo['size'],
                    'size_diff' => $headInfo['size'],
                    'head_internal_path' => $headInfo['internal_path'],
                ];
                $changed[] = $change;
                $addedFiles[] = $change;
            } elseif ($baseFiles[$path]['crc'] !== $headInfo['crc'] || $baseFiles[$path]['size'] !== $headInfo['size']) {
                $change = [
                    'filename' => $path,
                    'status' => 'modified',
                    'old_size' => $baseFiles[$path]['size'],
                    'new_size' => $headInfo['size'],
                    'size_diff' => $headInfo['size'] - $baseFiles[$path]['size'],
                    'base_internal_path' => $baseFiles[$path]['internal_path'],
                    'head_internal_path' => $headInfo['internal_path'],
                ];
                $changed[] = $change;
                $modifiedFiles[] = $change;
            }
        }

        // Identify Deleted
        foreach ($baseFiles as $path => $baseInfo) {
            if (!isset($headFiles[$path])) {
                $change = [
                    'filename' => $path,
                    'status' => 'deleted',
                    'old_size' => $baseInfo['size'],
                    'new_size' => 0,
                    'size_diff' => -$baseInfo['size'],
                    'base_internal_path' => $baseInfo['internal_path'],
                ];
                $changed[] = $change;
                $deletedFiles[] = $change;
            }
        }

        return [
            'changed' => $changed,
            'addedFiles' => $addedFiles,
            'modifiedFiles' => $modifiedFiles,
            'deletedFiles' => $deletedFiles,
        ];
    }

    private function versionFileDifferenceTxt(string $packageRoot, array $modifiedFiles, array $deletedFiles, array $addedFiles, string $base, string $head)
    {   
        $versionChangesTxt = $packageRoot . DIRECTORY_SEPARATOR . 'version_changes.txt';

        $content = "{$base} -> {$head}\n\n";

        $content .= "File(s) modified (" . count($modifiedFiles) . "):\n";
        foreach ($modifiedFiles as $file) {
            $diffSign = $file['size_diff'] > 0 ? '+' : '';
            $content .= " ~ {$file['filename']} (diff: {$diffSign}{$file['size_diff']} bytes)\n";
        }
        $content .= "\n";

        $content .= "File(s) deleted (" . count($deletedFiles) . "):\n";
        foreach ($deletedFiles as $file) {
            $content .= " - {$file['filename']} (-{$file['old_size']} bytes)\n";
        }
        $content .= "\n";

        $content .= "File(s) added (" . count($addedFiles) . "):\n";
        foreach ($addedFiles as $file) {
            $content .= " + {$file['filename']} (+{$file['new_size']} bytes)\n";
        }
        $content .= "\n";

        file_put_contents($versionChangesTxt, $content);
    }
}