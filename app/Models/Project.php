<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    public const DEFAULT_COLOR = 'from-brand-rose to-brand-iris';

    public const COLOR_OPTIONS = [
        'from-brand-rose to-brand-iris',
        'from-brand-teal to-brand-iris',
        'from-brand-iris to-brand-teal',
        'from-brand-rose to-brand-teal',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'color',
        'last_deployed_at',
    ];

    protected $casts = [
        'last_deployed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withTimestamps();
    }

    public function involvedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['source', 'ldap_identifier', 'role'])
            ->withTimestamps();
    }

    public function deploymentJobs(): HasMany
    {
        return $this->hasMany(DeploymentJob::class);
    }
}
