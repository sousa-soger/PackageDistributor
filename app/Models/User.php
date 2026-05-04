<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'ldap_username',
        'ldap_display_name',
        'ldap_photo',
        'github_id',
        'github_username',
        'github_name',
        'github_email',
        'github_token',
        'github_refresh_token',
        'github_token_expires_at',
        'github_connected_at',
        'password',
        'gitlab_id',
        'gitlab_username',
        'gitlab_name',
        'gitlab_email',
        'gitlab_token',
        'gitlab_refresh_token',
        'gitlab_token_expires_at',
        'gitlab_connected_at',
        'team_role',
        'team_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'github_token',
        'github_refresh_token',
        'gitlab_token',
        'gitlab_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'github_token' => 'encrypted',
            'github_refresh_token' => 'encrypted',
            'github_token_expires_at' => 'datetime',
            'github_connected_at' => 'datetime',
            'gitlab_token' => 'encrypted',
            'gitlab_refresh_token' => 'encrypted',
            'gitlab_token_expires_at' => 'datetime',
            'gitlab_connected_at' => 'datetime',
        ];
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withPivot(['role', 'status', 'invited_by_user_id'])
            ->withTimestamps();
    }

    public function involvedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot(['source', 'ldap_identifier', 'role'])
            ->withTimestamps();
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_user_id');
    }

    public function deploymentJobs(): HasMany
    {
        return $this->hasMany(DeploymentJob::class);
    }

    public function teamRole(?Team $team = null): ?string
    {
        if (! $team) {
            return $this->team_role;
        }

        if ($team->relationLoaded('members')) {
            $member = $team->members->firstWhere('id', $this->id);

            return $member?->pivot?->role;
        }

        $member = $team->members()->whereKey($this->id)->first();

        return $member?->pivot?->role;
    }

    public function isTeamOwnerOrAdmin($team = null): bool
    {
        $role = $team instanceof Team
            ? ($this->teamRole($team) ?? 'viewer')
            : ($this->team_role ?? 'viewer');

        return in_array($role, ['owner', 'maintainer'], true);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->ldap_photo;
    }

    public function getDisplayUsernameAttribute(): ?string
    {
        if ($this->ldap_username) {
            return $this->ldap_username;
        }

        if ($this->gitlab_username) {
            return $this->gitlab_username;
        }

        if ($this->github_username) {
            return $this->github_username;
        }

        if ($this->email && str_contains($this->email, '@')) {
            return explode('@', $this->email)[0];
        }

        return null;
    }
}
