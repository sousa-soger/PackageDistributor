<?php

namespace App\Http\Controllers;

use App\Exceptions\OAuthTokenRefreshException;
use App\Models\Repository;
use App\Models\User;
use App\Services\GitHubService;
use App\Services\LdapService;
use App\Services\OAuthTokenService;
use App\Services\ProjectInvolvementService;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Symfony\Component\Process\Process;
use ZipArchive;

class RepositoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $repositories = Repository::query()
            ->where(fn ($query) => $query
                ->where('user_id', $user->id)
                ->orWhereHas('members', fn ($query) => $query->whereKey($user->id)))
            ->with(['members', 'user'])
            ->latest()
            ->get();

        $repositoryCards = $repositories
            ->map(fn (Repository $repository) => $this->repositoryPayload($repository, $user))
            ->values();

        $repositoryRoleOptions = $this->repositoryRoleOptions();

        $oauthConnections = [
            'github' => (bool) $user->github_token,
            'gitlab' => (bool) $user->gitlab_token,
        ];

        return view('repositories', compact('oauthConnections', 'repositories', 'repositoryCards', 'repositoryRoleOptions'));
    }

    public function store(Request $request, GitHubService $github, OAuthTokenService $oauthTokens): JsonResponse
    {
        $provider = $request->input('provider');

        $rules = [
            'access_token' => ['nullable', 'string', 'max:500'],
            'auth_method' => ['nullable', Rule::in(['oauth', 'pat'])],
            'display_name' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:500'],
            'provider' => ['required', Rule::in(['github', 'gitlab', 'company-server'])],
            'server_host' => ['nullable', 'string', 'max:255'],
            'server_path' => ['nullable', 'string', 'max:500'],
            'server_protocol' => ['nullable', Rule::in(['SSH', 'SFTP', 'HTTP', 'HTTPS'])],
            'url' => ['nullable', 'string', 'max:500'],
            'username' => ['nullable', 'string', 'max:255'],
        ];

        if (in_array($provider, ['github', 'gitlab'], true)) {
            $rules['auth_method'][] = 'required';
            $rules['access_token'][] = Rule::requiredIf(
                fn () => $request->input('auth_method') === 'pat'
            );
        }

        $validated = $request->validate($rules);
        $normalized = $this->normalizeRepositoryInput(
            $validated['provider'],
            $validated['name'],
            $validated['url'] ?? null
        );

        if (! $normalized) {
            return response()->json([
                'message' => 'Invalid repository format. Use owner/repo or a full repository URL.',
            ], 422);
        }

        $exists = $request->user()->repositories()
            ->where('provider', $validated['provider'])
            ->where('name', $normalized['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This repository is already connected to your account.',
            ], 422);
        }

        $metadata = [
            'access_token' => null,
            'branches' => [],
            'default_branch' => 'main',
            'external_id' => null,
            'status' => 'connected',
            'tags' => [],
        ];

        if ($validated['provider'] === 'github') {
            if ($validated['auth_method'] === 'oauth') {
                $metadata = $this->fetchGitHubMetadataWithOAuth(
                    $github,
                    $request->user(),
                    $oauthTokens,
                    $normalized['name']
                );
            } else {
                $metadata = $this->fetchGitHubMetadata($github, $normalized['name'], $validated['access_token']);
            }

            if (! $metadata['ok']) {
                return $this->githubConnectionErrorResponse($metadata);
            }

            $metadata['access_token'] = $validated['auth_method'] === 'pat'
                ? $validated['access_token']
                : null;
        }

        if ($validated['provider'] === 'gitlab') {
            $oauthToken = null;
            if ($validated['auth_method'] === 'oauth') {
                [$oauthToken, $authResponse] = $this->oauthTokenForProvider($request->user(), 'gitlab', $oauthTokens);
                if ($authResponse) {
                    return $authResponse;
                }
            }

            $token = $validated['auth_method'] === 'pat'
                ? $validated['access_token']
                : $oauthToken;

            if ($validated['auth_method'] === 'oauth' && ! $oauthToken) {
                return response()->json([
                    'message' => 'Connect your GitLab account first to use OAuth.',
                    'redirect_url' => route('gitlab.oauth.redirect', ['return_to' => 'repositories']),
                    'requires_oauth' => true,
                ], 409);
            }

            $metadata = $this->fetchGitLabMetadata($normalized['name'], $token);

            if (! $metadata['ok']) {
                return response()->json([
                    'message' => $metadata['message'],
                ], 422);
            }

            $metadata['access_token'] = $validated['auth_method'] === 'pat'
                ? $validated['access_token']
                : null;
        }

        if ($validated['provider'] === 'company-server') {
            $metadata['access_token'] = $validated['access_token'] ?? null;
            $metadata['status'] = 'connected';
        }

        $repository = $request->user()->repositories()->create([
            'access_token' => $metadata['access_token'],
            'branches' => $metadata['branches'],
            'default_branch' => $metadata['default_branch'],
            'display_name' => $validated['display_name'] ?? $normalized['display_name'],
            'external_id' => $metadata['external_id'],
            'last_synced_at' => now(),
            'name' => $normalized['name'],
            'project_id' => null,
            'provider' => $validated['provider'],
            'server_host' => $validated['server_host'] ?? $normalized['server_host'],
            'server_path' => $validated['server_path'] ?? null,
            'server_protocol' => $validated['server_protocol'] ?? $normalized['server_protocol'],
            'status' => $metadata['status'],
            'tags' => $metadata['tags'],
            'url' => $normalized['url'],
            'username' => $validated['username'] ?? null,
        ]);

        return response()->json([
            'message' => 'Repository connected successfully.',
            'repository' => $repository->fresh(),
        ], 201);
    }

    public function sync(Repository $repository, GitHubService $github, OAuthTokenService $oauthTokens): JsonResponse
    {
        $this->authorize('update', $repository);
        $repository->loadMissing('user');

        if ($repository->provider === 'local-pc') {
            return $this->syncLocalRepository($repository, $repository->type === 'ssh-mirror');
        }

        if ($repository->provider === 'github') {
            $metadata = $repository->access_token
                ? $this->fetchGitHubMetadata($github, $repository->name, $repository->access_token)
                : (
                    $repository->user
                        ? $this->fetchGitHubMetadataWithOAuth($github, $repository->user, $oauthTokens, $repository->name)
                        : [
                            'message' => 'This repository no longer has an owner account.',
                            'ok' => false,
                        ]
                );

            if (! $metadata['ok']) {
                if (! empty($metadata['requires_oauth'])) {
                    $repository->update(['status' => 'needs-auth']);
                }

                return $this->githubConnectionErrorResponse($metadata);
            }

            $repository->update([
                'branches' => $metadata['branches'],
                'default_branch' => $metadata['default_branch'],
                'external_id' => $metadata['external_id'],
                'last_synced_at' => now(),
                'status' => 'connected',
                'tags' => $metadata['tags'],
            ]);
        }

        if ($repository->provider === 'gitlab') {
            $token = $repository->access_token;
            if (! $token && $repository->user) {
                [$token, $authResponse] = $this->oauthTokenForProvider($repository->user, 'gitlab', $oauthTokens);
                if ($authResponse) {
                    $repository->update(['status' => 'needs-auth']);

                    return $authResponse;
                }
            }

            if (! $token) {
                $repository->update(['status' => 'needs-auth']);

                return response()->json([
                    'message' => 'Reconnect GitLab OAuth or provide a PAT to sync this repository.',
                ], 422);
            }

            $metadata = $this->fetchGitLabMetadata($repository->name, $token);

            if (! $metadata['ok']) {
                $repository->update(['status' => 'needs-auth']);

                return response()->json([
                    'message' => $metadata['message'],
                ], 422);
            }

            $repository->update([
                'branches' => $metadata['branches'],
                'default_branch' => $metadata['default_branch'],
                'external_id' => $metadata['external_id'],
                'last_synced_at' => now(),
                'status' => 'connected',
                'tags' => $metadata['tags'],
            ]);
        }

        return response()->json([
            'message' => 'Repository synced.',
            'repository' => $repository->fresh(),
        ]);
    }

    public function sshPublicKey(): JsonResponse
    {
        return response()->json([
            'public_key' => $this->ensureSshPublicKey(),
        ]);
    }

    public function connectSsh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ip' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:500'],
        ]);

        if ($request->user()->repositories()->where('provider', 'local-pc')->where('name', $validated['name'])->exists()) {
            return response()->json([
                'message' => 'This repository is already connected to your account.',
                'success' => false,
            ], 422);
        }

        $repository = $request->user()->repositories()->create([
            'branches' => [],
            'default_branch' => 'main',
            'display_name' => $validated['name'],
            'has_git_history' => true,
            'name' => $validated['name'],
            'provider' => 'local-pc',
            'remote_ip' => $validated['ip'],
            'remote_path' => $validated['path'],
            'server_path' => $validated['path'],
            'server_protocol' => 'SSH',
            'status' => 'connected',
            'tags' => [],
            'type' => 'ssh-mirror',
            'url' => "ssh://owner@{$validated['ip']}:{$validated['path']}",
        ]);

        $storagePath = storage_path("app/repos/{$repository->id}.git");

        try {
            File::ensureDirectoryExists(dirname($storagePath));

            $process = new Process([
                'git',
                'clone',
                '--mirror',
                "ssh://owner@{$validated['ip']}:{$validated['path']}",
                $storagePath,
            ]);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput() ?: $process->getOutput());
            }

            $repository->update(array_merge([
                'last_synced_at' => now(),
                'storage_path' => $storagePath,
            ], $this->localRepositoryRefAttributes($storagePath)));

            return response()->json([
                'repository' => [
                    'id' => $repository->id,
                    'last_synced_at' => $repository->last_synced_at?->toIso8601String(),
                    'name' => $repository->name,
                ],
                'success' => true,
            ], 201);
        } catch (\Throwable $e) {
            File::deleteDirectory($storagePath);
            $repository->delete();

            return response()->json([
                'message' => $this->humanGitError($e->getMessage()),
                'success' => false,
            ], 422);
        }
    }

    public function syncSsh(Repository $repository): JsonResponse
    {
        $this->authorize('update', $repository);

        if ($repository->type !== 'ssh-mirror' || ! $repository->storage_path) {
            return response()->json([
                'message' => 'This repository is not connected through SSH access.',
                'success' => false,
            ], 422);
        }

        return $this->syncLocalRepository($repository, true);
    }

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:512000'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $uploadedFile = $validated['file'];
        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        if (! in_array($extension, ['bundle', 'zip'], true)) {
            return response()->json([
                'message' => 'Upload a ZIP archive or Git bundle file.',
                'success' => false,
            ], 422);
        }

        if ($request->user()->repositories()->where('provider', 'local-pc')->where('name', $validated['name'])->exists()) {
            return response()->json([
                'message' => 'This repository is already connected to your account.',
                'success' => false,
            ], 422);
        }

        $repository = $request->user()->repositories()->create([
            'branches' => [],
            'default_branch' => 'main',
            'display_name' => $validated['name'],
            'has_git_history' => true,
            'name' => $validated['name'],
            'provider' => 'local-pc',
            'status' => 'connected',
            'tags' => [],
            'type' => 'uploaded',
            'url' => $uploadedFile->getClientOriginalName(),
        ]);

        $storagePath = $this->availableRepositoryStoragePath($repository);
        $tmpDir = storage_path('app/tmp/'.$repository->id.'-upload-'.uniqid());
        $warning = null;

        try {
            File::ensureDirectoryExists($tmpDir);
            File::ensureDirectoryExists(dirname($storagePath));

            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $uploadedFile->getClientOriginalName());
            $tmpFile = $uploadedFile->move($tmpDir, $safeName)->getPathname();

            if ($extension === 'bundle') {
                $this->processBundleUpload($tmpFile, $storagePath);
            } else {
                $hasGitHistory = $this->processZipUpload($tmpFile, $tmpDir, $storagePath);

                if (! $hasGitHistory) {
                    $warning = 'No .git folder found. File contents were imported but version history is not available.';
                    $repository->has_git_history = false;
                }
            }

            $repository->fill(array_merge([
                'last_synced_at' => now(),
                'storage_path' => $storagePath,
            ], $this->localRepositoryRefAttributes($storagePath)))->save();

            return response()->json([
                'repository' => [
                    'id' => $repository->id,
                    'name' => $repository->name,
                ],
                'success' => true,
                'warning' => $warning,
            ], 201);
        } catch (\Throwable $e) {
            File::deleteDirectory($storagePath);
            $repository->delete();

            return response()->json([
                'message' => $this->humanGitError($e->getMessage()),
                'success' => false,
            ], 422);
        } finally {
            File::deleteDirectory($tmpDir);
        }
    }

    public function uploadVersion(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('update', $repository);

        if ($repository->provider !== 'local-pc' || $repository->type !== 'uploaded') {
            return response()->json([
                'message' => 'Only uploaded Local PC repositories can receive a new uploaded version.',
                'success' => false,
            ], 422);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:512000'],
        ]);

        $uploadedFile = $validated['file'];
        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        if (! in_array($extension, ['bundle', 'zip'], true)) {
            return response()->json([
                'message' => 'Upload a ZIP archive or Git bundle file.',
                'success' => false,
            ], 422);
        }

        $storagePath = $repository->storage_path ?: storage_path("app/repos/{$repository->id}.git");
        $tmpDir = storage_path('app/tmp/'.$repository->id.'-upload-version-'.uniqid());
        $replacementPath = storage_path('app/repos/'.$repository->id.'.upload-version.'.uniqid().'.git');
        $warning = null;

        try {
            File::ensureDirectoryExists($tmpDir);
            File::ensureDirectoryExists(dirname($storagePath));
            File::ensureDirectoryExists(dirname($replacementPath));

            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $uploadedFile->getClientOriginalName());
            $tmpFile = $uploadedFile->move($tmpDir, $safeName)->getPathname();

            $hasGitHistory = $extension === 'bundle'
                ? $this->processBundleUploadVersion($tmpFile, $replacementPath)
                : $this->processZipUploadVersion($tmpFile, $tmpDir, $storagePath, $replacementPath);

            if (! $hasGitHistory) {
                $warning = 'No .git folder found. A new snapshot commit was created from the uploaded files.';
            }

            $this->replaceDirectory($replacementPath, $storagePath);

            $repository->fill(array_merge([
                'has_git_history' => $hasGitHistory,
                'last_synced_at' => now(),
                'status' => 'connected',
                'storage_path' => $storagePath,
                'url' => $uploadedFile->getClientOriginalName(),
            ], $this->localRepositoryRefAttributes($storagePath)))->save();

            return response()->json([
                'message' => 'Repository version uploaded.',
                'repository' => $this->repositoryPayload($repository->fresh(), $request->user()),
                'success' => true,
                'warning' => $warning,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $this->humanGitError($e->getMessage()),
                'success' => false,
            ], 422);
        } finally {
            File::deleteDirectory($tmpDir);
            File::deleteDirectory($replacementPath);
        }
    }

    protected function syncLocalRepository(Repository $repository, bool $fetchRemote): JsonResponse
    {
        if (! in_array($repository->type, ['ssh-mirror', 'uploaded'], true) || ! $repository->storage_path || ! File::isDirectory($repository->storage_path)) {
            return response()->json([
                'message' => 'This local repository is missing its stored Git mirror. Reconnect or upload it again before syncing.',
                'success' => false,
            ], 422);
        }

        if ($fetchRemote) {
            $process = new Process(['git', 'fetch', '--all'], $repository->storage_path);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                return response()->json([
                    'message' => $this->humanGitError($process->getErrorOutput() ?: $process->getOutput()),
                    'success' => false,
                ], 422);
            }
        }

        try {
            $repository->update(array_merge([
                'last_synced_at' => now(),
                'status' => 'connected',
            ], $this->localRepositoryRefAttributes($repository->storage_path)));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Could not read branches and tags from this local repository: '.$e->getMessage(),
                'success' => false,
            ], 422);
        }

        return response()->json([
            'last_synced_at' => $repository->last_synced_at?->toIso8601String(),
            'message' => 'Repository synced.',
            'repository' => $repository->fresh(),
            'success' => true,
        ]);
    }

    /**
     * @return array{branches: list<string>, default_branch: string, tags: list<string>}
     */
    protected function localRepositoryRefAttributes(string $gitDirectory): array
    {
        $branches = $this->localGitRefs($gitDirectory, 'refs/heads');
        $tags = $this->localGitRefs($gitDirectory, 'refs/tags');

        return [
            'branches' => $branches,
            'default_branch' => $this->localDefaultBranch($gitDirectory, $branches),
            'tags' => $tags,
        ];
    }

    /**
     * @param  list<string>  $branches
     */
    protected function localDefaultBranch(string $gitDirectory, array $branches): string
    {
        $process = new Process([
            'git',
            "--git-dir={$gitDirectory}",
            'symbolic-ref',
            '--quiet',
            '--short',
            'HEAD',
        ]);
        $process->setTimeout(30);
        $process->run();

        if ($process->isSuccessful()) {
            $branch = trim($process->getOutput());

            if ($branch !== '') {
                return $branch;
            }
        }

        if (in_array('main', $branches, true)) {
            return 'main';
        }

        if (in_array('master', $branches, true)) {
            return 'master';
        }

        return $branches[0] ?? 'main';
    }

    protected function ensureSshPublicKey(): string
    {
        $sshDir = storage_path('app/ssh');
        $privateKey = "{$sshDir}/id_rsa";
        $publicKey = "{$privateKey}.pub";

        if (! File::exists($publicKey)) {
            File::ensureDirectoryExists($sshDir, 0700);

            $process = new Process([
                'ssh-keygen',
                '-t',
                'rsa',
                '-b',
                '4096',
                '-f',
                $privateKey,
                '-N',
                '',
            ]);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException('Unable to generate the server SSH key. Make sure ssh-keygen is installed.');
            }
        }

        return trim(File::get($publicKey));
    }

    protected function processBundleUpload(string $bundlePath, string $storagePath): void
    {
        $verify = new Process(['git', 'bundle', 'verify', $bundlePath]);
        $verify->setTimeout(120);
        $verify->run();

        if (! $verify->isSuccessful()) {
            throw new \RuntimeException($verify->getErrorOutput() ?: $verify->getOutput());
        }

        $clone = new Process(['git', 'clone', '--mirror', $bundlePath, $storagePath]);
        $clone->setTimeout(120);
        $clone->run();

        if (! $clone->isSuccessful()) {
            throw new \RuntimeException($clone->getErrorOutput() ?: $clone->getOutput());
        }
    }

    protected function availableRepositoryStoragePath(Repository $repository): string
    {
        $storagePath = storage_path("app/repos/{$repository->id}.git");

        if (! File::isDirectory($storagePath)) {
            return $storagePath;
        }

        return storage_path('app/repos/'.$repository->id.'-'.uniqid().'.git');
    }

    protected function processBundleUploadVersion(string $bundlePath, string $replacementPath): bool
    {
        $this->processBundleUpload($bundlePath, $replacementPath);

        return true;
    }

    protected function processZipUpload(string $zipPath, string $tmpDir, string $storagePath): bool
    {
        $extractDir = "{$tmpDir}/extracted";
        File::deleteDirectory($extractDir);
        File::ensureDirectoryExists($extractDir);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('The ZIP archive could not be opened.');
        }

        $zip->extractTo($extractDir);
        $zip->close();

        $gitDirectory = $this->findGitDirectory($extractDir);

        if ($gitDirectory) {
            File::copyDirectory($gitDirectory, $storagePath);

            $config = new Process(['git', "--git-dir={$storagePath}", 'config', '--bool', 'core.bare', 'true']);
            $config->setTimeout(120);
            $config->run();

            if (! $config->isSuccessful()) {
                throw new \RuntimeException($config->getErrorOutput() ?: $config->getOutput());
            }

            return true;
        }

        $contentRoot = $this->uploadedContentRoot($extractDir);
        $this->importFilesIntoBareRepository($contentRoot, $storagePath);

        return false;
    }

    protected function processZipUploadVersion(string $zipPath, string $tmpDir, string $storagePath, string $replacementPath): bool
    {
        $extractDir = "{$tmpDir}/version-extracted";
        File::deleteDirectory($extractDir);
        File::ensureDirectoryExists($extractDir);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('The ZIP archive could not be opened.');
        }

        $zip->extractTo($extractDir);
        $zip->close();

        $gitDirectory = $this->findGitDirectory($extractDir);

        if ($gitDirectory) {
            File::copyDirectory($gitDirectory, $replacementPath);

            $config = new Process(['git', "--git-dir={$replacementPath}", 'config', '--bool', 'core.bare', 'true']);
            $config->setTimeout(120);
            $config->run();

            if (! $config->isSuccessful()) {
                throw new \RuntimeException($config->getErrorOutput() ?: $config->getOutput());
            }

            return true;
        }

        $contentRoot = $this->uploadedContentRoot($extractDir);
        $this->importSnapshotVersionIntoBareRepository($contentRoot, $storagePath, $replacementPath, "{$tmpDir}/version-worktree");

        return false;
    }

    protected function findGitDirectory(string $root): ?string
    {
        $items = scandir($root);

        if ($items === false) {
            return null;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $root.DIRECTORY_SEPARATOR.$item;

            if (! is_dir($path)) {
                continue;
            }

            if ($item === '.git') {
                return $path;
            }

            $nested = $this->findGitDirectory($path);
            if ($nested) {
                return $nested;
            }
        }

        return null;
    }

    protected function uploadedContentRoot(string $extractDir): string
    {
        $directories = File::directories($extractDir);
        $files = File::files($extractDir);

        if (count($directories) === 1 && count($files) === 0) {
            return $directories[0];
        }

        return $extractDir;
    }

    protected function importFilesIntoBareRepository(string $contentRoot, string $storagePath): void
    {
        $commands = [
            ['git', 'init'],
            ['git', 'add', '-A', '--force'],
            ['git', '-c', 'user.name=Cybix Upload', '-c', 'user.email=cybix@example.invalid', 'commit', '--allow-empty', '-m', 'Import uploaded repository'],
            ['git', 'clone', '--mirror', $contentRoot, $storagePath],
        ];

        foreach ($commands as $command) {
            $process = new Process($command, in_array('clone', $command, true) ? null : $contentRoot);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput() ?: $process->getOutput());
            }
        }
    }

    protected function importSnapshotVersionIntoBareRepository(string $contentRoot, string $storagePath, string $replacementPath, string $workTree): void
    {
        if (! File::isDirectory($storagePath)) {
            $this->importFilesIntoBareRepository($contentRoot, $replacementPath);

            return;
        }

        $clone = new Process(['git', 'clone', $storagePath, $workTree]);
        $clone->setTimeout(120);
        $clone->run();

        if (! $clone->isSuccessful()) {
            throw new \RuntimeException($clone->getErrorOutput() ?: $clone->getOutput());
        }

        $this->clearWorkingTree($workTree);
        File::copyDirectory($contentRoot, $workTree);

        $commands = [
            ['git', 'add', '-A', '--force'],
            ['git', '-c', 'user.name=Cybix Upload', '-c', 'user.email=cybix@example.invalid', 'commit', '--allow-empty', '-m', 'Import uploaded repository snapshot'],
            ['git', 'clone', '--mirror', $workTree, $replacementPath],
        ];

        foreach ($commands as $command) {
            $process = new Process($command, in_array('clone', $command, true) ? null : $workTree);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput() ?: $process->getOutput());
            }
        }
    }

    protected function clearWorkingTree(string $workTree): void
    {
        foreach (File::files($workTree) as $file) {
            File::delete($file->getPathname());
        }

        foreach (File::directories($workTree) as $directory) {
            if (basename($directory) === '.git') {
                continue;
            }

            File::deleteDirectory($directory);
        }
    }

    protected function replaceDirectory(string $source, string $target): void
    {
        if (File::isDirectory($target)) {
            $this->refreshExistingMirror($source, $target);
            File::deleteDirectory($source);

            return;
        }

        if (File::moveDirectory($source, $target)) {
            return;
        }

        if (File::copyDirectory($source, $target)) {
            File::deleteDirectory($source);

            return;
        }

        throw new \RuntimeException('Could not store the uploaded repository mirror.');
    }

    protected function refreshExistingMirror(string $source, string $target): void
    {
        $removeRemote = new Process(['git', "--git-dir={$target}", 'remote', 'remove', 'upload-replacement']);
        $removeRemote->setTimeout(30);
        $removeRemote->run();

        $commands = [
            ['git', "--git-dir={$target}", 'remote', 'add', '--mirror=fetch', 'upload-replacement', $source],
            ['git', "--git-dir={$target}", 'fetch', '--prune', 'upload-replacement'],
            ['git', "--git-dir={$target}", 'remote', 'remove', 'upload-replacement'],
        ];

        foreach ($commands as $command) {
            $process = new Process($command);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput() ?: $process->getOutput());
            }
        }

        $branches = $this->localGitRefs($source, 'refs/heads');
        $defaultBranch = $this->localDefaultBranch($source, $branches);
        $setHead = new Process(['git', "--git-dir={$target}", 'symbolic-ref', 'HEAD', "refs/heads/{$defaultBranch}"]);
        $setHead->setTimeout(30);
        $setHead->run();

        if (! $setHead->isSuccessful()) {
            throw new \RuntimeException($setHead->getErrorOutput() ?: $setHead->getOutput());
        }
    }

    protected function humanGitError(string $output): string
    {
        $normalized = strtolower($output);

        if (str_contains($normalized, 'connection refused') || str_contains($normalized, 'port 22')) {
            return 'Connection refused. Check your IP address, SSH service, and firewall.';
        }

        if (str_contains($normalized, 'permission denied') || str_contains($normalized, 'publickey')) {
            return 'Authentication failed. Make sure the server public key was added to your machine.';
        }

        if (str_contains($normalized, 'no such file or directory') || str_contains($normalized, 'does not exist')) {
            return 'Path not found. Check the repository path on the remote machine.';
        }

        return 'Unknown error: '.trim($output);
    }

    public function versions(Request $request, Repository $repository, GitHubService $github, OAuthTokenService $oauthTokens): JsonResponse
    {
        $this->authorize('createPackage', $repository);

        if ($repository->provider === 'local-pc') {
            return $this->localRepositoryVersions($repository);
        }

        if (! in_array($repository->provider, ['github', 'gitlab'], true)) {
            return response()->json([
                'message' => 'Package creation is not available for this repository type.',
            ], 422);
        }

        [$token, $authResponse] = $this->repositoryAccessToken($repository, $request->user(), $oauthTokens);
        if ($authResponse) {
            return $authResponse;
        }

        if ($repository->provider === 'github') {
            $parsed = $this->parseGitHubOwnerRepo($repository->name);

            if (! $parsed) {
                return response()->json([
                    'message' => 'GitHub repositories must use the format owner/repo.',
                ], 422);
            }

            [$owner, $repo] = $parsed;
            $branchesResponse = $github->getBranches($owner, $repo, $token);
            $tagsResponse = $github->getTags($owner, $repo, $token);
            $releasesResponse = $github->getReleases($owner, $repo, $token);

            if ($branchesResponse->failed() || $tagsResponse->failed() || $releasesResponse->failed()) {
                return response()->json([
                    'message' => 'Failed to fetch repository versions with the repository owner credentials.',
                ], 422);
            }

            return response()->json([
                'branches' => $branchesResponse->json() ?? [],
                'tags' => $tagsResponse->json() ?? [],
                'releases' => $releasesResponse->json() ?? [],
            ]);
        }

        $projectPath = rawurlencode($repository->external_id ?: $repository->name);
        $baseUrl = rtrim(config('services.gitlab.base_url', 'https://gitlab.com'), '/');

        $branchesResponse = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v4/projects/{$projectPath}/repository/branches", [
                'per_page' => 100,
            ]);

        $tagsResponse = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v4/projects/{$projectPath}/repository/tags", [
                'per_page' => 100,
            ]);

        if ($branchesResponse->failed() || $tagsResponse->failed()) {
            return response()->json([
                'message' => 'Failed to fetch repository versions with the repository owner credentials.',
            ], 422);
        }

        return response()->json([
            'branches' => collect($branchesResponse->json())->map(fn (array $branch) => ['name' => $branch['name']])->values(),
            'tags' => collect($tagsResponse->json())->map(fn (array $tag) => ['name' => $tag['name']])->values(),
            'releases' => [],
        ]);
    }

    protected function localRepositoryVersions(Repository $repository): JsonResponse
    {
        if (! $repository->storage_path || ! File::isDirectory($repository->storage_path)) {
            return response()->json([
                'message' => 'This local repository is missing its stored Git mirror. Reconnect or upload it again before creating a package.',
            ], 422);
        }

        try {
            $branches = $this->localGitRefs($repository->storage_path, 'refs/heads');
            $tags = $this->localGitRefs($repository->storage_path, 'refs/tags');
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Could not read versions from this local repository: '.$e->getMessage(),
            ], 422);
        }

        return response()->json([
            'branches' => collect($branches)->map(fn (string $branch) => ['name' => $branch])->values(),
            'tags' => collect($tags)->map(fn (string $tag) => ['name' => $tag])->values(),
            'releases' => [],
        ]);
    }

    /**
     * @return list<string>
     */
    protected function localGitRefs(string $gitDirectory, string $refPrefix): array
    {
        $process = new Process([
            'git',
            "--git-dir={$gitDirectory}",
            'for-each-ref',
            '--format=%(refname:short)',
            $refPrefix,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        return collect(preg_split('/\r\n|\r|\n/', trim($process->getOutput())) ?: [])
            ->map(fn (string $ref) => trim($ref))
            ->filter()
            ->values()
            ->all();
    }

    public function destroy(Repository $repository): JsonResponse
    {
        $this->authorize('delete', $repository);

        $this->deleteLocalRepositoryStorage($repository);

        $repository->delete();

        return response()->json(['message' => 'Repository removed.']);
    }

    protected function deleteLocalRepositoryStorage(Repository $repository): void
    {
        if ($repository->provider !== 'local-pc' || blank($repository->storage_path)) {
            return;
        }

        $storagePath = $this->normalizedPath($repository->storage_path);
        $managedRepoRoot = $this->normalizedPath(storage_path('app/repos'));

        if (! str_starts_with($storagePath, $managedRepoRoot.DIRECTORY_SEPARATOR)) {
            return;
        }

        File::deleteDirectory($storagePath);
    }

    protected function normalizedPath(string $path): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    public function members(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);

        return response()->json($this->repositoryMembersPayload($repository, $request->user()));
    }

    public function searchUsers(Request $request, Repository $repository, LdapService $ldap): JsonResponse
    {
        $this->authorize('manageMembers', $repository);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        if (mb_strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        $directoryUsers = collect($ldap->searchUsers($query, 8));
        $usernames = $directoryUsers->pluck('username')->filter()->values()->all();
        $emails = $directoryUsers->pluck('email')->filter()->values()->all();

        $existingUsers = empty($usernames) && empty($emails)
            ? collect()
            : User::query()
                ->where(function ($query) use ($emails, $usernames) {
                    if (! empty($usernames)) {
                        $query->whereIn('ldap_username', $usernames);
                    }

                    if (! empty($emails)) {
                        $query->orWhereIn('email', $emails);
                    }
                })
                ->get();

        $repositoryMemberIds = $repository->members()->pluck('users.id')->all();

        $results = $directoryUsers->map(function (array $directoryUser) use ($existingUsers, $repository, $repositoryMemberIds) {
            $existingUser = $existingUsers->first(function (User $user) use ($directoryUser) {
                return ($directoryUser['username'] && $user->ldap_username === $directoryUser['username'])
                    || ($directoryUser['email'] && $user->email === $directoryUser['email']);
            });

            $alreadyMember = $existingUser
                ? in_array($existingUser->id, $repositoryMemberIds, true) || $existingUser->id === $repository->user_id
                : false;

            return [
                'already_member' => $alreadyMember,
                'avatar' => $directoryUser['avatar'],
                'email' => $directoryUser['email'],
                'id' => $existingUser?->id,
                'name' => $directoryUser['name'],
                'username' => $directoryUser['username'],
            ];
        })->values();

        return response()->json(['users' => $results]);
    }

    public function storeUser(Request $request, Repository $repository, LdapService $ldap): JsonResponse
    {
        $this->authorize('manageMembers', $repository);

        $validated = $request->validate([
            'role' => ['nullable', Rule::in($this->repositoryRoleKeys())],
            'username' => ['required', 'string', 'max:255'],
        ]);

        $directoryUser = $ldap->findUser($validated['username']);

        if (! $directoryUser) {
            return response()->json([
                'message' => 'No matching LDAP user was found.',
            ], 404);
        }

        try {
            $member = $ldap->syncLocalUser($directoryUser);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        if ($member->id === $repository->user_id) {
            return response()->json([
                'message' => 'The repository owner already has access to this repository.',
            ], 422);
        }

        $pivot = [
            'source' => 'ldap',
            'ldap_identifier' => $directoryUser['username'] ?? $validated['username'],
            'role' => $validated['role'] ?? ProjectInvolvementService::DEFAULT_PROJECT_ROLE,
        ];

        if ($repository->members()->whereKey($member->id)->exists()) {
            $repository->members()->updateExistingPivot($member->id, $pivot);
        } else {
            $repository->members()->attach($member->id, $pivot);
        }

        return response()->json($this->repositoryMembersPayload($repository->fresh(), $request->user()));
    }

    public function updateUserRole(Request $request, Repository $repository, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $repository);

        $validated = $request->validate([
            'role' => ['required', Rule::in($this->repositoryRoleKeys())],
        ]);

        if (! $repository->members()->whereKey($user->id)->exists()) {
            return response()->json([
                'message' => 'That user is not assigned to this repository.',
            ], 404);
        }

        $repository->members()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
        ]);

        return response()->json($this->repositoryMembersPayload($repository->fresh(), $request->user()));
    }

    public function destroyUser(Request $request, Repository $repository, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $repository);

        if (! $repository->members()->whereKey($user->id)->exists()) {
            return response()->json([
                'message' => 'That user is not assigned to this repository.',
            ], 404);
        }

        $repository->members()->detach($user->id);

        return response()->json($this->repositoryMembersPayload($repository->fresh(), $request->user()));
    }

    public function updateCredentials(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('update', $repository);

        if (in_array($repository->provider, ['github', 'gitlab'], true)) {
            $validated = $request->validate([
                'access_token' => [
                    'nullable',
                    'string',
                    'max:500',
                    Rule::requiredIf(fn () => $request->input('auth_method') === 'pat'),
                ],
                'auth_method' => ['required', Rule::in(['oauth', 'pat'])],
            ]);

            $repository->update([
                'access_token' => $validated['auth_method'] === 'pat'
                    ? $validated['access_token']
                    : null,
                'status' => 'connected',
            ]);
        } elseif ($repository->provider === 'company-server') {
            $validated = $request->validate([
                'server_host' => ['required', 'string', 'max:255'],
                'server_path' => ['required', 'string', 'max:500'],
                'server_protocol' => ['nullable', Rule::in(['SSH', 'SFTP', 'HTTP', 'HTTPS'])],
            ]);

            $repository->update([
                'name' => $validated['server_path'],
                'server_host' => $validated['server_host'],
                'server_path' => $validated['server_path'],
                'server_protocol' => $validated['server_protocol'] ?? 'SSH',
                'url' => $validated['server_path'],
                'status' => 'connected',
            ]);
        } elseif ($repository->provider === 'local-pc') {
            return response()->json([
                'message' => 'Local PC repositories are managed through SSH access or uploaded archives. Reconnect the repository to change its source.',
            ], 422);
        }

        return response()->json([
            'message' => 'Repository connection updated.',
            'repository' => $this->repositoryPayload($repository->fresh(), $request->user()),
        ]);
    }

    /**
     * @return array{0: string|null, 1: JsonResponse|null}
     */
    protected function oauthTokenForProvider(User $user, string $provider, OAuthTokenService $oauthTokens): array
    {
        try {
            $token = $oauthTokens->accessToken($user, $provider);
        } catch (OAuthTokenRefreshException $e) {
            return [null, response()->json([
                'message' => $e->getMessage(),
                'redirect_url' => route("{$provider}.oauth.redirect", ['return_to' => 'repositories']),
                'requires_oauth' => true,
            ], 409)];
        }

        if (! $token) {
            return [null, response()->json([
                'message' => 'Connect your '.$this->providerLabel($provider).' account first to use OAuth.',
                'redirect_url' => route("{$provider}.oauth.redirect", ['return_to' => 'repositories']),
                'requires_oauth' => true,
            ], 409)];
        }

        return [$token, null];
    }

    /**
     * @return array{0: string|null, 1: JsonResponse|null}
     */
    protected function repositoryAccessToken(Repository $repository, User $viewer, OAuthTokenService $oauthTokens): array
    {
        if ($repository->access_token) {
            return [$repository->access_token, null];
        }

        $repository->loadMissing('user');
        $owner = $repository->user;

        if (! $owner) {
            return [null, response()->json([
                'message' => 'This repository no longer has an owner account.',
            ], 422)];
        }

        try {
            $token = $oauthTokens->accessToken($owner, $repository->provider);
        } catch (OAuthTokenRefreshException $e) {
            return [null, $this->ownerCredentialRequiredResponse($repository, $viewer, $e->getMessage())];
        }

        if (! $token) {
            return [null, $this->ownerCredentialRequiredResponse(
                $repository,
                $viewer,
                'The repository owner needs to reconnect '.$this->providerLabel($repository->provider).' OAuth or save a PAT before this repository can be packaged.'
            )];
        }

        return [$token, null];
    }

    protected function ownerCredentialRequiredResponse(Repository $repository, User $viewer, string $message): JsonResponse
    {
        $payload = ['message' => $message];

        if ($viewer->id === $repository->user_id) {
            $payload['redirect_url'] = route("{$repository->provider}.oauth.redirect", ['return_to' => 'create-package']);
            $payload['requires_oauth'] = true;

            return response()->json($payload, 409);
        }

        return response()->json($payload, 422);
    }

    protected function repositoryPayload(Repository $repository, User $viewer): array
    {
        if (
            $repository->provider === 'local-pc'
            && filled($repository->storage_path)
            && File::isDirectory($repository->storage_path)
            && blank($repository->branches)
            && blank($repository->tags)
        ) {
            try {
                $repository->fill($this->localRepositoryRefAttributes($repository->storage_path))->save();
            } catch (\Throwable) {
            }
        }

        $repository->loadMissing(['members', 'user']);

        $membersPayload = $this->repositoryMembersPayload($repository, $viewer);
        $ownerName = $repository->user?->name ?: $repository->user?->email;

        return array_merge($membersPayload, [
            'authType' => $this->repositoryAuthType($repository),
            'branchCount' => $repository->branch_count,
            'canCreatePackage' => $viewer->can('createPackage', $repository),
            'canManageRepository' => $viewer->can('update', $repository),
            'defaultBranch' => $repository->default_branch ?? 'main',
            'externalId' => $repository->external_id,
            'id' => $repository->id,
            'label' => $repository->label,
            'lastSyncedAt' => $repository->last_synced_at?->toIso8601String(),
            'lastSyncedLabel' => $repository->last_synced_at?->diffForHumans() ?? 'Not synced yet',
            'name' => $repository->name,
            'ownerInitials' => $this->initials($ownerName),
            'ownerName' => $ownerName,
            'provider' => $repository->provider,
            'providerLabel' => $this->providerLabel($repository->provider),
            'serverHost' => $repository->server_host,
            'serverPath' => $repository->server_path,
            'serverProtocol' => $repository->server_protocol,
            'slug' => $repository->name,
            'status' => $repository->status ?? 'connected',
            'statusLabel' => $this->statusLabel($repository->status ?? 'connected'),
            'storagePath' => $repository->storage_path,
            'tagCount' => $repository->tag_count,
            'type' => $repository->type,
            'url' => $repository->url,
            'hasGitHistory' => (bool) $repository->has_git_history,
            'remoteIp' => $repository->remote_ip,
            'remotePath' => $repository->remote_path,
            'username' => $repository->username,
        ]);
    }

    protected function repositoryMembersPayload(Repository $repository, User $viewer): array
    {
        $repository->loadMissing('members');

        return [
            'canManageMembers' => $viewer->can('manageMembers', $repository),
            'memberCount' => $repository->members->count(),
            'users' => $repository->members
                ->sortBy('name')
                ->map(fn (User $user) => $this->memberPayload($user))
                ->values(),
        ];
    }

    protected function memberPayload(User $user): array
    {
        return [
            'avatar' => $user->avatar_url,
            'email' => $user->email,
            'id' => $user->id,
            'initials' => $this->initials($user->name ?: $user->email),
            'name' => $user->name,
            'role' => $this->normalizeRepositoryRole($user->pivot->role ?? null),
            'source' => $user->pivot->source ?? 'ldap',
            'username' => $user->display_username,
        ];
    }

    protected function repositoryRoleOptions(): array
    {
        return ProjectInvolvementService::PROJECT_ROLES;
    }

    protected function repositoryRoleKeys(): array
    {
        return array_column($this->repositoryRoleOptions(), 'key');
    }

    protected function normalizeRepositoryRole(?string $role): string
    {
        return in_array($role, $this->repositoryRoleKeys(), true)
            ? $role
            : ProjectInvolvementService::DEFAULT_PROJECT_ROLE;
    }

    protected function repositoryAuthType(Repository $repository): string
    {
        if (in_array($repository->provider, ['github', 'gitlab'], true)) {
            return $repository->access_token ? 'Personal Access Token' : 'OAuth';
        }

        if ($repository->provider === 'company-server') {
            return $repository->server_protocol ?: 'SSH';
        }

        if ($repository->provider === 'local-pc') {
            if ($repository->type === 'ssh-mirror') {
                return 'SSH mirror';
            }

            if ($repository->type === 'uploaded') {
                return 'Uploaded archive';
            }

            return 'Local agent';
        }

        return 'Repository connection';
    }

    protected function providerLabel(string $provider): string
    {
        return match ($provider) {
            'github' => 'GitHub',
            'gitlab' => 'GitLab',
            'company-server' => 'Company Server',
            'local-pc' => 'Local PC',
            default => ucfirst(str_replace('-', ' ', $provider)),
        };
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'connected' => 'Connected',
            'expired' => 'Expired',
            'needs-auth' => 'Needs auth',
            default => ucfirst($status),
        };
    }

    protected function initials(?string $value): string
    {
        $parts = preg_split('/\s+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return '?';
        }

        return collect($parts)
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    }

    protected function githubConnectionErrorResponse(array $metadata): JsonResponse
    {
        $payload = [
            'message' => $metadata['message'],
        ];

        if (! empty($metadata['requires_oauth'])) {
            $payload['redirect_url'] = route('github.oauth.redirect', ['return_to' => 'repositories']);
            $payload['requires_oauth'] = true;

            return response()->json($payload, 409);
        }

        return response()->json($payload, 422);
    }

    protected function fetchGitHubMetadataWithOAuth(
        GitHubService $github,
        User $user,
        OAuthTokenService $oauthTokens,
        string $name
    ): array {
        $parsed = $this->parseGitHubOwnerRepo($name);

        if (! $parsed) {
            return [
                'message' => 'GitHub repositories must use the format owner/repo.',
                'ok' => false,
            ];
        }

        [$owner, $repo] = $parsed;

        try {
            $responses = $oauthTokens->withFreshToken(
                $user,
                'github',
                fn (string $token) => [
                    'branches' => $github->getBranches($owner, $repo, $token),
                    'repository' => $github->getRepository($owner, $repo, $token),
                    'tags' => $github->getTags($owner, $repo, $token),
                ]
            );
        } catch (OAuthTokenRefreshException $e) {
            return [
                'message' => $e->getMessage(),
                'ok' => false,
                'requires_oauth' => true,
            ];
        }

        if ($responses === null) {
            return [
                'message' => 'Connect your GitHub account first to use OAuth.',
                'ok' => false,
                'requires_oauth' => true,
            ];
        }

        return $this->githubMetadataFromResponses(
            $responses['repository'],
            $responses['branches'],
            $responses['tags'],
            true
        );
    }

    protected function fetchGitHubMetadata(GitHubService $github, string $name, ?string $token, bool $usingOAuth = false): array
    {
        $parsed = $this->parseGitHubOwnerRepo($name);

        if (! $parsed) {
            return [
                'message' => 'GitHub repositories must use the format owner/repo.',
                'ok' => false,
            ];
        }

        [$owner, $repo] = $parsed;

        $repoResponse = $github->getRepository($owner, $repo, $token);

        if ($repoResponse->failed()) {
            return $this->githubMetadataFromResponses($repoResponse, null, null, $usingOAuth);
        }

        $branchesResponse = $github->getBranches($owner, $repo, $token);
        $tagsResponse = $github->getTags($owner, $repo, $token);

        return $this->githubMetadataFromResponses($repoResponse, $branchesResponse, $tagsResponse, $usingOAuth);
    }

    protected function githubMetadataFromResponses(
        HttpResponse $repoResponse,
        ?HttpResponse $branchesResponse,
        ?HttpResponse $tagsResponse,
        bool $usingOAuth
    ): array {
        if ($repoResponse->failed()) {
            return [
                'message' => $this->githubRepositoryFailureMessage($repoResponse, $usingOAuth),
                'ok' => false,
                'requires_oauth' => $usingOAuth && $this->githubResponseNeedsOAuthReconnect($repoResponse),
            ];
        }

        $repoData = $repoResponse->json();

        return [
            'branches' => $branchesResponse?->ok()
                ? collect($branchesResponse->json())->pluck('name')->all()
                : [],
            'default_branch' => $repoData['default_branch'] ?? 'main',
            'external_id' => isset($repoData['id']) ? (string) $repoData['id'] : null,
            'message' => null,
            'ok' => true,
            'status' => 'connected',
            'tags' => $tagsResponse?->ok()
                ? collect($tagsResponse->json())->pluck('name')->all()
                : [],
        ];
    }

    protected function githubRepositoryFailureMessage(HttpResponse $response, bool $usingOAuth): string
    {
        if ($usingOAuth && $this->usesGitHubAppClient() && $this->githubResponseMissingRepoScope($response)) {
            return 'This private repository cannot be accessed because the GitHub App is not installed on it, or it does not have the required repository permissions. Please install the GitHub App on this repository and allow access to Metadata and Contents, then try again.';
        }

        if ($usingOAuth && $this->githubResponseMissingRepoScope($response)) {
            return 'Your GitHub OAuth token is missing the repo scope required for private repositories. Reconnect GitHub and approve private repository access, then try again.';
        }

        if ($usingOAuth && $response->unauthorized()) {
            return 'GitHub rejected the stored OAuth token. Reconnect GitHub and try again.';
        }

        return 'Could not reach this GitHub repository. Check the URL and credentials.';
    }

    protected function githubResponseNeedsOAuthReconnect(HttpResponse $response): bool
    {
        return $response->unauthorized()
            || (! $this->usesGitHubAppClient() && $this->githubResponseMissingRepoScope($response));
    }

    protected function githubResponseMissingRepoScope(HttpResponse $response): bool
    {
        $acceptedScopes = $this->parseGitHubScopeHeader($response->header('X-Accepted-OAuth-Scopes'));
        $grantedScopes = $this->parseGitHubScopeHeader($response->header('X-OAuth-Scopes'));

        return in_array('repo', $acceptedScopes, true)
            && ! in_array('repo', $grantedScopes, true);
    }

    protected function parseGitHubScopeHeader(?string $header): array
    {
        return str($header ?? '')
            ->explode(',')
            ->map(fn (string $scope) => trim($scope))
            ->filter()
            ->values()
            ->all();
    }

    protected function usesGitHubAppClient(): bool
    {
        return str_starts_with((string) config('services.github.client_id'), 'Iv');
    }

    protected function fetchGitLabMetadata(string $name, ?string $token): array
    {
        if (! $token) {
            return [
                'message' => 'Provide a GitLab PAT or connect GitLab OAuth first.',
                'ok' => false,
            ];
        }

        if (! $this->isGitLabProjectPath($name)) {
            return [
                'message' => 'GitLab repositories must use the format group/project or group/subgroup/project.',
                'ok' => false,
            ];
        }

        $projectPath = rawurlencode($name);
        $baseUrl = rtrim(config('services.gitlab.base_url', 'https://gitlab.com'), '/');

        $repoResponse = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v4/projects/{$projectPath}");

        if ($repoResponse->failed()) {
            return [
                'message' => 'Could not reach this GitLab repository. Check the URL and credentials.',
                'ok' => false,
            ];
        }

        $repoData = $repoResponse->json();

        $branchesResponse = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v4/projects/{$repoData['id']}/repository/branches");

        $tagsResponse = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v4/projects/{$repoData['id']}/repository/tags");

        return [
            'branches' => $branchesResponse->ok()
                ? collect($branchesResponse->json())->pluck('name')->all()
                : [],
            'default_branch' => $repoData['default_branch'] ?? 'main',
            'external_id' => isset($repoData['id']) ? (string) $repoData['id'] : null,
            'message' => null,
            'ok' => true,
            'status' => 'connected',
            'tags' => $tagsResponse->ok()
                ? collect($tagsResponse->json())->pluck('name')->all()
                : [],
        ];
    }

    protected function normalizeRepositoryInput(string $provider, string $rawName, ?string $rawUrl = null): ?array
    {
        $name = trim($rawName);
        $url = trim((string) $rawUrl);

        if (in_array($provider, ['github', 'gitlab'], true)) {
            $identifier = $this->extractRepositoryIdentifier($name);

            if (! $identifier && $url !== '') {
                $identifier = $this->extractRepositoryIdentifier($url);
            }

            if (! $identifier) {
                return null;
            }

            $baseUrl = $provider === 'github'
                ? 'https://github.com'
                : rtrim(config('services.gitlab.base_url', 'https://gitlab.com'), '/');

            return [
                'display_name' => str($identifier)->afterLast('/')->toString(),
                'name' => $identifier,
                'server_host' => null,
                'server_protocol' => null,
                'url' => $url !== '' ? $url : "{$baseUrl}/{$identifier}",
            ];
        }

        return [
            'display_name' => $name,
            'name' => $name,
            'server_host' => null,
            'server_protocol' => $provider === 'company-server' ? 'SSH' : null,
            'url' => $url !== '' ? $url : null,
        ];
    }

    protected function extractRepositoryIdentifier(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('~^(?:https?://|git@)(?:[^/:]+)[:/](.+?)(?:\.git)?/?$~i', $value, $matches)) {
            return trim($matches[1], '/');
        }

        if (preg_match('~^[^/\s]+/[^/\s]+$~', $value)) {
            return $value;
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    protected function parseGitHubOwnerRepo(string $name): ?array
    {
        $parts = explode('/', $name, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return [$parts[0], $parts[1]];
    }

    protected function isGitLabProjectPath(string $name): bool
    {
        $segments = array_values(array_filter(explode('/', trim($name))));

        return count($segments) >= 2;
    }
}
