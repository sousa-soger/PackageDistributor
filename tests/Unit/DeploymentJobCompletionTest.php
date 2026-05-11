<?php

use App\Models\DeploymentJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('stores a compact completion result for large repositories', function () {
    $job = DeploymentJob::create([
        'base_version' => 'v1.0.0',
        'environment' => 'DEV',
        'head_version' => 'v1.1.0',
        'package_name' => 'DEV-large-repo',
        'project_name' => 'Large Repo',
        'repo' => 'acme/large-repo',
        'status' => 'running',
        'vcs_provider' => 'github',
    ]);

    $changedFiles = collect(range(1, 2500))
        ->map(fn (int $index): array => [
            'filename' => "src/File{$index}.php",
            'new_size' => 2048,
            'old_size' => 1024,
            'size_diff' => 1024,
            'status' => 'modified',
        ])
        ->all();

    $job->markCompleted([
        'changed_files' => $changedFiles,
        'file_size' => '8.5 MB',
        'folder_name' => 'DEV-large-repo',
        'message' => 'Package created successfully.',
        'status' => 'success',
        'summary' => ['total_changes' => count($changedFiles)],
        'targz_sha256' => str_repeat('b', 64),
        'targz_size' => '4.8 MB',
        'zip_sha256' => str_repeat('a', 64),
        'zip_size' => '5.1 MB',
    ]);

    $completedJob = $job->fresh();

    expect($completedJob->status)->toBe('completed')
        ->and($completedJob->message)->toBe('Done.')
        ->and($completedJob->finished_at)->not->toBeNull()
        ->and($completedJob->zip_size)->toBe('5.1 MB')
        ->and($completedJob->zip_sha256)->toBe(str_repeat('a', 64))
        ->and($completedJob->targz_size)->toBe('4.8 MB')
        ->and($completedJob->targz_sha256)->toBe(str_repeat('b', 64))
        ->and($completedJob->result_json)->not->toHaveKey('changed_files')
        ->and($completedJob->result_json['changed_files_count'])->toBe(2500)
        ->and($completedJob->result_json['summary']['total_changes'])->toBe(2500);
});
