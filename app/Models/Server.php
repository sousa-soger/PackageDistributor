<?php

namespace App\Models;

use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Server extends Model
{
    public const ENVIRONMENTS = [
        'DEV' => 'Development',
        'QA' => 'QA',
        'PROD' => 'Production',
    ];

    public const STATUSES = [
        'pending' => 'Pending verification',
        'online' => 'Online',
        'offline' => 'Offline',
        'deploying' => 'Deploying',
    ];

    public const AUTO_DEPLOY_STRATEGIES = [
        'on_package_ready' => 'When package is ready',
        'after_qa_success' => 'After QA succeeds',
        'manual_approval' => 'Manual approval',
    ];

    public const DEFAULT_AUTO_DEPLOY_STRATEGY = 'on_package_ready';

    /** @use HasFactory<ServerFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'name',
        'environment',
        'host',
        'ssh_user',
        'port',
        'deploy_path',
        'health_check_url',
        'status',
        'current_release',
        'auto_deploy_enabled',
        'auto_deploy_strategy',
        'production_approval_required',
        'notes',
        'last_checked_at',
        'last_deployed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auto_deploy_enabled' => 'boolean',
            'last_checked_at' => 'datetime',
            'last_deployed_at' => 'datetime',
            'port' => 'integer',
            'production_approval_required' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
