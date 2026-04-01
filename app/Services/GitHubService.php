<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

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

    public function downloadZip(string $owner, string $repo, string $ref, string $destinationPath): bool
    {
        // Only create the parent directory (temp/yymmddhhmmss) so the zip can download securely.
        // Don't create the target folder yet, otherwise Windows 'Access is Denied' Lock will occur during the rename later.
        $parentDir = dirname($destinationPath);
        if (!is_dir($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        $zipPath = $destinationPath . '.zip';
        
        $response = $this->client()->withOptions(['sink' => $zipPath])
            ->get("{$this->baseUrl}/repos/{$owner}/{$repo}/zipball/{$ref}");

        if (! $response->successful()) {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            return false;
        }

        // If the file is too big (e.g. > 50MB), leave it as a zip file to prevent memory/timeout issues
        $maxExtractionSize = 50 * 1024 * 1024; // 50 MB
        if (file_exists($zipPath) && filesize($zipPath) > $maxExtractionSize) {
            return true; // Return success but do not extract
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            $extractTempPath = $destinationPath . '_extract_' . uniqid();
            \Illuminate\Support\Facades\File::ensureDirectoryExists($extractTempPath);
            
            $zip->extractTo($extractTempPath);
            $zip->close();
            unlink($zipPath);

            // GitHub zipballs contain a single root folder (e.g. owner-repo-commitHash)
            $extractedFolders = glob($extractTempPath . '/*', GLOB_ONLYDIR);
            
            if (count($extractedFolders) === 1) {
                $innerFolder = $extractedFolders[0];
                
                // Destination path is usually created before this method is called and is empty.
                // We must remove it first so we can atomically rename the inner folder over it.
                if (is_dir($destinationPath)) {
                    // It should be empty, but just in case
                    \Illuminate\Support\Facades\File::deleteDirectory($destinationPath);
                }
                
                // Atomically rename the single inner folder to the destination path
                rename($innerFolder, $destinationPath);
            } else {
                // If it isn't wrapped in a single folder, just move the whole temp directory
                if (is_dir($destinationPath)) {
                    \Illuminate\Support\Facades\File::deleteDirectory($destinationPath);
                }
                rename($extractTempPath, $destinationPath);
            }
            
            // Clean up the extraction temp container if it still exists (e.g. if we moved the inner folder)
            if (is_dir($extractTempPath)) {
                \Illuminate\Support\Facades\File::deleteDirectory($extractTempPath);
            }

            return true;
        }

        return false;
    }
}
