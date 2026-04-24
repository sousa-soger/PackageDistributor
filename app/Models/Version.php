<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    // ** Should be taken from GitLab Repo listing */
    protected $fillable = [
        'version_name',
        'commit_type',
        'app_name',
        'is_active',
        'update_type',
        'release_notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
