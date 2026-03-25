<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GitHubService
{
    protected string $baseUrl = 'https://api.github.com';

    protected function client()
    {
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];

        if (!empty(env('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'))) {
            $headers['Authorization'] = 'Bearer ' . env('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');
        }

        return Http::withHeaders($headers);
    }

    public function getRepo(string $owner, string $repo)
    {
        return $this->client()->get("{$this->baseUrl}/repos/{$owner}/{$repo}")->json();
    }

    public function getBranches(string $owner, string $repo)
    {
        return $this->client()->get("{$this->baseUrl}/repos/{$owner}/{$repo}/branches")->json();
    }

    public function getTags(string $owner, string $repo)
    {
        return $this->client()->get("{$this->baseUrl}/repos/{$owner}/{$repo}/tags")->json();
    }

    public function getReleases(string $owner, string $repo)
    {
        return $this->client()->get("{$this->baseUrl}/repos/{$owner}/{$repo}/releases")->json();
    }

    public function getFile(string $owner, string $repo, string $path, string $ref = 'main')
    {
        $response = $this->client()->get(
            "{$this->baseUrl}/repos/{$owner}/{$repo}/contents/{$path}",
            ['ref' => $ref]
        )->json();

        if (!isset($response['content'])) {
            return null;
        }

        return json_decode(base64_decode($response['content']), true);
    }

    public function compare(string $owner, string $repo, string $base, string $head)
    {
        return $this->client()->get("{$this->baseUrl}/repos/{$owner}/{$repo}/compare/{$base}...{$head}")->json();
    }
}