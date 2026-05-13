<?php

use App\Services\DeploymentPackageService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

uses(TestCase::class);

test('deployment package service builds tar gz archives without leaving a raw tar file behind', function () {
    $packageName = 'service-targz-package';
    $packageRoot = storage_path('framework/testing/'.$packageName);
    $tarPath = $packageRoot.'.tar';
    $tarGzPath = $packageRoot.'.tar.gz';

    File::deleteDirectory($packageRoot);
    File::delete($tarPath);
    File::delete($tarGzPath);
    File::ensureDirectoryExists($packageRoot);
    File::put($packageRoot.DIRECTORY_SEPARATOR.'manifest.json', '{"version":"0.1.7"}');

    try {
        $service = new DeploymentPackageService;
        $buildTarGz = Closure::bind(
            fn (string $path): ?string => $this->buildTarGz($path),
            $service,
            DeploymentPackageService::class,
        );

        $result = $buildTarGz($packageRoot);

        expect($result)->toBe($tarGzPath)
            ->and(File::exists($tarGzPath))->toBeTrue()
            ->and(File::exists($tarPath))->toBeFalse();
    } finally {
        File::deleteDirectory($packageRoot);
        File::delete($tarPath);
        File::delete($tarGzPath);
    }
});
