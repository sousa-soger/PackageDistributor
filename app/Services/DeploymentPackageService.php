<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * DeploymentPackageService
 *
 * Encapsulates the full delta-package generation pipeline.
 * Callers provide a $progressCallback so both the Artisan command
 * and the queued job can hook in their own progress reporters.
 *
 * Progress callback signature:
 *   fn(array $stageData, string $message): void
 *
 * where $stageData is an array with any subset of:
 *   fileDownloadProgress, headFileExtraction, baseFileExtraction,
 *   compareFilesProgress, packageGenProgress, compressionProgress,
 *   packagingProgress, packagingMessage
 */
class DeploymentPackageService
{
    // ── Public entry point ───────────────────────────────────────────────────

    /**
     * Run the complete package generation pipeline.
     *
     * @param  callable  $progressCallback  fn(array $data, string $message): void
     * @return array     Final result payload (same shape as the old Artisan JSON output)
     * @throws \Throwable on any failure
     */
    public function generate(
        string   $environment,
        string   $projectName,
        string   $baseVersion,
        string   $headVersion,
        string   $repo,
        string   $packageName,
        callable $progressCallback
    ): array {
        $environment = strtoupper(trim($environment));
        $projectName = trim($projectName);
        $baseVersion = trim($baseVersion);
        $headVersion = trim($headVersion);

        $outputBase  = 'C:\\xampp\\htdocs\\cyb-pack-dist\\storage\\app\\deployment-packages';
        $packageRoot = $outputBase . DIRECTORY_SEPARATOR . $packageName;

        $progressCallback(['packagingMessage' => 'Setting up package folder...'], 'Setting up package folder...');
        File::ensureDirectoryExists($packageRoot);
        $this->grantWindowsPermissions($packageRoot);

        $changedFiles = [];
        $totalChanges = 0;

        if ($repo && str_contains($repo, '/')) {
            [$owner, $repoName] = explode('/', $repo, 2);
            /** @var GitHubService $githubService */
            $githubService = app(GitHubService::class);

            // ── Temp workspace ────────────────────────────────────────────
            $tempTimestamp = now()->format('YmdHis');
            $tempBasePath  = storage_path("app/temp/{$tempTimestamp}");
            File::ensureDirectoryExists($tempBasePath);
            $this->grantWindowsPermissions($tempBasePath);

            // ── Download base ────────────────────────────────────────────
            $baseZipPath = $tempBasePath . DIRECTORY_SEPARATOR . 'base.zip';
            $progressCallback(['packagingMessage' => 'Downloading base version...'], 'Downloading base version...');

            $downloaded = ['base' => 0, 'head' => 0];

            if (!$githubService->downloadZip(
                $owner, $repoName, $baseVersion, $baseZipPath,
                function (int $dlNow, int $dlTotal) use ($progressCallback, &$downloaded) {
                    $downloaded['base'] = $dlTotal > 0 ? (int) round(($dlNow / $dlTotal) * 100) : 0;
                    $combined = (int) round(($downloaded['base'] + $downloaded['head']) / 2);
                    $progressCallback(
                        ['fileDownloadProgress' => $combined],
                        'Downloading base version...'
                    );
                }
            )) {
                throw new \RuntimeException("Failed to download base version {$baseVersion}. Check repository access or API limits.");
            }
            $downloaded['base'] = 100;

            // ── Download head ────────────────────────────────────────────
            $headZipPath = $tempBasePath . DIRECTORY_SEPARATOR . 'head.zip';
            $progressCallback(['packagingMessage' => 'Downloading head version...'], 'Downloading head version...');

            if (!$githubService->downloadZip(
                $owner, $repoName, $headVersion, $headZipPath,
                function (int $dlNow, int $dlTotal) use ($progressCallback, &$downloaded) {
                    $downloaded['head'] = $dlTotal > 0 ? (int) round(($dlNow / $dlTotal) * 100) : 0;
                    $combined = (int) round(($downloaded['base'] + $downloaded['head']) / 2);
                    $progressCallback(
                        ['fileDownloadProgress' => $combined],
                        'Downloading head version...'
                    );
                }
            )) {
                throw new \RuntimeException("Failed to download head version {$headVersion}. Check repository access or API limits.");
            }
            $downloaded['head'] = 100;
            $progressCallback(['fileDownloadProgress' => 100], 'Download complete.');

            $this->grantWindowsPermissions($baseZipPath);
            $this->grantWindowsPermissions($headZipPath);

            // ── Extract base ─────────────────────────────────────────────
            $baseExtractPath = $tempBasePath . DIRECTORY_SEPARATOR . 'base_extract';
            $progressCallback(['packagingMessage' => 'Extracting base version...'], 'Extracting base version...');
            $this->extractZipWithProgress($baseZipPath, $baseExtractPath, 'baseFileExtraction', $progressCallback);
            $this->grantWindowsPermissions($baseExtractPath);

            // ── Extract head ─────────────────────────────────────────────
            $headExtractPath = $tempBasePath . DIRECTORY_SEPARATOR . 'head_extract';
            $progressCallback(['packagingMessage' => 'Extracting head version...'], 'Extracting head version...');
            $this->extractZipWithProgress($headZipPath, $headExtractPath, 'headFileExtraction', $progressCallback);
            $this->grantWindowsPermissions($headExtractPath);

            // ── Compare ──────────────────────────────────────────────────
            $progressCallback(['packagingMessage' => 'Comparing files...'], 'Comparing files...');
            $diffData     = $this->compareFileSizeWithProgress($baseZipPath, $headZipPath, $progressCallback);
            $changedFiles = $diffData['changed'];
            $totalChanges = count($changedFiles);

            $this->versionFileDifferenceTxt(
                $packageRoot,
                $diffData['modifiedFiles'],
                $diffData['deletedFiles'],
                $diffData['addedFiles'],
                $baseVersion,
                $headVersion
            );

            try {
                $compareData = $githubService->compare($owner, $repoName, $baseVersion, $headVersion);
                $this->versionInfoTxt($packageRoot, $headVersion, $compareData);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Could not generate version_info.txt: " . $e->getMessage());
            }

            // ── Generate update / rollback directories ────────────────────
            $progressCallback(['packagingMessage' => 'Generating update and rollback packages...'], 'Generating packages...');
            $this->generatePackagesWithProgress($packageRoot, $baseExtractPath, $headExtractPath, $diffData, $progressCallback);
        }

        // ── Compress ──────────────────────────────────────────────────────
        $progressCallback(['packagingMessage' => 'Creating ZIP archive...'], 'Creating ZIP archive...');
        $zipPath = $this->buildZipWithProgress($packageRoot, $progressCallback);

        $progressCallback(['packagingMessage' => 'Creating TAR.GZ archive...'], 'Creating TAR.GZ archive...');
        $tarGzPath = $this->buildTarGz($packageRoot);

        // ── Mark done ────────────────────────────────────────────────────
        $progressCallback([
            'fileDownloadProgress' => 100,
            'headFileExtraction'   => 100,
            'baseFileExtraction'   => 100,
            'compareFilesProgress' => 100,
            'packageGenProgress'   => 100,
            'compressionProgress'  => 100,
            'packagingProgress'    => 100,
            'packagingMessage'     => 'Done.',
        ], 'Done.');

        $zipSha256   = ($zipPath && file_exists($zipPath)) ? hash_file('sha256', $zipPath) : null;
        $targzSha256 = ($tarGzPath && file_exists($tarGzPath)) ? hash_file('sha256', $tarGzPath) : null;

        $zipSize   = ($zipPath && file_exists($zipPath)) ? $this->getFileSize($zipPath) : null;
        $targzSize = ($tarGzPath && file_exists($tarGzPath)) ? $this->getFileSize($tarGzPath) : null;
        $originalDirSize = $this->getDirectorySize($packageRoot);

        // ── Cleanup original uncompressed folder ──────────────────────────
        $progressCallback(['packagingMessage' => 'Cleaning up uncompressed files...'], 'Cleaning up uncompressed files...');
        try {
            if (File::exists($packageRoot)) {
                File::deleteDirectory($packageRoot);
            }
        } catch (\Throwable $e) {}

        return [
            'status'        => 'success',
            'folder_name'   => $packageName,
            'package_root'  => $packageRoot,
            'temp_path'     => isset($tempBasePath) ? $tempBasePath : null,
            'message'       => 'Package created successfully.',
            'changed_files' => $changedFiles,
            'file_size'     => $originalDirSize,
            'zip_size'      => $zipSize,
            'zip_sha256'    => $zipSha256,
            'targz_size'    => $targzSize,
            'targz_sha256'  => $targzSha256,
            'summary'       => [
                'total_changes'        => $totalChanges,
                'update_delete_count'  => 0,
                'rollback_delete_count'=> 0,
            ],
        ];
    }

    // ── Windows helpers ──────────────────────────────────────────────────────

    private function grantWindowsPermissions(string $path): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = 'icacls ' . escapeshellarg($path) . ' /grant Everyone:(OI)(CI)F /T /C /Q 2>&1';
            shell_exec($cmd);
        }
    }

    // ── ZIP extraction with progress ─────────────────────────────────────────

    /**
     * @param  callable  $progressCallback  fn(array $data, string $message): void
     */
    private function extractZipWithProgress(
        string   $zipPath,
        string   $destinationPath,
        string   $field,
        callable $progressCallback
    ): void {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return;
        }

        $total   = $zip->numFiles;
        $done    = 0;
        $lastPct = -1;

        for ($i = 0; $i < $total; $i++) {
            $name = $zip->getNameIndex($i);
            if (substr($name, -1) === '/') {
                $dir = $destinationPath . DIRECTORY_SEPARATOR . $name;
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            } else {
                $zip->extractTo($destinationPath, $name);
            }
            $done++;
            $pct = $total > 0 ? (int) round(($done / $total) * 100) : 0;
            if ($pct !== $lastPct) {
                $lastPct = $pct;
                $label   = str_contains($field, 'base') ? 'Base' : 'Head';
                $progressCallback(
                    [$field => $pct],
                    "{$label} file extraction ({$done}/{$total})"
                );
            }
        }

        $zip->close();
        $progressCallback([$field => 100], 'Extraction complete.');
    }

    // ── Manifest comparison with progress ────────────────────────────────────

    private function getZipManifest(string $zipPath): array
    {
        $manifest = [];
        $zip      = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return $manifest;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat         = $zip->statIndex($i);
            $internalPath = $stat['name'];
            if (substr($internalPath, -1) === '/') {
                continue;
            }
            $parts = explode('/', $internalPath, 2);
            if (count($parts) === 2) {
                $manifest[$parts[1]] = [
                    'crc'           => $stat['crc'],
                    'size'          => $stat['size'],
                    'internal_path' => $internalPath,
                ];
            }
        }
        $zip->close();

        return $manifest;
    }

    /**
     * @param  callable  $progressCallback  fn(array $data, string $message): void
     */
    private function compareFileSizeWithProgress(
        string   $baseZipPath,
        string   $headZipPath,
        callable $progressCallback
    ): array {
        $baseFiles     = $this->getZipManifest($baseZipPath);
        $headFiles     = $this->getZipManifest($headZipPath);

        $changed       = [];
        $addedFiles    = [];
        $modifiedFiles = [];
        $deletedFiles  = [];

        $total   = count($headFiles) + count($baseFiles);
        $done    = 0;
        $lastPct = -1;

        $tick = function (string $msg) use ($progressCallback, $total, &$done, &$lastPct) {
            $done++;
            if ($total === 0) return;
            $pct = (int) round(($done / $total) * 100);
            if ($pct !== $lastPct) {
                $lastPct = $pct;
                $progressCallback(['compareFilesProgress' => $pct], $msg);
            }
        };

        foreach ($headFiles as $path => $headInfo) {
            if (!isset($baseFiles[$path])) {
                $change        = [
                    'filename'           => $path,
                    'status'             => 'added',
                    'old_size'           => 0,
                    'new_size'           => $headInfo['size'],
                    'size_diff'          => $headInfo['size'],
                    'head_internal_path' => $headInfo['internal_path'],
                ];
                $changed[]    = $change;
                $addedFiles[] = $change;
            } elseif (
                $baseFiles[$path]['crc']  !== $headInfo['crc'] ||
                $baseFiles[$path]['size'] !== $headInfo['size']
            ) {
                $change          = [
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
            $tick("Comparing files ({$done}/{$total})");
        }

        foreach ($baseFiles as $path => $baseInfo) {
            if (!isset($headFiles[$path])) {
                $change         = [
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
            $tick("Comparing files ({$done}/{$total})");
        }

        $progressCallback(['compareFilesProgress' => 100], 'File comparison complete.');

        return compact('changed', 'addedFiles', 'modifiedFiles', 'deletedFiles');
    }

    // ── Package generation with progress ─────────────────────────────────────

    /**
     * @param  callable  $progressCallback  fn(array $data, string $message): void
     */
    private function generatePackagesWithProgress(
        string   $packageRoot,
        string   $baseExtractPath,
        string   $headExtractPath,
        array    $diffData,
        callable $progressCallback
    ): void {
        $updatePath   = $packageRoot . DIRECTORY_SEPARATOR . 'update';
        $rollbackPath = $packageRoot . DIRECTORY_SEPARATOR . 'rollback';

        File::ensureDirectoryExists($updatePath);
        File::ensureDirectoryExists($rollbackPath);

        $totalOps = count($diffData['addedFiles'])
                  + count($diffData['modifiedFiles']) * 2
                  + count($diffData['deletedFiles']);
        $done     = 0;
        $lastPct  = -1;

        $tick = function () use ($progressCallback, $totalOps, &$done, &$lastPct) {
            $done++;
            if ($totalOps === 0) return;
            $pct = (int) round(($done / $totalOps) * 100);
            if ($pct !== $lastPct) {
                $lastPct = $pct;
                $progressCallback(
                    ['packageGenProgress' => $pct],
                    "Generating packages ({$done}/{$totalOps})"
                );
            }
        };

        // Update package (Base → Head)
        foreach ($diffData['addedFiles'] as $file) {
            $src  = $headExtractPath . DIRECTORY_SEPARATOR . $file['head_internal_path'];
            $dest = $updatePath . DIRECTORY_SEPARATOR . $file['filename'];
            File::ensureDirectoryExists(dirname($dest));
            File::copy($src, $dest);
            $tick();
        }
        foreach ($diffData['modifiedFiles'] as $file) {
            $src  = $headExtractPath . DIRECTORY_SEPARATOR . $file['head_internal_path'];
            $dest = $updatePath . DIRECTORY_SEPARATOR . $file['filename'];
            File::ensureDirectoryExists(dirname($dest));
            File::copy($src, $dest);
            $tick();
        }

        // Rollback package (Head → Base)
        foreach ($diffData['deletedFiles'] as $file) {
            $src  = $baseExtractPath . DIRECTORY_SEPARATOR . $file['base_internal_path'];
            $dest = $rollbackPath . DIRECTORY_SEPARATOR . $file['filename'];
            File::ensureDirectoryExists(dirname($dest));
            File::copy($src, $dest);
            $tick();
        }
        foreach ($diffData['modifiedFiles'] as $file) {
            $src  = $baseExtractPath . DIRECTORY_SEPARATOR . $file['base_internal_path'];
            $dest = $rollbackPath . DIRECTORY_SEPARATOR . $file['filename'];
            File::ensureDirectoryExists(dirname($dest));
            File::copy($src, $dest);
            $tick();
        }

        $progressCallback(['packageGenProgress' => 100], 'Package generation complete.');
    }

    // ── ZIP compression with progress ────────────────────────────────────────

    /**
     * @param  callable  $progressCallback  fn(array $data, string $message): void
     */
    private function buildZipWithProgress(string $packageRoot, callable $progressCallback): ?string
    {
        $zipPath = $packageRoot . '.zip';

        if (file_exists($zipPath)) {
            $progressCallback(['compressionProgress' => 100], 'ZIP already exists.');
            return $zipPath;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $files    = new \RecursiveIteratorIterator(
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
            $pct = $total > 0 ? (int) round(($done / $total) * 100) : 0;
            if ($pct !== $lastPct) {
                $lastPct = $pct;
                $progressCallback(
                    ['compressionProgress' => $pct],
                    "Compressing archive ({$done}/{$total})"
                );
            }
        }

        $zip->close();
        $progressCallback(['compressionProgress' => 100], 'ZIP archive created.');

        return $zipPath;
    }

    // ── TAR.GZ (no per-file progress — PharData doesn't expose it) ───────────

    private function buildTarGz(string $packageRoot): ?string
    {
        $tarGzPath = $packageRoot . '.tar.gz';

        if (file_exists($tarGzPath)) {
            return $tarGzPath;
        }

        try {
            $tarPath = $packageRoot . '.tar';
            $tar     = new \PharData($tarPath);
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

    // ── Version diff text file ────────────────────────────────────────────────

    private function versionFileDifferenceTxt(
        string $packageRoot,
        array  $modifiedFiles,
        array  $deletedFiles,
        array  $addedFiles,
        string $base,
        string $head
    ): void {
        $path    = $packageRoot . DIRECTORY_SEPARATOR . 'version_changes.txt';
        $content = "{$base} -> {$head}\n\n";

        $content .= 'File(s) deleted (' . count($deletedFiles) . "):\n";
        foreach ($deletedFiles as $file) {
            $content .= " - {$file['filename']} (-{$file['old_size']} bytes)\n";
        }
        $content .= "\n";

        $content .= 'File(s) added (' . count($addedFiles) . "):\n";
        foreach ($addedFiles as $file) {
            $content .= " + {$file['filename']} (+{$file['new_size']} bytes)\n";
        }
        $content .= "\n";

        $content .= 'File(s) modified (' . count($modifiedFiles) . "):\n";
        foreach ($modifiedFiles as $file) {
            $sign     = $file['size_diff'] > 0 ? '+' : '';
            $content .= " ~ {$file['filename']} (diff: {$sign}{$file['size_diff']} bytes)\n";
        }

        file_put_contents($path, $content);
    }

    private function versionInfoTxt(string $packageRoot, string $headVersion, array $compareData): void
    {
        $path = $packageRoot . DIRECTORY_SEPARATOR . 'version_info.txt';
        $content = "==================================================\n";
        $content .= "               Version Information                \n";
        $content .= "==================================================\n";
        $content .= "Version: {$headVersion}\n";
        $content .= "GeneratedDate: " . now()->format('Y-m-d H:i:s') . "\n\n";

        $commits = $compareData['commits'] ?? [];
        if (!empty($commits)) {
            $latestCommit = array_pop($commits);
            $latestSha = substr($latestCommit['sha'] ?? '', 0, 8);
            $latestMessage = rtrim($latestCommit['commit']['message'] ?? '');
            $latestMessageIndented = collect(explode("\n", $latestMessage))
                ->map(fn($line) => $line === '' ? '' : "  " . $line)
                ->implode("\n");

            $content .= "--- Latest Commit Details ------------------------\n";
            $content .= "Commit Hash: {$latestSha}\n";
            $content .= "Commit Message:\n{$latestMessageIndented}\n\n";

            if (!empty($commits)) {
                $content .= "==================================================\n";
                $content .= "            Additional Commit History             \n";
                $content .= "==================================================\n";

                $commits = array_reverse($commits);
                foreach ($commits as $index => $commit) {
                    $sha = substr($commit['sha'] ?? '', 0, 8);
                    $message = rtrim($commit['commit']['message'] ?? '');
                    $messageIndented = collect(explode("\n", $message))
                        ->map(fn($line) => $line === '' ? '' : "  " . $line)
                        ->implode("\n");

                    $content .= "Commit: \n";
                    $content .= "  {$sha}\n";
                    if ($messageIndented !== '') {
                        $content .= "{$messageIndented}\n";
                    }
                    
                    if ($index < count($commits) - 1) {
                        $content .= "\n--------------------------------------------------\n";
                    } else {
                        $content .= "\n";
                    }
                }
            }
        }

        file_put_contents($path, rtrim($content) . "\n");
    }

    // ── Directory size helper ─────────────────────────────────────────────────

    private function getFileSize(string $path): string
    {
        if (!file_exists($path)) {
            return '0 B';
        }
        $size  = filesize($path);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i     = 0;
        while ($size >= 1024 && $i < 4) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
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
        $i     = 0;
        while ($size >= 1024 && $i < 4) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}
