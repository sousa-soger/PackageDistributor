<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'password',
        'gitlab_id',
        'gitlab_username',
        'gitlab_name',
        'gitlab_email',
        'gitlab_avatar',
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
    
    public function isTeamOwnerOrAdmin($team = null): bool
    {
        return in_array($this->team_role ?? 'viewer', ['owner', 'maintainer']);
    }
}
