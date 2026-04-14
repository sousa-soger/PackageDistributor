<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GitHubController;
use App\Http\Controllers\DeploymentPackageController;
use App\Http\Controllers\PackageController;
use Illuminate\Support\Facades\Route;

// ** Route for Sidebar nav but only logged in user can view
Route::view('/user-auth', 'user-auth')->name('user-auth');

Route::middleware('auth')->group(function () {
    Route::view('/', 'home')->name('home');

    Route::get('/new-package', [PackageController::class, 'index'])->name('new-package');

    Route::get('/new-packageV2', [PackageController::class, 'indexV2'])->name('new-packageV2');

    Route::get('/new-packageV3', [PackageController::class, 'indexV3'])->name('new-packageV3');
    Route::get('/packages/done', [PackageController::class, 'donePackages'])
        ->name('packages.done');
        
    Route::view('/settings', 'settings')->name('settings');
});

// ** Route for auth
Route::post('/register', [AuthController::class, 'register'])->name('register.user');
Route::post('/login', [AuthController::class, 'login'])->name('login.user');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout.user');

Route::get('/github/repo-info', [GitHubController::class, 'repoInfo'])->name('github.repo-info');
Route::get('/github/repo-versions', [GitHubController::class, 'repoVersions'])->name('github.repo-versions');
Route::get('/github/rate-limit', [GitHubController::class, 'rateLimit'])->name('github.rate-limit');

Route::middleware('auth')->group(function () {

    // ── V1 / V2 — Synchronous (unchanged) ────────────────────────────────────
    Route::post('/deployments/generate-delta', [DeploymentPackageController::class, 'generate'])
        ->name('deployments.generate-delta');

    // {name} may contain dots (e.g. "v1.1.2") – the where() prevents silent 404s.
    Route::get('/deployments/progress/{name}', [DeploymentPackageController::class, 'progress'])
        ->where('name', '[^/]+')
        ->name('deployments.progress');

    // ── V3 — Queue-based ──────────────────────────────────────────────────────

    // Submit a new queued deployment job (returns immediately with job_id)
    Route::post('/deployments/queue-job', [DeploymentPackageController::class, 'queueJob'])
        ->name('deployments.queue-job');

    // Poll progress + status by job DB ID
    Route::get('/deployments/jobs/{id}/progress', [DeploymentPackageController::class, 'jobProgress'])
        ->where('id', '[0-9]+')
        ->name('deployments.job-progress');
});

// User download package route (no auth guard so direct link downloads still work)
Route::get('/download-archive', [DeploymentPackageController::class, 'downloadArchive'])->name('download.archive');