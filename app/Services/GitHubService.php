<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    protected string $baseUrl = 'https://api.github.com';

    protected function client()
    {
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
            'User-Agent' => 'Laravel-App',
        ];

        $token = config('services.github.token');
        if (! empty($token)) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return Http::withHeaders($headers);
    }

    public function getRepository(string $owner, string $repo): Response
    {
        return $this->client()->get("{$this->baseUrl}/repos/{$owner}/{$repo}");
    }

    public function getBranches(string $owner, string $repo): Response
    {
        return $this->client()->get("{$this->baseUrl}/repos/{$owner}/{$repo}/branches");
    }

    public function getTags(string $owner, string $repo): Response
    {
        return $this->client()->get("{$this->baseUrl}/repos/{$owner}/{$repo}/tags");
    }

    public function getReleases(string $owner, string $repo): Response
    {
        return $this->client()->get("{$this->baseUrl}/repos/{$owner}/{$repo}/releases");
    }

    /**
     * @return array<string, mixed>|null Decoded JSON file contents, or null if missing or not successful
     */
    public function getFile(string $owner, string $repo, string $path, string $ref = 'main'): ?array
    {
        $response = $this->client()->get(
            "{$this->baseUrl}/repos/{$owner}/{$repo}/contents/{$path}",
            ['ref' => $ref]
        );

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        if (! is_array($data) || ! isset($data['content'])) {
            return null;
        }

        $decoded = json_decode(base64_decode((string) $data['content'], true), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function compare(string $owner, string $repo, string $base, string $head): array
    {
        $response = $this->client()->get(
            "{$this->baseUrl}/repos/{$owner}/{$repo}/compare/{$base}...{$head}"
        );

        return $response->json() ?? [];
    }

    public function getRateLimit(): Response
    {
        return $this->client()->get("{$this->baseUrl}/rate_limit");
    }

    /**
     * Download a repository ZIP. If a $progressCallback is provided it will be
     * called with (bytesDownloaded, totalBytes) approximately every 512 KB so
     * that callers can report real download progress to the cache.
     *
     * @param  callable|null  $progressCallback  fn(int $downloaded, int $total): void
     */
    public function downloadZip(
        string $owner,
        string $repo,
        string $ref,
        string $destinationZipPath,
        ?callable $progressCallback = null
    ): bool {
        $parentDir = dirname($destinationZipPath);
        if (! is_dir($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        $token = config('services.github.token');
        $url = "{$this->baseUrl}/repos/{$owner}/{$repo}/zipball/{$ref}";

        $fh = fopen($destinationZipPath, 'wb');
        if (! $fh) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_FILE => $fh,
            CURLOPT_HTTPHEADER => array_filter([
                'Accept: application/vnd.github+json',
                'X-GitHub-Api-Version: 2022-11-28',
                'User-Agent: Laravel-App',
                $token ? "Authorization: Bearer {$token}" : null,
            ]),
            CURLOPT_NOPROGRESS => $progressCallback === null,
        ]);

        $lastNotify = 0;
        $notifyEvery = 512 * 1024; // 512 KB

        if ($progressCallback !== null) {
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function (
                $resource,
                $dlTotal,
                $dlNow,
                $ulTotal,
                $ulNow
            ) use ($progressCallback, &$lastNotify, $notifyEvery) {
                $dlTotal = (int) $dlTotal;
                $dlNow = (int) $dlNow;

                if ($dlTotal <= 0 || $dlNow <= 0) {
                    return;
                }

                // Use abs() to handle the dlNow counter resetting after redirects
                // (which would make the raw difference negative and mute all updates).
                if (abs($dlNow - $lastNotify) >= $notifyEvery || $lastNotify === 0) {
                    $lastNotify = $dlNow;
                    ($progressCallback)($dlNow, $dlTotal);
                }
            });
        }

        $ok = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fh);

        if (! $ok || $httpCode < 200 || $httpCode >= 300) {
            $errorBody = '';
            if (file_exists($destinationZipPath)) {
                $errorBody = file_get_contents($destinationZipPath);
                unlink($destinationZipPath);
            }
            Log::error("GitHub download failed for {$url}", [
                'http_code' => $httpCode,
                'curl_ok' => $ok,
                'response' => substr($errorBody, 0, 1000), // Log first 1KB of error
            ]);

            return false;
        }

        // Final notification at 100 %
        if ($progressCallback !== null) {
            $size = filesize($destinationZipPath);
            ($progressCallback)($size, $size);
        }

        return true;
    }
}
