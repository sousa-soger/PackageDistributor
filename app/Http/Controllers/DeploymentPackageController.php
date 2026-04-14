<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateDeploymentPackageJob;
use App\Models\DeploymentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class DeploymentPackageController extends Controller
{
    // =========================================================================
    // V1 / V2 — SYNCHRONOUS (unchanged behaviour, kept for backward compat)
    // =========================================================================

    /**
     * Synchronous package generation (used by V1 and V2 pages via old route).
     * Still calls Artisan::call() inline so V1/V2 pages are not affected.
     */
    public function generate(Request $request)
    {
        set_time_limit(600);
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
                    'status'  => 'error',
                    'message' => "Command exited with code {$exitCode}.\nOutput:\n{$output}",
                ], 500);
            }

            $outputLines = preg_split('/\r\n|\r|\n/', trim($output));
            $lastLine    = trim(end($outputLines));
            $decoded     = json_decode($lastLine, true);

            if (!is_array($decoded)) {
                return response()->json([
                    'status'     => 'error',
                    'message'    => 'Package generated, but result payload could not be parsed.',
                    'raw_output' => $output,
                ], 500);
            }

            return response()->json($decoded);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to generate package: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Progress polling by package name (V1 / V2 compat).
     */
    public function progress($name)
    {
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

        return response()->json($progress)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    // =========================================================================
    // V3 — QUEUE-BASED
    // =========================================================================

    /**
     * Create a deployment_jobs record and dispatch a queued job.
     * Returns immediately with { status: "queued", job_id, package_name }.
     *
     * Route: POST /deployments/queue-job
     */
    public function queueJob(Request $request)
    {
        session()->save();

        $validated = $request->validate([
            'environment'  => ['required', 'string', 'max:20'],
            'project_name' => ['required', 'string', 'max:100'],
            'base_version' => ['required', 'string', 'max:100'],
            'head_version' => ['required', 'string', 'max:100'],
            'repo'         => ['required', 'string', 'max:255'],
            'package_name' => ['nullable', 'string', 'max:255'],
            'format'       => ['nullable', 'string', 'in:.zip,.tar.gz,both'],
        ]);

        // Derive the package name the same way the Artisan command did
        if (empty($validated['package_name'])) {
            $environment = strtoupper(trim($validated['environment']));
            $project     = trim($validated['project_name']);
            $base        = preg_replace('/[^\w.\-]+/', '_', $validated['base_version']);
            $head        = preg_replace('/[^\w.\-]+/', '_', $validated['head_version']);
            $timestamp   = now()->format('Ymd-Hi');
            $validated['package_name'] = "{$environment}-{$project}-{$base}-to-{$head}-{$timestamp}";
        }

        // Create the DB record
        $job = DeploymentJob::create([
            'user_id'      => auth()->id(),
            'repo'         => $validated['repo'],
            'project_name' => $validated['project_name'],
            'environment'  => $validated['environment'],
            'base_version' => $validated['base_version'],
            'head_version' => $validated['head_version'],
            'format'       => $validated['format'] ?? '.zip',
            'package_name' => $validated['package_name'],
            'status'       => 'queued',
        ]);

        // Seed the cache so progress polling returns something useful immediately
        Cache::put($job->progressCacheKey(), $job->defaultProgressArray(), 600);

        // Dispatch to the database queue
        GenerateDeploymentPackageJob::dispatch($job->id);

        return response()->json([
            'status'       => 'queued',
            'job_id'       => $job->id,
            'package_name' => $job->package_name,
        ]);
    }

    /**
     * Poll progress for a specific queued job (by DB ID).
     * Fast reads from cache; falls back to DB progress column when cache is cold.
     *
     * Route: GET /deployments/jobs/{id}/progress
     */
    public function jobProgress(int $id)
    {
        session()->save();

        $job = DeploymentJob::find($id);

        if (!$job) {
            return response()->json(['error' => 'Job not found.'], 404)
                ->header('Cache-Control', 'no-store');
        }

        // Try cache first (live updates during run), fall back to DB snapshot
        $progress = Cache::get($job->progressCacheKey(), $job->progress ?? $job->defaultProgressArray());

        $payload = [
            'job_id'  => $job->id,
            'status'  => $job->status,
            'progress'=> $progress,
        ];

        if ($job->status === 'completed') {
            $payload['result'] = $job->result_json;
        }

        if ($job->status === 'failed') {
            $payload['error'] = $job->error_message;
        }

        return response()->json($payload)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    // =========================================================================
    // Download (unchanged)
    // =========================================================================

    public function downloadArchive(Request $request)
    {
        $folderName = $request->query('folder');
        $format     = $request->query('format', '.zip');

        if (!$folderName) {
            return response('Folder parameter is required.', 400);
        }

        $folderName = basename($folderName);
        if ($folderName === '.' || $folderName === '..') {
            return response('Invalid folder name.', 400);
        }

        $packageRoot = storage_path("app/deployment-packages/{$folderName}");

        if (!File::exists($packageRoot) || !File::isDirectory($packageRoot)) {
            return response('Package directory not found.', 404);
        }

        if ($format === '.tar.gz') {
            $tarGzPath = $this->createTarGz($packageRoot, $folderName);
            if (!$tarGzPath) {
                return response('Could not generate tar.gz file.', 500);
            }
            return response()->download($tarGzPath, "{$folderName}.tar.gz");
        }

        $zipPath = $this->createZip($packageRoot, $folderName);
        if (!$zipPath) {
            return response('Could not generate ZIP file.', 500);
        }
        return response()->download($zipPath, "{$folderName}.zip");
    }

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
                    $filePath     = $file->getRealPath();
                    $relativePath = str_replace('\\', '/', substr($filePath, strlen($packageRoot) + 1));
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
            return $zipPath;
        }

        return null;
    }

    private function createTarGz(string $packageRoot, string $folderName): ?string
    {
        $tarGzPath = $packageRoot . '.tar.gz';
        if (File::exists($tarGzPath)) {
            return $tarGzPath;
        }

        try {
            $tarPath = $packageRoot . '.tar';
            $tar     = new \PharData($tarPath);
            $tar->buildFromDirectory($packageRoot);
            $tar->compress(\Phar::GZ);
            if (File::exists($tarPath)) {
                unlink($tarPath);
            }
            return $tarGzPath;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
