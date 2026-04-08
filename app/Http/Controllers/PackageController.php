<?php

namespace App\Http\Controllers;

class PackageController extends Controller
{
    public function index()
    {
        $repositories = array_map(static function (array $item) {
            return [
                'id' => $item['id'],
                'label' => $item['label'],
                'owner' => $item['owner'],
                'repo' => $item['repo'],
            ];
        }, config('github-repos'));

        return view('new-package', compact('repositories'));
    }

    public function indexV2()
    {
        $repositories = array_map(static function (array $item) {
            return [
                'id' => $item['id'],
                'label' => $item['label'],
                'owner' => $item['owner'],
                'repo' => $item['repo'],
            ];
        }, config('github-repos'));

        return view('new-packageV2', compact('repositories'));
    }
}
