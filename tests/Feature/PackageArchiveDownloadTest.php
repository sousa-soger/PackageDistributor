<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

test('download archive can recover a tar.gz package from an existing tar file', function () {
    $user = User::factory()->create();
    $packageName = 'DEV-broken-targz-package';
    $sourceRoot = storage_path('framework/testing/'.$packageName.'-source');
    $packageRoot = storage_path("app/deployment-packages/{$packageName}");
    $tarPath = $packageRoot.'.tar';
    $tarGzPath = $packageRoot.'.tar.gz';

    File::deleteDirectory($sourceRoot);
    File::delete($tarPath);
    File::delete($tarGzPath);
    File::ensureDirectoryExists($sourceRoot);
    File::ensureDirectoryExists(dirname($packageRoot));
    File::put($sourceRoot.DIRECTORY_SEPARATOR.'manifest.json', '{"version":"0.1.7"}');

    try {
        $tar = new PharData($tarPath);
        $tar->buildFromDirectory($sourceRoot);

        expect(File::exists($tarPath))->toBeTrue()
            ->and(File::exists($tarGzPath))->toBeFalse();

        $this->actingAs($user)
            ->get(route('download.archive', ['folder' => $packageName, 'format' => '.tar.gz']))
            ->assertDownload("{$packageName}.tar.gz");

        expect(File::exists($tarGzPath))->toBeTrue()
            ->and(File::exists($tarPath))->toBeFalse();
    } finally {
        File::deleteDirectory($sourceRoot);
        File::delete($tarPath);
        File::delete($tarGzPath);
    }
});
