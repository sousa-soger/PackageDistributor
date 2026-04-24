<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class DeploymentJob extends Model
{
    protected $fillable = [
        'user_id',
        'batch_id',
        'repo',
        'project_name',
        'environment',
        'base_version',
        'head_version',
        'package_name',
        'status',
        'queue_order',
        'progress',
        'message',
        'result_json',
        'zip_size',
        'zip_sha256',
        'targz_size',
        'targz_sha256',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'progress' => 'array',
        'result_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    // ── Cache key helpers ────────────────────────────────────────────────────

    /**
     * The cache key used for fast live progress polling by job ID.
     */
    public function progressCacheKey(): string
    {
        return "packaging_progress_job_{$this->id}";
    }

    // ── Status transition helpers ────────────────────────────────────────────

    public function markRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(array $result): void
    {
        $this->update([
            'status' => 'completed',
            'result_json' => $result,
            'finished_at' => now(),
            'message' => 'Done.',
            'progress' => $this->fullProgressArray(100),
            'zip_size' => $result['zip_size'] ?? null,
            'zip_sha256' => $result['zip_sha256'] ?? null,
            'targz_size' => $result['targz_size'] ?? null,
            'targz_sha256' => $result['targz_sha256'] ?? null,
        ]);

        Cache::forget($this->progressCacheKey());
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'finished_at' => now(),
            'message' => 'Error: '.$errorMessage,
        ]);
    }

    public function markCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
            'finished_at' => now(),
        ]);
    }

    /**
     * Store a progress snapshot in cache (fast) and optionally persist to DB
     * (throttled so we don't write on every percentage tick).
     *
     * @param  array  $progressData  Stage fields to merge (e.g. ['fileDownloadProgress' => 42])
     * @param  string  $message  Human-readable status message
     * @param  bool  $persistToDB  Force a DB write this tick (default: false for performance)
     */
    public function updateProgress(array $progressData, string $message = '', bool $persistToDB = false): void
    {
        $cacheKey = $this->progressCacheKey();

        $current = Cache::get($cacheKey, $this->defaultProgressArray());
        $merged = array_merge($current, $progressData);

        if ($message !== '') {
            $merged['packagingMessage'] = $message;
        }

        Cache::put($cacheKey, $merged, 600);

        if ($persistToDB) {
            $this->update([
                'progress' => $merged,
                'message' => $merged['packagingMessage'] ?? '',
            ]);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function defaultProgressArray(): array
    {
        return [
            'fileDownloadProgress' => 0,
            'headFileExtraction' => 0,
            'baseFileExtraction' => 0,
            'compareFilesProgress' => 0,
            'packageGenProgress' => 0,
            'compressionProgress' => 0,
            'packagingProgress' => 0,
            'packagingMessage' => 'Initializing...',
        ];
    }

    private function fullProgressArray(int $value): array
    {
        return [
            'fileDownloadProgress' => $value,
            'headFileExtraction' => $value,
            'baseFileExtraction' => $value,
            'compareFilesProgress' => $value,
            'packageGenProgress' => $value,
            'compressionProgress' => $value,
            'packagingProgress' => $value,
            'packagingMessage' => 'Done.',
        ];
    }
}
