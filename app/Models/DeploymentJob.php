<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class DeploymentJob extends Model
{
    public const PROGRESS_CACHE_TTL_SECONDS = 3600;

    protected $fillable = [
        'user_id',
        'project_id',
        'repository_id',
        'batch_id',
        'repo',
        'vcs_provider',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

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
        $resultForStorage = $this->resultForStorage($result);

        $this->update([
            'status' => 'completed',
            'result_json' => $resultForStorage,
            'finished_at' => now(),
            'message' => 'Done.',
            'progress' => $this->fullProgressArray(100),
            'zip_size' => $resultForStorage['zip_size'] ?? null,
            'zip_sha256' => $resultForStorage['zip_sha256'] ?? null,
            'targz_size' => $resultForStorage['targz_size'] ?? null,
            'targz_sha256' => $resultForStorage['targz_sha256'] ?? null,
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

        Cache::put($cacheKey, $merged, self::PROGRESS_CACHE_TTL_SECONDS);

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

    private function resultForStorage(array $result): array
    {
        $changedFiles = $result['changed_files'] ?? [];
        $changedFilesCount = is_countable($changedFiles)
            ? count($changedFiles)
            : (int) ($result['summary']['total_changes'] ?? 0);

        unset($result['changed_files']);

        $result['changed_files_count'] = $changedFilesCount;
        $result['summary'] = array_merge(
            ['total_changes' => $changedFilesCount],
            is_array($result['summary'] ?? null) ? $result['summary'] : []
        );

        return $result;
    }
}
