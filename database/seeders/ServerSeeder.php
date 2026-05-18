<?php

namespace Database\Seeders;

use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create();

        Server::factory()
            ->for($user)
            ->count(3)
            ->sequence(
                [
                    'name' => 'Development App Server',
                    'environment' => 'DEV',
                    'host' => 'dev.example.internal',
                    'status' => 'online',
                    'auto_deploy_enabled' => true,
                    'production_approval_required' => false,
                ],
                [
                    'name' => 'QA App Server',
                    'environment' => 'QA',
                    'host' => 'qa.example.internal',
                    'status' => 'pending',
                    'auto_deploy_enabled' => true,
                    'auto_deploy_strategy' => 'after_qa_success',
                    'production_approval_required' => false,
                ],
                [
                    'name' => 'Production App Server',
                    'environment' => 'PROD',
                    'host' => 'www.example.com',
                    'status' => 'pending',
                    'auto_deploy_enabled' => false,
                    'auto_deploy_strategy' => 'manual_approval',
                    'production_approval_required' => true,
                ],
            )
            ->create();
    }
}
