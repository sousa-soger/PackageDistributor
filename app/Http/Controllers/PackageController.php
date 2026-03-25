<?php

namespace App\Http\Controllers;

use App\Models\Version;
use Illuminate\Http\Request;

class PackageController extends Controller
{   //** Should be taken from GitLab Repo listing */
    public function create()
    {
        $repositories = Version::query()
            ->select('app_name')
            ->distinct()
            ->orderBy('app_name')
            ->pluck('app_name');

        $versions = Version::orderBy('app_name')
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();

        return view('new-package', compact('repositories', 'versions'));
    }
}