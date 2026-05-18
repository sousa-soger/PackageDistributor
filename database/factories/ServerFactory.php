<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Server>
 */
class ServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $environment = fake()->randomElement(array_keys(Server::ENVIRONMENTS));
        $name = fake()->unique()->company().' '.$environment;

        return [
            'user_id' => User::factory(),
            'project_id' => null,
            'name' => $name,
            'environment' => $environment,
            'host' => fake()->domainName(),
            'ssh_user' => fake()->userName(),
            'port' => 22,
            'deploy_path' => '/var/www/'.fake()->slug(2),
            'health_check_url' => fake()->optional()->url(),
            'status' => fake()->randomElement(array_keys(Server::STATUSES)),
            'current_release' => fake()->optional()->randomElement(['v1.0.0', 'v1.1.0', 'v1.2.0']),
            'auto_deploy_enabled' => fake()->boolean(35),
            'auto_deploy_strategy' => fake()->randomElement(array_keys(Server::AUTO_DEPLOY_STRATEGIES)),
            'production_approval_required' => $environment === 'PROD',
            'notes' => fake()->optional()->sentence(),
            'last_checked_at' => fake()->optional()->dateTimeBetween('-2 weeks'),
            'last_deployed_at' => fake()->optional()->dateTimeBetween('-1 month'),
        ];
    }
}
