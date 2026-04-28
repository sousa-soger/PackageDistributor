<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repository extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
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
        'username',
        'access_token',
        'last_synced_at',
    ];

    protected $casts = [
        'branches'       => 'array',
        'tags'           => 'array',
        'last_synced_at' => 'datetime',
        'access_token'   => 'encrypted',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
