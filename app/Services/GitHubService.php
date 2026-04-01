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
        // Create directory if it doesn't exist
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0755, true);
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
            $zip->extractTo($destinationPath);
            $zip->close();
            unlink($zipPath);

            // GitHub zipballs contain a single root folder (e.g. owner-repo-commitHash)
            // Move its contents up to destinationPath
            $extractedFolders = glob($destinationPath . '/*', GLOB_ONLYDIR);
            if (count($extractedFolders) === 1) {
                $innerFolder = $extractedFolders[0];
                $files = scandir($innerFolder);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        // For Windows we might want to use a robust move or File facade
                        \Illuminate\Support\Facades\File::move($innerFolder . DIRECTORY_SEPARATOR . $file, $destinationPath . DIRECTORY_SEPARATOR . $file);
                    }
                }
                rmdir($innerFolder);
            }

            return true;
        }

        return false;
    }
}
