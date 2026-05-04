<?php

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Services\LdapService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function projectInvolvementProjectFor(User $owner, array $attributes = []): Project
{
    return $owner->projects()->create(array_merge([
        'name' => 'Launch Platform',
        'slug' => 'launch-platform',
        'description' => 'Deployment project',
        'color' => Project::DEFAULT_COLOR,
    ], $attributes));
}

function projectInvolvementTeamFor(User $owner, array $attributes = []): Team
{
    $team = Team::create(array_merge([
        'owner_user_id' => $owner->id,
        'name' => 'Platform Team',
        'slug' => 'platform-team',
    ], $attributes));

    $team->members()->attach($owner->id, [
        'role' => 'owner',
        'status' => 'active',
    ]);

    return $team;
}

test('project owner can add and remove teams from a project', function () {
    $owner = User::factory()->create();
    $project = projectInvolvementProjectFor($owner);
    $team = projectInvolvementTeamFor($owner);

    $this->actingAs($owner)
        ->postJson(route('projects.teams.store', $project), ['team_id' => $team->id])
        ->assertOk()
        ->assertJsonPath('teams.0.id', $team->id);

    $this->assertDatabaseHas('project_team', [
        'project_id' => $project->id,
        'team_id' => $team->id,
    ]);

    $this->actingAs($owner)
        ->deleteJson(route('projects.teams.destroy', [$project, $team]))
        ->assertOk()
        ->assertJsonCount(0, 'teams');

    $this->assertDatabaseMissing('project_team', [
        'project_id' => $project->id,
        'team_id' => $team->id,
    ]);
});

test('non owner cannot manage project teams directly', function () {
    $owner = User::factory()->create();
    $nonOwner = User::factory()->create();
    $project = projectInvolvementProjectFor($owner);
    $team = projectInvolvementTeamFor($nonOwner);

    $this->actingAs($nonOwner)
        ->postJson(route('projects.teams.store', $project), ['team_id' => $team->id])
        ->assertForbidden();

    $project->teams()->attach($team->id);

    $this->actingAs($nonOwner)
        ->deleteJson(route('projects.teams.destroy', [$project, $team]))
        ->assertForbidden();

    $this->assertDatabaseHas('project_team', [
        'project_id' => $project->id,
        'team_id' => $team->id,
    ]);
});

test('project members endpoint shows real teams and individual users to non owners', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create([
        'ldap_username' => 'viewer',
    ]);
    $project = projectInvolvementProjectFor($owner);
    $team = projectInvolvementTeamFor($owner);

    $team->members()->attach($viewer->id, [
        'role' => 'viewer',
        'status' => 'active',
    ]);
    $project->teams()->attach($team->id);
    $project->involvedUsers()->attach($viewer->id, [
        'source' => 'ldap',
        'ldap_identifier' => 'viewer',
        'role' => 'member',
    ]);

    $this->actingAs($viewer)
        ->getJson(route('projects.members.show', $project))
        ->assertOk()
        ->assertJsonPath('canManageMembers', false)
        ->assertJsonPath('teams.0.id', $team->id)
        ->assertJsonPath('users.0.id', $viewer->id);
});

test('project owner can add and remove an LDAP user from a project', function () {
    $owner = User::factory()->create();
    $project = projectInvolvementProjectFor($owner);
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
        ->postJson(route('projects.users.store', $project), ['username' => 'jdoe'])
        ->assertOk()
        ->assertJsonPath('users.0.id', $ldapUser->id);

    $this->assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $ldapUser->id,
        'source' => 'ldap',
        'ldap_identifier' => 'jdoe',
    ]);

    $this->actingAs($owner)
        ->deleteJson(route('projects.users.destroy', [$project, $ldapUser]))
        ->assertOk()
        ->assertJsonCount(0, 'users');

    $this->assertDatabaseMissing('project_user', [
        'project_id' => $project->id,
        'user_id' => $ldapUser->id,
    ]);
});

test('non owner cannot manage project users directly', function () {
    $owner = User::factory()->create();
    $nonOwner = User::factory()->create();
    $member = User::factory()->create();
    $project = projectInvolvementProjectFor($owner);

    $this->actingAs($nonOwner)
        ->postJson(route('projects.users.store', $project), ['username' => 'someone'])
        ->assertForbidden();

    $project->involvedUsers()->attach($member->id, [
        'source' => 'ldap',
        'ldap_identifier' => 'member',
        'role' => 'member',
    ]);

    $this->actingAs($nonOwner)
        ->deleteJson(route('projects.users.destroy', [$project, $member]))
        ->assertForbidden();

    $this->assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $member->id,
    ]);
});

test('LDAP search returns matching company users and marks existing project users', function () {
    $owner = User::factory()->create();
    $project = projectInvolvementProjectFor($owner);
    $member = User::factory()->create([
        'email' => 'jane.doe@example.com',
        'ldap_username' => 'jdoe',
        'name' => 'Jane Doe',
    ]);

    $project->involvedUsers()->attach($member->id, [
        'source' => 'ldap',
        'ldap_identifier' => 'jdoe',
        'role' => 'member',
    ]);

    $this->app->instance(LdapService::class, new class extends LdapService
    {
        public function searchUsers(string $term, int $limit = 8): array
        {
            return [[
                'avatar' => null,
                'email' => 'jane.doe@example.com',
                'name' => 'Jane Doe',
                'username' => 'jdoe',
            ]];
        }
    });

    $this->actingAs($owner)
        ->getJson(route('ldap.users.search', [
            'q' => 'jan',
            'project_id' => $project->id,
        ]))
        ->assertOk()
        ->assertJsonPath('users.0.username', 'jdoe')
        ->assertJsonPath('users.0.already_member', true);
});
