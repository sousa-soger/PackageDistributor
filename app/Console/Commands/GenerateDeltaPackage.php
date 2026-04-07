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
        $current  = Cache::get($cacheKey, [
            'fileDownloadProgress'   => 0,
            'baseFileExtraction'     => 0,
            'headFileExtraction'     => 0,
            'compareFilesProgress'   => 0,
            'packageGenProgress'     => 0,
            'compressionProgress'    => 0,
            'packagingProgress'      => 0,
            'packagingMessage'       => 'Initializing...',
        ]);
        $merged = array_merge($current, $data);

        // Derive overall packagingProgress from weighted stage values
        $weighted = (int) round(
            ($merged['fileDownloadProgress']   / 100) * 10 +
            ($merged['headFileExtraction']     / 100) * 20 +
            ($merged['baseFileExtraction']     / 100) * 20 +
            ($merged['compareFilesProgress']   / 100) * 10 +
            ($merged['packageGenProgress']     / 100) * 20 +
            ($merged['compressionProgress']    / 100) * 20
        );

        // Allow explicit overrides (e.g. setting 100 on done, or 0 on error)
        if (!isset($data['packagingProgress'])) {
            $merged['packagingProgress'] = $weighted;
        }

        Cache::put($cacheKey, $merged, 600);
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
                $this->updateProgress($folderName, ['packagingMessage' => "Downloading base version..."]);

                $self = $this;
                $downloaded = ['base' => 0, 'head' => 0];

                if (!$githubService->downloadZip($owner, $repoName, $base, $baseZipPath, function (int $dlNow, int $dlTotal) use ($self, $folderName, &$downloaded) {
                    $downloaded['base'] = $dlTotal > 0 ? (int) round(($dlNow / $dlTotal) * 100) : 0;
                    // Combined download: base contributes 50%, head contributes 50%
                    $combined = (int) round(($downloaded['base'] + $downloaded['head']) / 2);
                    $self->updateProgress($folderName, [
                        'packagingMessage'     => 'Downloading base version...',
                        'fileDownloadProgress' => $combined,
                    ]);
                })) {
                    throw new \Exception("Failed to download base version {$base}. Please check repository access or limits.");
                }
                $downloaded['base'] = 100;
                

                $headZipPath = $tempBasePath . DIRECTORY_SEPARATOR . 'head.zip';
                $this->line("Downloading head version ({$head}) to {$headZipPath}...");
                $this->updateProgress($folderName, ['packagingMessage' => "Downloading head version..."]);

                if (!$githubService->downloadZip($owner, $repoName, $head, $headZipPath, function (int $dlNow, int $dlTotal) use ($self, $folderName, &$downloaded) {
                    $downloaded['head'] = $dlTotal > 0 ? (int) round(($dlNow / $dlTotal) * 100) : 0;
                    $combined = (int) round(($downloaded['base'] + $downloaded['head']) / 2);
                    $self->updateProgress($folderName, [
                        'packagingMessage'     => 'Downloading head version...',
                        'fileDownloadProgress' => $combined,
                    ]);
                })) {
                    throw new \Exception("Failed to download head version {$head}. Please check repository access or limits.");
                }
                $downloaded['head'] = 100;
                $this->updateProgress($folderName, ['fileDownloadProgress' => 100]);

                $this->grantWindowsPermissions($baseZipPath);
                $this->grantWindowsPermissions($headZipPath);

                $baseExtractPath = $tempBasePath . DIRECTORY_SEPARATOR . 'base_extract';
                $this->line("Extracting base version ({$base}) to {$baseExtractPath}...");
                $this->updateProgress($folderName, ['packagingMessage' => "Extracting base version..."]);
                $this->extractZipWithProgress($baseZipPath, $baseExtractPath, $folderName, 'baseFileExtraction');
                $this->grantWindowsPermissions($baseExtractPath);

                $headExtractPath = $tempBasePath . DIRECTORY_SEPARATOR . 'head_extract';
                $this->line("Extracting head version ({$head}) to {$headExtractPath}...");
                $this->updateProgress($folderName, ['packagingMessage' => "Extracting head version..."]);
                $this->extractZipWithProgress($headZipPath, $headExtractPath, $folderName, 'headFileExtraction');
                $this->grantWindowsPermissions($headExtractPath);

                
                $this->line("Comparing zip manifests...");
                $this->updateProgress($folderName, ['packagingMessage' => "Comparing files..."]);

                $diffData = $this->compareFileSizeWithProgress($baseZipPath, $headZipPath, $folderName);
                $changedFiles  = $diffData['changed'];
                $totalChanges  = count($changedFiles);
                $this->line("Found {$totalChanges} changes.");

                $this->versionFileDifferenceTxt($packageRoot, $diffData['modifiedFiles'], $diffData['deletedFiles'], $diffData['addedFiles'], $base, $head);

                $this->line("Generating packages for update and rollback...");
                $this->updateProgress($folderName, ['packagingMessage' => "Generating update and rollback packages..."]);
                $this->generatePackagesWithProgress($packageRoot, $baseExtractPath, $headExtractPath, $diffData, $folderName);

            }

            $this->updateProgress($folderName, ['packagingMessage' => "Creating ZIP archive..."]);
            $zipPath = $this->buildZipWithProgress($packageRoot, $folderName);

            $this->updateProgress($folderName, ['packagingMessage' => "Creating TAR.GZ archive..."]);
            $tarGzPath = $this->buildTarGz($packageRoot, $folderName);

            $this->updateProgress($folderName, ['packagingMessage' => "Done.", 'packagingProgress' => 100,
                'fileDownloadProgress' => 100, 'headFileExtraction' => 100, 'baseFileExtraction' => 100,
                'compareFilesProgress' => 100, 'packageGenProgress' => 100, 'compressionProgress' => 100]);


            $sha256 = $zipPath && file_exists($zipPath) ? hash_file('sha256', $zipPath) : null;

            $result = [
                'status' => 'success',
                'folder_name' => $folderName,
                'package_root' => $packageRoot,
                'temp_path' => isset($tempBasePath) ? $tempBasePath : null,
                'message' => 'Package created successfully.',
                'changed_files' => $changedFiles,
                'file_size' => $this->getDirectorySize($packageRoot),
                'sha256' => $sha256,
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

    private function getDirectorySize(string $directory): string
    {
        if (!File::exists($directory)) {
            return '0 B';
        }
        $size = 0;
        foreach (File::allFiles($directory) as $file) {
            $size += $file->getSize();
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size >= 1024 && $i < 4; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . ' ' . $units[$i];
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

    /**
     * Extract a ZIP file while reporting per-file progress to the cache.
     */
    private function extractZipWithProgress(string $zipPath, string $destinationPath, string $folderName, string $field): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return;
        }

        $total   = $zip->numFiles;
        $done    = 0;
        $lastPct = -1;

        for ($i = 0; $i < $total; $i++) {
            $name = $zip->getNameIndex($i);
            // Create directory entries inline
            if (substr($name, -1) === '/') {
                $dir = $destinationPath . DIRECTORY_SEPARATOR . $name;
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            } else {
                // Extract single file
                $zip->extractTo($destinationPath, $name);
            }
            $done++;
            $pct = $total > 0 ? (int) round(($done / $total) * 100) : 0;
            if ($pct !== $lastPct) {
                $lastPct = $pct;
                $this->updateProgress($folderName, [
                    $field             => $pct,
                    'packagingMessage' => ucfirst(str_replace(['FileExtraction', 'File'], [' file extraction', ' file'], $field)) . " ({$done}/{$total})",
                ]);
            }
        }

        $zip->close();
        $this->updateProgress($folderName, [$field => 100]);
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
        return $this->compareFileSizeWithProgress($baseZipPath, $headZipPath, null);
    }

    /**
     * Compare two ZIP manifests and report progress to cache during iteration.
     */
    private function compareFileSizeWithProgress(string $baseZipPath, string $headZipPath, ?string $folderName): array
    {
        $baseFiles = $this->getZipManifest($baseZipPath);
        $headFiles = $this->getZipManifest($headZipPath);

        $changed       = [];
        $addedFiles    = [];
        $modifiedFiles = [];
        $deletedFiles  = [];

        $total   = count($headFiles) + count($baseFiles);
        $done    = 0;
        $lastPct = -1;

        $maybeUpdate = function (string $msg) use ($folderName, $total, &$done, &$lastPct) {
            $done++;
            if ($folderName === null || $total === 0) return;
            $pct = (int) round(($done / $total) * 100);
            if ($pct !== $lastPct) {
                $lastPct = $pct;
                $this->updateProgress($folderName, [
                    'compareFilesProgress' => $pct,
                    'packagingMessage'     => $msg,
                ]);
            }
        };

        // Identify Added and Modified
        foreach ($headFiles as $path => $headInfo) {
            if (!isset($baseFiles[$path])) {
                $change = [
                    'filename'          => $path,
                    'status'            => 'added',
                    'old_size'          => 0,
                    'new_size'          => $headInfo['size'],
                    'size_diff'         => $headInfo['size'],
                    'head_internal_path' => $headInfo['internal_path'],
                ];
                $changed[]    = $change;
                $addedFiles[] = $change;
            } elseif ($baseFiles[$path]['crc'] !== $headInfo['crc'] || $baseFiles[$path]['size'] !== $headInfo['size']) {
                $change = [
                    'filename'           => $path,
                    'status'             => 'modified',
                    'old_size'           => $baseFiles[$path]['size'],
                    'new_size'           => $headInfo['size'],
                    'size_diff'          => $headInfo['size'] - $baseFiles[$path]['size'],
                    'base_internal_path' => $baseFiles[$path]['internal_path'],
                    'head_internal_path' => $headInfo['internal_path'],
                ];
                $changed[]       = $change;
                $modifiedFiles[] = $change;
            }
            $maybeUpdate("Comparing files ({$done}/{$total})");
        }

        // Identify Deleted
        foreach ($baseFiles as $path => $baseInfo) {
            if (!isset($headFiles[$path])) {
                $change = [
                    'filename'           => $path,
                    'status'             => 'deleted',
                    'old_size'           => $baseInfo['size'],
                    'new_size'           => 0,
                    'size_diff'          => -$baseInfo['size'],
                    'base_internal_path' => $baseInfo['internal_path'],
                ];
                $changed[]      = $change;
                $deletedFiles[] = $change;
            }
            $maybeUpdate("Comparing files ({$done}/{$total})");
        }

        if ($folderName !== null) {
            $this->updateProgress($folderName, ['compareFilesProgress' => 100]);
        }

        return [
            'changed'       => $changed,
            'addedFiles'    => $addedFiles,
            'modifiedFiles' => $modifiedFiles,
            'deletedFiles'  => $deletedFiles,
        ];
    }

    private function versionFileDifferenceTxt(string $packageRoot, array $modifiedFiles, array $deletedFiles, array $addedFiles, string $base, string $head)
    {   
        $versionChangesTxt = $packageRoot . DIRECTORY_SEPARATOR . 'version_changes.txt';

        $content = "{$base} -> {$head}\n\n";

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
        
        $content .= "File(s) modified (" . count($modifiedFiles) . "):\n";
        foreach ($modifiedFiles as $file) {
            $diffSign = $file['size_diff'] > 0 ? '+' : '';
            $content .= " ~ {$file['filename']} (diff: {$diffSign}{$file['size_diff']} bytes)\n";
        }
        $content .= "\n";

        file_put_contents($versionChangesTxt, $content);
    }

    private function generatePackages(string $packageRoot, string $baseExtractPath, string $headExtractPath, array $diffData): void
    {
        $this->generatePackagesWithProgress($packageRoot, $baseExtractPath, $headExtractPath, $diffData, null);
    }

    /**
     * Copy changed files into update/rollback directories, reporting progress.
     */
    private function generatePackagesWithProgress(
        string $packageRoot,
        string $baseExtractPath,
        string $headExtractPath,
        array $diffData,
        ?string $folderName
    ): void {
        $updatePath   = $packageRoot . DIRECTORY_SEPARATOR . 'update';
        $rollbackPath = $packageRoot . DIRECTORY_SEPARATOR . 'rollback';

        File::ensureDirectoryExists($updatePath);
        File::ensureDirectoryExists($rollbackPath);

        // Total operations: update (added + modified) + rollback (deleted + modified)
        $totalOps = count($diffData['addedFiles']) + count($diffData['modifiedFiles']) * 2 + count($diffData['deletedFiles']);
        $done     = 0;
        $lastPct  = -1;

        $maybeUpdate = function () use ($folderName, $totalOps, &$done, &$lastPct) {
            $done++;
            if ($folderName === null || $totalOps === 0) return;
            $pct = (int) round(($done / $totalOps) * 100);
            if ($pct !== $lastPct) {
                $lastPct = $pct;
                $this->updateProgress($folderName, [
                    'packageGenProgress' => $pct,
                    'packagingMessage'   => "Generating packages ({$done}/{$totalOps})",
                ]);
            }
        };

        // Populate update (Base -> Head)
        foreach ($diffData['addedFiles'] as $file) {
            $src  = $headExtractPath . DIRECTORY_SEPARATOR . $file['head_internal_path'];
            $dest = $updatePath . DIRECTORY_SEPARATOR . $file['filename'];
            File::ensureDirectoryExists(dirname($dest));
            File::copy($src, $dest);
            $maybeUpdate();
        }
        foreach ($diffData['modifiedFiles'] as $file) {
            $src  = $headExtractPath . DIRECTORY_SEPARATOR . $file['head_internal_path'];
            $dest = $updatePath . DIRECTORY_SEPARATOR . $file['filename'];
            File::ensureDirectoryExists(dirname($dest));
            File::copy($src, $dest);
            $maybeUpdate();
        }

        // Populate rollback (Head -> Base)
        foreach ($diffData['deletedFiles'] as $file) {
            $src  = $baseExtractPath . DIRECTORY_SEPARATOR . $file['base_internal_path'];
            $dest = $rollbackPath . DIRECTORY_SEPARATOR . $file['filename'];
            File::ensureDirectoryExists(dirname($dest));
            File::copy($src, $dest);
            $maybeUpdate();
        }
        foreach ($diffData['modifiedFiles'] as $file) {
            $src  = $baseExtractPath . DIRECTORY_SEPARATOR . $file['base_internal_path'];
            $dest = $rollbackPath . DIRECTORY_SEPARATOR . $file['filename'];
            File::ensureDirectoryExists(dirname($dest));
            File::copy($src, $dest);
            $maybeUpdate();
        }

        if ($folderName !== null) {
            $this->updateProgress($folderName, ['packageGenProgress' => 100]);
        }
    }


    /**
     * Build a .zip archive from the package directory (idempotent). No progress tracking.
     */
    private function buildZip(string $packageRoot, string $folderName): ?string
    {
        return $this->buildZipWithProgress($packageRoot, null);
    }

    /**
     * Build a .zip archive while optionally reporting per-file compression progress.
     * Pass a non-null $folderName to enable cache progress updates.
     */
    private function buildZipWithProgress(string $packageRoot, ?string $folderName): ?string
    {
        $zipPath = $packageRoot . '.zip';

        if (file_exists($zipPath)) {
            if ($folderName !== null) {
                $this->updateProgress($folderName, ['compressionProgress' => 100]);
            }
            return $zipPath;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($packageRoot, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $allFiles = [];
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $allFiles[] = $file->getRealPath();
            }
        }

        $total   = count($allFiles);
        $done    = 0;
        $lastPct = -1;

        foreach ($allFiles as $filePath) {
            $relativePath = substr($filePath, strlen($packageRoot) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);
            $zip->addFile($filePath, $relativePath);
            $done++;
            if ($folderName !== null) {
                $pct = $total > 0 ? (int) round(($done / $total) * 100) : 0;
                if ($pct !== $lastPct) {
                    $lastPct = $pct;
                    $this->updateProgress($folderName, [
                        'compressionProgress' => $pct,
                        'packagingMessage'    => "Compressing archive ({$done}/{$total})",
                    ]);
                }
            }
        }

        $zip->close();

        if ($folderName !== null) {
            $this->updateProgress($folderName, ['compressionProgress' => 100]);
        }

        return $zipPath;
    }

    /**
     * Build a .tar.gz archive from the package directory (idempotent).
     */
    private function buildTarGz(string $packageRoot, string $folderName): ?string
    {
        $tarGzPath = $packageRoot . '.tar.gz';

        if (file_exists($tarGzPath)) {
            return $tarGzPath;
        }

        try {
            $tarPath = $packageRoot . '.tar';
            $tar = new \PharData($tarPath);
            $tar->buildFromDirectory($packageRoot);
            $tar->compress(\Phar::GZ);
            if (file_exists($tarPath)) {
                unlink($tarPath);
            }
            return $tarGzPath;
        } catch (\Throwable $e) {
            return null;
        }
    }
}