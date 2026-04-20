<?php

namespace App\Jobs;

use App\Models\DeploymentJob;
use App\Services\DeploymentPackageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Thrown inside the progress callback when the DB status flips to 'cancelled'.
 * Caught in handle() and treated as a clean stop — not a failure.
 */
class JobCancelledException extends \RuntimeException {}

class GenerateDeploymentPackageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     * Set to 1 — we do not auto-retry long-running generation jobs.
     */
    public int $tries = 1;

    /**
     * Maximum seconds the job may run before it is killed.
     * Matches the set_time_limit(600) from the old controller.
     */
    public int $timeout = 600;

    public function __construct(public readonly int $deploymentJobId)
    {
    }

    public function handle(DeploymentPackageService $service): void
    {
        /** @var DeploymentJob $job */
        $job = DeploymentJob::findOrFail($this->deploymentJobId);

        // Only process if the job is still queued (not already cancelled etc.)
        if ($job->status !== 'queued') {
            Log::warning("GenerateDeploymentPackageJob: job #{$job->id} has status '{$job->status}', skipping.");
            return;
        }

        $job->markRunning();

        // ── Progress callback wiring ──────────────────────────────────────────
        // Write every tick to cache (fast, polling-friendly).
        // Write to DB only every ~2 seconds to avoid excessive DB load.
        // On every tick we also re-read the DB status — if it flipped to
        // 'cancelled' we throw JobCancelledException to bail out immediately.

        $lastDbWrite      = 0;
        $dbThrottleMs     = 2000; // milliseconds
        $lastCancelCheck  = 0;
        $cancelCheckMs    = 1500; // check for cancellation every 1.5 s

        $progressCallback = function (array $data, string $message) use ($job, &$lastDbWrite, $dbThrottleMs, &$lastCancelCheck, $cancelCheckMs) {
            // ── Cancellation check ────────────────────────────────────────
            $nowMs = (int) (microtime(true) * 1000);
            if ($nowMs - $lastCancelCheck >= $cancelCheckMs) {
                $lastCancelCheck = $nowMs;
                // Fresh read — bypass any Eloquent model cache
                $fresh = DeploymentJob::find($job->id);
                if ($fresh && $fresh->status === 'cancelled') {
                    throw new JobCancelledException("Job #{$job->id} was cancelled.");
                }
            }

            $cacheKey = $job->progressCacheKey();

            $current = Cache::get($cacheKey, $job->defaultProgressArray());
            $merged  = array_merge($current, $data);
            if ($message !== '') {
                $merged['packagingMessage'] = $message;
            }

            // Derive weighted overall progress
            if (!isset($data['packagingProgress'])) {
                $merged['packagingProgress'] = (int) round(
                    ($merged['fileDownloadProgress']   / 100) * 10 +
                    ($merged['headFileExtraction']     / 100) * 20 +
                    ($merged['baseFileExtraction']     / 100) * 20 +
                    ($merged['compareFilesProgress']   / 100) * 10 +
                    ($merged['packageGenProgress']     / 100) * 20 +
                    ($merged['compressionProgress']    / 100) * 20
                );
            }

            Cache::put($cacheKey, $merged, 600);

            // Throttled DB write
            if ($nowMs - $lastDbWrite >= $dbThrottleMs) {
                $lastDbWrite = $nowMs;
                $job->update([
                    'progress' => $merged,
                    'message'  => $merged['packagingMessage'] ?? '',
                ]);
            }
        };

        // ── Run the service ───────────────────────────────────────────────────

        try {
            $result = $service->generate(
                $job->environment,
                $job->project_name,
                $job->base_version,
                $job->head_version,
                $job->repo,
                $job->package_name,
                $progressCallback
            );

            // Guard: job may have been cancelled while the final lines of
            // generate() were running (after the last progress tick).
            $job->refresh();
            if ($job->status === 'cancelled') {
                Log::info("GenerateDeploymentPackageJob: job #{$job->id} completed but status is 'cancelled' — discarding result.");
                return;
            }

            $job->markCompleted($result);

            // Also write the final full-100 snapshot to cache
            Cache::put($job->progressCacheKey(), [
                'fileDownloadProgress' => 100,
                'headFileExtraction'   => 100,
                'baseFileExtraction'   => 100,
                'compareFilesProgress' => 100,
                'packageGenProgress'   => 100,
                'compressionProgress'  => 100,
                'packagingProgress'    => 100,
                'packagingMessage'     => 'Done.',
            ], 600);

        } catch (JobCancelledException $e) {
            // Clean stop — the DB already has status='cancelled', nothing to update.
            Log::info("GenerateDeploymentPackageJob: job #{$job->id} was cancelled mid-run and stopped cleanly.");

        } catch (\Throwable $e) {
            Log::error("GenerateDeploymentPackageJob #{$this->deploymentJobId} failed: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            $job->markFailed($e->getMessage());
        }
    }

    /**
     * Handle a job failure triggered by the queue worker itself
     * (e.g. timeout, out-of-memory kill signal).
     */
    public function failed(\Throwable $exception): void
    {
        $job = DeploymentJob::find($this->deploymentJobId);
        // Don't overwrite a deliberate cancellation
        if ($job && $job->status === 'running') {
            $job->markFailed('Job was killed by the queue worker: ' . $exception->getMessage());
        }
    }
}

