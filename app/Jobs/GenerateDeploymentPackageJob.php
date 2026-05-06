<?php

namespace App\Jobs;

use App\Exceptions\OAuthTokenRefreshException;
use App\Models\DeploymentJob;
use App\Models\User;
use App\Services\DeploymentPackageService;
use App\Services\OAuthTokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class JobCancelledException extends \RuntimeException {}

class GenerateDeploymentPackageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public readonly int $deploymentJobId) {}

    public function handle(DeploymentPackageService $service, OAuthTokenService $oauthTokens): void
    {
        /** @var DeploymentJob $job */
        $job = DeploymentJob::with('repository.user')->findOrFail($this->deploymentJobId);

        if ($job->status !== 'queued') {
            Log::warning("GenerateDeploymentPackageJob: job #{$job->id} has status '{$job->status}', skipping.");

            return;
        }

        $job->markRunning();

        $lastDbWrite = 0;
        $dbThrottleMs = 2000;
        $lastCancelCheck = 0;
        $cancelCheckMs = 1500;

        $progressCallback = function (array $data, string $message) use ($job, &$lastDbWrite, $dbThrottleMs, &$lastCancelCheck, $cancelCheckMs) {
            $nowMs = (int) (microtime(true) * 1000);
            if ($nowMs - $lastCancelCheck >= $cancelCheckMs) {
                $lastCancelCheck = $nowMs;

                $fresh = DeploymentJob::find($job->id);
                if ($fresh && $fresh->status === 'cancelled') {
                    throw new JobCancelledException("Job #{$job->id} was cancelled.");
                }
            }

            $cacheKey = $job->progressCacheKey();

            $current = Cache::get($cacheKey, $job->defaultProgressArray());
            $merged = array_merge($current, $data);
            if ($message !== '') {
                $merged['packagingMessage'] = $message;
            }

            if (! isset($data['packagingProgress'])) {
                $merged['packagingProgress'] = (int) round(
                    ($merged['fileDownloadProgress'] / 100) * 10 +
                    ($merged['headFileExtraction'] / 100) * 20 +
                    ($merged['baseFileExtraction'] / 100) * 20 +
                    ($merged['compareFilesProgress'] / 100) * 10 +
                    ($merged['packageGenProgress'] / 100) * 20 +
                    ($merged['compressionProgress'] / 100) * 20
                );
            }

            Cache::put($cacheKey, $merged, 600);

            if ($nowMs - $lastDbWrite >= $dbThrottleMs) {
                $lastDbWrite = $nowMs;
                $job->update([
                    'progress' => $merged,
                    'message' => $merged['packagingMessage'] ?? '',
                ]);
            }
        };

        $repository = $job->repository;
        $packageRepository = $repository?->name ?? $job->repo;
        $vcsProvider = $repository?->provider ?? $job->vcs_provider ?? 'github';
        $vcsToken = '';

        if (in_array($vcsProvider, ['github', 'gitlab'], true)) {
            if ($repository?->access_token) {
                $vcsToken = $repository->access_token;
            } else {
                $owner = $repository?->user ?? User::find($job->user_id);

                if ($owner) {
                    try {
                        $vcsToken = $oauthTokens->accessToken($owner, $vcsProvider) ?? '';
                    } catch (OAuthTokenRefreshException $e) {
                        $job->markFailed($e->getMessage());

                        return;
                    }
                }
            }
        }

        if ($repository && in_array($vcsProvider, ['github', 'gitlab'], true) && $vcsToken === '') {
            $job->markFailed('The repository owner needs to reconnect OAuth or save a PAT before this repository can be packaged.');

            return;
        }

        try {
            $result = $service->generate(
                $job->environment,
                $job->project_name,
                $job->base_version,
                $job->head_version,
                $packageRepository,
                $job->package_name,
                $progressCallback,
                $vcsProvider,
                $vcsToken
            );

            $job->refresh();
            if ($job->status === 'cancelled') {
                Log::info("GenerateDeploymentPackageJob: job #{$job->id} completed but status is 'cancelled' - discarding result.");

                return;
            }

            $job->markCompleted($result);

            Cache::put($job->progressCacheKey(), [
                'fileDownloadProgress' => 100,
                'headFileExtraction' => 100,
                'baseFileExtraction' => 100,
                'compareFilesProgress' => 100,
                'packageGenProgress' => 100,
                'compressionProgress' => 100,
                'packagingProgress' => 100,
                'packagingMessage' => 'Done.',
            ], 600);
        } catch (JobCancelledException $e) {
            Log::info("GenerateDeploymentPackageJob: job #{$job->id} was cancelled mid-run and stopped cleanly.");
        } catch (\Throwable $e) {
            Log::error("GenerateDeploymentPackageJob #{$this->deploymentJobId} failed: ".$e->getMessage(), [
                'exception' => $e,
            ]);
            $job->markFailed($e->getMessage());
        }
    }

    public function failed(\Throwable $exception): void
    {
        $job = DeploymentJob::find($this->deploymentJobId);
        if ($job && $job->status === 'running') {
            $job->markFailed('Job was killed by the queue worker: '.$exception->getMessage());
        }
    }
}
