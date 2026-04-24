<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $now = Carbon::now();

        $apps = [
            'Cybix+ Web',
            'Cybix+ Android',
            'Cybix+ iOS',
            'SarawakID Web',
            'SarawakID Android',
            'SarawakID iOS',
            'SCS Web',
            'SCS Android',
            'SCS iOS',
        ];

        $dummyVersions = [
            [
                'version_name' => 'v1.0.0',
                'commit_type' => 'tag',
                'is_active' => false,
                'update_type' => 'feature',
                'release_notes' => 'Initial public release with core login, dashboard, and basic package distribution flow.',
            ],
            [
                'version_name' => 'v1.1.0',
                'commit_type' => 'branch',
                'is_active' => false,
                'update_type' => 'feature',
                'release_notes' => 'Added user management, version listing, and improved navigation across modules.',
            ],
            [
                'version_name' => 'v1.1.2',
                'commit_type' => 'commit',
                'is_active' => false,
                'update_type' => 'bug fix',
                'release_notes' => 'Fixed validation errors, corrected UI spacing issues, and resolved API response handling bugs.',
            ],
            [
                'version_name' => 'v1.1.3',
                'commit_type' => 'commit',
                'is_active' => false,
                'update_type' => 'hot fix',
                'release_notes' => 'Urgent patch for authentication issue and failed package download edge cases.',
            ],
            [
                'version_name' => 'v1.2.0',
                'commit_type' => 'tag',
                'is_active' => true,
                'update_type' => 'performance',
                'release_notes' => 'Improved loading speed, optimized database queries, and reduced package generation time.',
            ],
        ];

        $rows = [];

        foreach ($apps as $app) {
            foreach ($dummyVersions as $version) {
                $rows[] = [
                    'version_name' => $version['version_name'],
                    'commit_type' => $version['commit_type'],
                    'app_name' => $app,
                    'is_active' => $version['is_active'],
                    'update_type' => $version['update_type'],
                    'release_notes' => $version['release_notes'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('versions')->insert($rows);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
