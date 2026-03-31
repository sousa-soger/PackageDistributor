<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GitHubController;
use App\Http\Controllers\PackageController;
use Illuminate\Support\Facades\Route;

// ** Route for Sidebar nav but only logged in user can view
Route::view('/user-auth', 'user-auth')->name('user-auth');

Route::middleware('auth')->group(function () {
    Route::view('/', 'home')->name('home');
    Route::get('/new-package', [PackageController::class, 'index'])->name('new-package');
    Route::view('/settings', 'settings')->name('settings');
});

// For trying only
Route::post('/register', [AuthController::class, 'register'])->name('register.user');

// ** Route for auth
Route::post('/login', [AuthController::class, 'login'])->name('login.user');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout.user');

// for testing only - dummy loader
Route::get('/dummy-package', function () {
    sleep(2); // simulate backend work

    return response()->json([
        'status' => 'success',
    ]);
});

Route::get('/github/repo-info', [GitHubController::class, 'repoInfo'])->name('github.repo-info');
Route::get('/github/repo-versions', [GitHubController::class, 'repoVersions'])->name('github.repo-versions');
