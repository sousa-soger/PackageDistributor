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
}
