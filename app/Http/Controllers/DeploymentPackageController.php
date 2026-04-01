<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;

class DeploymentPackageController extends Controller
{
    public function generate(Request $request)
    {
        // Prevent PHP from crashing if the download/extraction takes longer than 60s
        set_time_limit(600);

        $validated = $request->validate([
            'environment'  => ['required', 'string', 'max:20'],
            'project_name' => ['required', 'string', 'max:100'],
            'base_version' => ['required', 'string', 'max:100'],
            'head_version' => ['required', 'string', 'max:100'],
            'repo'         => ['required', 'string', 'max:255'],
            'package_name' => ['nullable', 'string', 'max:255'],
        ]);

        $tmpDir = storage_path('framework/cache');
        File::ensureDirectoryExists($tmpDir);
        
        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call('deploy:delta', [
                'environment'  => $validated['environment'],
                'project'      => $validated['project_name'],
                'base'         => $validated['base_version'],
                'head'         => $validated['head_version'],
                '--repo'       => $validated['repo'],
                '--name'       => $validated['package_name'] ?? '',
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();
            
            if ($exitCode !== 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Command exited with code {$exitCode}.\nOutput:\n{$output}",
                ], 500);
            }

            $outputLines = preg_split('/\r\n|\r|\n/', trim($output));
            $lastLine    = trim(end($outputLines));

            $decoded = json_decode($lastLine, true);

            if (! is_array($decoded)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Package generated, but result payload could not be parsed.',
                    'raw_output' => $output,
                ], 500);
            }

            return response()->json($decoded);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate package: ' . $e->getMessage(),
            ], 500);
        }
    }
}