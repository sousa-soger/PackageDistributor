<?php

use App\Models\Repository;
use App\Models\User;
use App\Services\LdapService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function repositoryMembershipRepositoryFor(User $owner, array $attributes = []): Repository
{
    return $owner->repositories()->create(array_merge([
        'branches' => ['main'],
        'default_branch' => 'main',
        'display_name' => 'Web Storefront',
        'name' => 'atlas/web-storefront',
        'provider' => 'github',
        'status' => 'connected',
        'tags' => ['v1.0.0'],
        'url' => 'https://github.com/atlas/web-storefront',
    ], $attributes));
}

test('repository owner can add remove and change roles for ldap users', function () {
    $owner = User::factory()->create();
    $repository = repositoryMembershipRepositoryFor($owner);
    $ldapUser = User::factory()->create([
        'email' => 'jane.doe@example.com',
        'ldap_username' => 'jdoe',
        'name' => 'Jane Doe',
    ]);
    $directoryUser = [
        'avatar' => null,
        'email' => 'jane.doe@example.com',
        'name' => 'Jane Doe',
        'username' => 'jdoe',
    ];

    $this->app->instance(LdapService::class, new class($directoryUser, $ldapUser) extends LdapService
    {
        public function __construct(private array $directoryUser, private User $localUser) {}

        public function findUser(string $usernameOrEmail): ?array
        {
            return $this->directoryUser;
        }

        public function syncLocalUser(array $directoryUser): User
        {
            return $this->localUser;
        }
    });

    $this->actingAs($owner)
        ->postJson(route('repositories.users.store', $repository), [
            'role' => 'creator',
            'username' => 'jdoe',
        ])
        ->assertOk()
        ->assertJsonPath('users.0.id', $ldapUser->id)
        ->assertJsonPath('users.0.role', 'creator');

    $this->assertDatabaseHas('repository_user', [
        'ldap_identifier' => 'jdoe',
        'repository_id' => $repository->id,
        'role' => 'creator',
        'source' => 'ldap',
        'user_id' => $ldapUser->id,
    ]);

    $this->actingAs($owner)
        ->patchJson(route('repositories.users.update-role', [$repository, $ldapUser]), [
            'role' => 'deployer',
        ])
        ->assertOk()
        ->assertJsonPath('users.0.role', 'deployer');

    $this->assertDatabaseHas('repository_user', [
        'repository_id' => $repository->id,
        'role' => 'deployer',
        'user_id' => $ldapUser->id,
    ]);

    $this->actingAs($owner)
        ->deleteJson(route('repositories.users.destroy', [$repository, $ldapUser]))
        ->assertOk()
        ->assertJsonCount(0, 'users');

    $this->assertDatabaseMissing('repository_user', [
        'repository_id' => $repository->id,
        'user_id' => $ldapUser->id,
    ]);
});

test('non owner cannot manage repository members', function () {
    $owner = User::factory()->create();
    $nonOwner = User::factory()->create();
    $repository = repositoryMembershipRepositoryFor($owner);

    $this->actingAs($nonOwner)
        ->postJson(route('repositories.users.store', $repository), ['username' => 'someone'])
        ->assertForbidden();
});

test('repositories page renders repository bottom sheet without project or team assignment UI', function () {
    $owner = User::factory()->create([
        'name' => 'Olivia Repository Owner',
    ]);
    repositoryMembershipRepositoryFor($owner);

    $this->actingAs($owner)
        ->get(route('repositories'))
        ->assertOk()
        ->assertSee('Olivia Repository Owner')
        ->assertDontSee('Aaron Voon Wu Chun')
        ->assertSee('Connection')
        ->assertSee('People and Roles')
        ->assertSee('Change PAT')
        ->assertDontSee('Project Name')
        ->assertDontSee('Add an existing team');
});
