<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class DeploymentPackageController extends Controller
{
    public function generate(Request $request)
    {
        // Prevent PHP from crashing if the download/extraction takes longer than 60s
        set_time_limit(600);

        // Release the session write-lock immediately so concurrent polling requests
        // to /deployments/progress/{name} are not blocked while Artisan::call() runs.
        session()->save();

        $validated = $request->validate([
            'environment'  => ['required', 'string', 'max:20'],
            'project_name' => ['required', 'string', 'max:100'],
            'base_version' => ['required', 'string', 'max:100'],
            'head_version' => ['required', 'string', 'max:100'],
            'repo'         => ['required', 'string', 'max:255'],
            'package_name' => ['nullable', 'string', 'max:255'],
        ]);

        $tmpDir = storage_path('framework/cache');
        File::ensureDirectoryExists($tmpDir);
        
        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call('deploy:delta', [
                'environment'  => $validated['environment'],
                'project'      => $validated['project_name'],
                'base'         => $validated['base_version'],
                'head'         => $validated['head_version'],
                '--repo'       => $validated['repo'],
                '--name'       => $validated['package_name'] ?? '',
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();
            
            if ($exitCode !== 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Command exited with code {$exitCode}.\nOutput:\n{$output}",
                ], 500);
            }

            $outputLines = preg_split('/\r\n|\r|\n/', trim($output));
            $lastLine    = trim(end($outputLines));

            $decoded = json_decode($lastLine, true);

            if (! is_array($decoded)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Package generated, but result payload could not be parsed.',
                    'raw_output' => $output,
                ], 500);
            }

            return response()->json($decoded);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate package: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function progress($name)
    {
        // Release session lock immediately — this endpoint is read-only and
        // must not be blocked by the session lock held during generate().
        session()->save();

        $progress = Cache::get("packaging_progress_{$name}", [
            'fileDownloadProgress' => 0,
            'headFileExtraction'   => 0,
            'baseFileExtraction'   => 0,
            'compareFilesProgress' => 0,
            'packageGenProgress'   => 0,
            'compressionProgress'  => 0,
            'packagingProgress'    => 0,
            'packagingMessage'     => 'Starting...',
        ]);

        return response()->json($progress)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    public function downloadArchive(Request $request)
    {
        $folderName = $request->query('folder');
        $format = $request->query('format', '.zip');

        if (!$folderName) {
            return response('Folder parameter is required.', 400);
        }

        // Keep it safe by blocking directory traversal attacks
        $folderName = basename($folderName);
        if ($folderName === '.' || $folderName === '..') {
            return response('Invalid folder name.', 400);
        }

        $packageRoot = storage_path("app/deployment-packages/{$folderName}");

        if (!File::exists($packageRoot) || !File::isDirectory($packageRoot)) {
            return response('Package directory not found.', 404);
        }

        // Determine which format to generate
        if ($format === '.tar.gz') {
            $tarGzPath = $this->createTarGz($packageRoot, $folderName);
            if (!$tarGzPath) {
                return response('Could not generate tar.gz file.', 500);
            }
            return response()->download($tarGzPath, "{$folderName}.tar.gz");
        } else {
            // Default to .zip
            $zipPath = $this->createZip($packageRoot, $folderName);
            if (!$zipPath) {
                return response('Could not generate ZIP file.', 500);
            }
            return response()->download($zipPath, "{$folderName}.zip");
        }
    }

    /**
     * Create a .zip archive from the package directory.
     */
    private function createZip(string $packageRoot, string $folderName): ?string
    {
        $zipPath = $packageRoot . '.zip';

        if (File::exists($zipPath)) {
            return $zipPath;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($packageRoot, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($packageRoot) + 1);
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
            return $zipPath;
        }

        return null;
    }

    /**
     * Create a .tar.gz archive from the package directory.
     */
    private function createTarGz(string $packageRoot, string $folderName): ?string
    {
        $tarGzPath = $packageRoot . '.tar.gz';

        if (File::exists($tarGzPath)) {
            return $tarGzPath;
        }

        try {
            $tarPath = $packageRoot . '.tar';
            $tar = new \PharData($tarPath);
            $tar->buildFromDirectory($packageRoot);
            $tar->compress(\Phar::GZ);

            // Remove the intermediate .tar file
            if (File::exists($tarPath)) {
                unlink($tarPath);
            }

            return $tarGzPath;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

