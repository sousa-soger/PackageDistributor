<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
    public const PACKAGE_CREATOR_ROLES = ['maintainer', 'creator'];

    public const PACKAGE_DEPLOYER_ROLES = ['maintainer', 'deployer'];

    protected $fillable = [
        'user_id',
        'project_id',
        'provider',
        'type',
        'name',
        'display_name',
        'url',
        'external_id',
        'default_branch',
        'branches',
        'tags',
        'status',
        'server_host',
        'server_path',
        'server_protocol',
        'remote_ip',
        'remote_path',
        'storage_path',
        'has_git_history',
        'username',
        'access_token',
        'last_synced_at',
    ];

    protected $casts = [
        'branches' => 'array',
        'tags' => 'array',
        'last_synced_at' => 'datetime',
        'access_token' => 'encrypted',
        'has_git_history' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['source', 'ldap_identifier', 'role'])
            ->withTimestamps();
    }

    public function deploymentJobs(): HasMany
    {
        return $this->hasMany(DeploymentJob::class);
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    /**
     * Human-readable label: display_name ?? name
     */
    public function getLabelAttribute(): string
    {
        return $this->display_name ?? $this->name;
    }

    /**
     * Branch count for UI display
     */
    public function getBranchCountAttribute(): int
    {
        return count($this->branches ?? []);
    }

    /**
     * Tag count for UI display
     */
    public function getTagCountAttribute(): int
    {
        return count($this->tags ?? []);
    }
}
