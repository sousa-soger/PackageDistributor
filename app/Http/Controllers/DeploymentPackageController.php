<?php

namespace App\Http\Controllers;

use App\Exceptions\OAuthTokenRefreshException;
use App\Http\Requests\PreviewDeploymentPackageRequest;
use App\Http\Requests\QueueDeploymentPackageRequest;
use App\Http\Requests\QueueGitlessDeploymentPackageRequest;
use App\Jobs\GenerateDeploymentPackageJob;
use App\Models\DeploymentJob;
use App\Models\Repository;
use App\Models\User;
use App\Services\DeploymentPackageService;
use App\Services\OAuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
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
            'environment' => ['required', 'string', 'max:20'],
            'project_name' => ['required', 'string', 'max:100'],
            'base_version' => ['required', 'string', 'max:100'],
            'head_version' => ['required', 'string', 'max:100'],
            'repo' => ['required', 'string', 'max:255'],
            'package_name' => ['nullable', 'string', 'max:255'],
        ]);

        $tmpDir = storage_path('framework/cache');
        File::ensureDirectoryExists($tmpDir);

        try {
            $exitCode = Artisan::call('deploy:delta', [
                'environment' => $validated['environment'],
                'project' => $validated['project_name'],
                'base' => $validated['base_version'],
                'head' => $validated['head_version'],
                '--repo' => $validated['repo'],
                '--name' => $validated['package_name'] ?? '',
            ]);

            $output = Artisan::output();

            if ($exitCode !== 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Command exited with code {$exitCode}.\nOutput:\n{$output}",
                ], 500);
            }

            $outputLines = preg_split('/\r\n|\r|\n/', trim($output));
            $lastLine = trim(end($outputLines));
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
                'message' => 'Failed to generate package: '.$e->getMessage(),
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
            'headFileExtraction' => 0,
            'baseFileExtraction' => 0,
            'compareFilesProgress' => 0,
            'packageGenProgress' => 0,
            'compressionProgress' => 0,
            'packagingProgress' => 0,
            'packagingMessage' => 'Starting...',
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
    public function queueJob(QueueDeploymentPackageRequest $request): JsonResponse
    {
        session()->save();

        $validated = $request->validated();
        $repository = $request->packageRepository();

        if ($repository) {
            $this->authorize('createPackage', $repository);

            if (! in_array($repository->provider, ['github', 'gitlab', 'local-pc'], true)) {
                return response()->json([
                    'message' => 'Package creation is not available for this repository type.',
                ], 422);
            }

            if (($repository->status ?? 'connected') !== 'connected') {
                return response()->json([
                    'message' => 'Reconnect or sync this repository before creating a package.',
                ], 422);
            }

            if ($repository->provider === 'local-pc' && (! $repository->storage_path || ! File::isDirectory($repository->storage_path))) {
                return response()->json([
                    'message' => 'This local repository is missing its stored Git mirror. Reconnect or upload it again before creating a package.',
                ], 422);
            }

            $validated['repo'] = $repository->provider === 'local-pc'
                ? $repository->storage_path
                : $repository->name;
            $validated['vcs_provider'] = $repository->provider;
            $validated['project_name'] = $repository->label;
        }

        // Derive the package name the same way the Artisan command did
        if (empty($validated['package_name'])) {
            $environment = strtoupper(trim($validated['environment']));
            $project = trim($validated['project_name']);
            $base = preg_replace('/[^\w.\-]+/', '_', $validated['base_version']);
            $head = preg_replace('/[^\w.\-]+/', '_', $validated['head_version']);
            $timestamp = now()->format('ymd-Hi');
            $validated['package_name'] = "{$environment}-{$project}-{$base}-to-{$head}-{$timestamp}";
        }

        // Create the DB record
        $job = DeploymentJob::create([
            'user_id' => auth()->id(),
            'project_id' => $repository?->project_id,
            'repository_id' => $repository?->id,
            'repo' => $validated['repo'],
            'vcs_provider' => $validated['vcs_provider'] ?? config('packaging.vcs_provider', 'github'),
            'project_name' => $validated['project_name'],
            'environment' => $validated['environment'],
            'base_version' => $validated['base_version'],
            'head_version' => $validated['head_version'],
            'package_name' => $validated['package_name'],
            'status' => 'queued',
        ]);

        // Seed the cache so progress polling returns something useful immediately
        Cache::put($job->progressCacheKey(), $job->defaultProgressArray(), DeploymentJob::PROGRESS_CACHE_TTL_SECONDS);

        // Dispatch to the database queue
        GenerateDeploymentPackageJob::dispatch($job->id);

        return response()->json([
            'status' => 'queued',
            'job_id' => $job->id,
            'package_name' => $job->package_name,
        ]);
    }

    public function queueGitlessJob(QueueGitlessDeploymentPackageRequest $request): JsonResponse
    {
        session()->save();

        $validated = $request->validated();
        /** @var UploadedFile $baseArchive */
        $baseArchive = $validated['base_archive'];
        /** @var UploadedFile $headArchive */
        $headArchive = $validated['head_archive'];

        if (! $this->isZipArchiveUpload($baseArchive)) {
            return response()->json([
                'message' => 'Upload a ZIP archive for the base folder.',
            ], 422);
        }

        if (! $this->isZipArchiveUpload($headArchive)) {
            return response()->json([
                'message' => 'Upload a ZIP archive for the target folder.',
            ], 422);
        }

        $environment = strtoupper(trim($validated['environment']));
        $projectName = trim($validated['project_name'] ?? '') ?: 'Gitless folders';
        $baseVersion = 'base-folder';
        $headVersion = 'target-folder';
        $packageName = trim($validated['package_name'] ?? '');

        if ($packageName === '') {
            $packageName = $this->gitlessPackageName(
                $environment,
                $baseArchive->getClientOriginalName(),
                $headArchive->getClientOriginalName()
            );
        }

        $workspace = storage_path('app/temp/gitless-'.auth()->id().'-'.uniqid());

        try {
            File::ensureDirectoryExists($workspace);

            $baseArchive->move($workspace, 'base.zip');
            $headArchive->move($workspace, 'head.zip');

            $job = DeploymentJob::create([
                'user_id' => auth()->id(),
                'repo' => $workspace,
                'vcs_provider' => 'gitless',
                'project_name' => $projectName,
                'environment' => $environment,
                'base_version' => $baseVersion,
                'head_version' => $headVersion,
                'package_name' => $packageName,
                'status' => 'queued',
            ]);

            Cache::put($job->progressCacheKey(), $job->defaultProgressArray(), DeploymentJob::PROGRESS_CACHE_TTL_SECONDS);

            GenerateDeploymentPackageJob::dispatch($job->id);

            return response()->json([
                'status' => 'queued',
                'job_id' => $job->id,
                'package_name' => $job->package_name,
            ]);
        } catch (\Throwable $e) {
            File::deleteDirectory($workspace);

            return response()->json([
                'message' => 'Failed to queue gitless package: '.$e->getMessage(),
            ], 500);
        }
    }

    private function gitlessPackageName(string $environment, string $baseArchiveName, string $headArchiveName): string
    {
        $baseName = $this->gitlessPackageNamePart($baseArchiveName);
        $headName = $this->gitlessPackageNamePart($headArchiveName);
        $timestamp = now()->format('ymd-Hi');

        if ($baseName === $headName) {
            return "{$environment}-{$baseName}-{$timestamp}";
        }

        return "{$environment}-{$baseName}-to-{$headName}-{$timestamp}";
    }

    private function gitlessPackageNamePart(string $archiveName): string
    {
        $name = preg_replace('/\.zip$/i', '', $archiveName) ?? $archiveName;
        $name = preg_replace('/[^\w.\-]+/', '_', trim($name)) ?? '';
        $name = trim($name, '_');

        return $name !== '' ? $name : 'folder';
    }

    public function previewChanges(
        PreviewDeploymentPackageRequest $request,
        DeploymentPackageService $service,
        OAuthTokenService $oauthTokens
    ): JsonResponse {
        session()->save();

        $repository = $request->packageRepository();

        if (! $repository) {
            return response()->json([
                'message' => 'Choose a repository before previewing changes.',
            ], 422);
        }

        if (! in_array($repository->provider, ['github', 'gitlab', 'local-pc'], true)) {
            return response()->json([
                'message' => 'Change preview is not available for this repository type.',
            ], 422);
        }

        if (($repository->status ?? 'connected') !== 'connected') {
            return response()->json([
                'message' => 'Reconnect or sync this repository before previewing changes.',
            ], 422);
        }

        if ($repository->provider === 'local-pc' && (! $repository->storage_path || ! File::isDirectory($repository->storage_path))) {
            return response()->json([
                'message' => 'This local repository is missing its stored Git mirror. Reconnect or upload it again before previewing changes.',
            ], 422);
        }

        $packageRepository = $repository->provider === 'local-pc'
            ? $repository->storage_path
            : $repository->name;
        $vcsToken = '';

        if (in_array($repository->provider, ['github', 'gitlab'], true)) {
            /** @var User|null $actor */
            $actor = $request->user();

            try {
                $vcsToken = $this->repositoryAccessToken($repository, $actor, $oauthTokens);
            } catch (OAuthTokenRefreshException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            if ($vcsToken === '') {
                return response()->json([
                    'message' => 'The repository owner needs to reconnect OAuth or save a PAT before changes can be previewed.',
                ], 422);
            }
        }

        try {
            return response()->json([
                'summary' => $service->previewChanges(
                    $request->validated('base_version'),
                    $request->validated('head_version'),
                    $packageRepository,
                    $repository->provider,
                    $vcsToken
                ),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to preview changes: '.$e->getMessage(),
            ], 500);
        }
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

        if (! $job) {
            return response()->json(['error' => 'Job not found.'], 404)
                ->header('Cache-Control', 'no-store');
        }

        // Try cache first (live updates during run), fall back to DB snapshot
        $progress = Cache::get($job->progressCacheKey(), $job->progress ?? $job->defaultProgressArray());

        $payload = [
            'job_id' => $job->id,
            'status' => $job->status,
            'progress' => $progress,
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
    // V3 — Job control (cancel / retry)
    // =========================================================================

    /**
     * Cancel a queued or running job.
     * Marks the DB record as cancelled; the queue worker checks status before
     * executing and will skip it if it hasn't started yet.
     *
     * Route: POST /deployments/jobs/{id}/cancel
     */
    public function cancelJob(int $id)
    {
        $job = DeploymentJob::find($id);

        if (! $job) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        if (! in_array($job->status, ['queued', 'pending', 'running'])) {
            return response()->json(['error' => "Cannot cancel a job with status '{$job->status}'."], 422);
        }

        $job->markCancelled();
        Cache::forget($job->progressCacheKey());

        return response()->json(['message' => 'Job cancelled.', 'status' => 'cancelled']);
    }

    /**
     * Retry a failed or cancelled job by re-queuing it.
     *
     * Route: POST /deployments/jobs/{id}/retry
     */
    public function retryJob(int $id)
    {
        $job = DeploymentJob::find($id);

        if (! $job) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        if (! in_array($job->status, ['failed', 'cancelled'])) {
            return response()->json(['error' => "Only failed or cancelled jobs can be retried (current status: '{$job->status}')."], 422);
        }

        // Reset the record back to queued
        $job->update([
            'status' => 'queued',
            'error_message' => null,
            'progress' => null,
            'message' => null,
            'started_at' => null,
            'finished_at' => null,
        ]);

        // Seed a fresh progress entry so polling returns something immediately
        Cache::put($job->progressCacheKey(), $job->defaultProgressArray(), DeploymentJob::PROGRESS_CACHE_TTL_SECONDS);

        // Dispatch a new queue job
        GenerateDeploymentPackageJob::dispatch($job->id);

        return response()->json(['message' => 'Job re-queued.', 'status' => 'queued', 'job_id' => $job->id]);
    }

    // =========================================================================
    // Download (single archive)
    // =========================================================================

    public function downloadArchive(Request $request)
    {
        $folderName = $request->query('folder');
        $format = $request->query('format', '.zip');

        if (! $folderName) {
            return response('Folder parameter is required.', 400);
        }

        $folderName = basename($folderName);
        if ($folderName === '.' || $folderName === '..') {
            return response('Invalid folder name.', 400);
        }

        $packageRoot = storage_path("app/deployment-packages/{$folderName}");
        $archivePath = $packageRoot.$format;

        if (File::exists($packageRoot) && File::isDirectory($packageRoot)) {
            if ($format === '.tar.gz') {
                if (! $this->createTarGz($packageRoot, $folderName)) {
                    return response('Could not generate tar.gz file.', 500);
                }
            } else {
                if (! $this->createZip($packageRoot, $folderName)) {
                    return response('Could not generate ZIP file.', 500);
                }
            }
        }

        if (! File::exists($archivePath) || File::isDirectory($archivePath)) {
            return response('Package archive not found.', 404);
        }

        return response()->download($archivePath, "{$folderName}{$format}");
    }

    private function isZipArchiveUpload(UploadedFile $uploadedFile): bool
    {
        return strtolower($uploadedFile->getClientOriginalExtension()) === 'zip';
    }

    private function repositoryAccessToken(Repository $repository, ?User $fallbackUser, OAuthTokenService $oauthTokens): string
    {
        if ($repository->access_token) {
            return $repository->access_token;
        }

        $owner = $repository->user ?? $fallbackUser;

        if (! $owner) {
            return '';
        }

        return $oauthTokens->accessToken($owner, $repository->provider) ?? '';
    }

    private function createZip(string $packageRoot, string $folderName): ?string
    {
        $zipPath = $packageRoot.'.zip';
        if (File::exists($zipPath)) {
            return $zipPath;
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($packageRoot, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if (! $file->isDir()) {
                    $filePath = $file->getRealPath();
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
        $tarGzPath = $packageRoot.'.tar.gz';
        if (File::exists($tarGzPath)) {
            return $tarGzPath;
        }

        try {
            $tarPath = $packageRoot.'.tar';
            $tar = new \PharData($tarPath);
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

    // =========================================================================
    // Bulk actions (V3)
    // =========================================================================

    /**
     * POST /deployments/bulk-download
     * Body: { ids: [1,2,...], format: '.zip'|'.tar.gz' }
     * Streams a single merged archive containing all selected packages.
     */
    public function bulkDownload(Request $request)
    {
        $ids = $request->input('ids', []);
        $format = $request->input('format', '.zip');

        if (empty($ids)) {
            return response('No packages selected.', 400);
        }

        $jobs = DeploymentJob::whereIn('id', $ids)->get();
        if ($jobs->isEmpty()) {
            return response('No matching packages found.', 404);
        }

        $tmpName = 'bulk-'.now()->format('YmdHis');
        $tmpZip = storage_path("app/temp/{$tmpName}.zip");
        File::ensureDirectoryExists(storage_path('app/temp'));

        $zip = new \ZipArchive;
        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response('Could not create bulk archive.', 500);
        }

        foreach ($jobs as $job) {
            $folder = $job->package_name;
            $archive = storage_path("app/deployment-packages/{$folder}{$format}");
            if (File::exists($archive)) {
                $zip->addFile($archive, basename($archive));
            }
        }
        $zip->close();

        if ($format === '.zip') {
            return response()->download($tmpZip, "{$tmpName}.zip")->deleteFileAfterSend(true);
        }

        // For tar.gz: wrap the collected .tar.gz files in a zip for simplicity
        return response()->download($tmpZip, "{$tmpName}.zip")->deleteFileAfterSend(true);
    }

    /**
     * DELETE /deployments/bulk-delete
     * Body: { ids: [1,2,...] }
     * Deletes DB records and package files for each id.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['message' => 'No packages selected.'], 400);
        }

        $jobs = DeploymentJob::with('repository.members')
            ->whereIn('id', $ids)
            ->get();

        if ($jobs->isEmpty()) {
            return response()->json(['message' => 'No matching packages found.'], 404);
        }

        foreach ($jobs as $job) {
            $this->authorize('delete', $job);
        }

        foreach ($jobs as $job) {
            $folder = $job->package_name;
            $base = storage_path("app/deployment-packages/{$folder}");

            // Delete archive files and folder
            foreach (['.zip', '.tar.gz', '.tar'] as $ext) {
                if (File::exists($base.$ext)) {
                    File::delete($base.$ext);
                }
            }
            if (File::isDirectory($base)) {
                File::deleteDirectory($base);
            }

            $job->delete();
        }

        return response()->json(['message' => count($jobs).' package(s) deleted.']);
    }
}
