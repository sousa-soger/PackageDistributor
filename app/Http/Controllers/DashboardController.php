<?php

namespace App\Http\Controllers;

use App\Models\DeploymentJob;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $projects = $user->projects()
            ->withCount('repositories')
            ->orderBy('name')
            ->get();

        $completedJobsByProjectId = DeploymentJob::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('project_id')
            ->selectRaw('project_id, COUNT(*) as package_count, MAX(finished_at) as last_finished_at')
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        $completedJobsByProjectName = DeploymentJob::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNull('project_id')
            ->whereNotNull('project_name')
            ->selectRaw('project_name, COUNT(*) as package_count, MAX(finished_at) as last_finished_at')
            ->groupBy('project_name')
            ->get()
            ->keyBy(fn ($row) => Str::lower(trim($row->project_name)));

        $projectCards = $projects->map(function (Project $project) use ($completedJobsByProjectId, $completedJobsByProjectName) {
            $summaryById = $completedJobsByProjectId->get($project->id);
            $summaryByName = $completedJobsByProjectName->get(Str::lower(trim($project->name)));
            $summary = $summaryById ?? $summaryByName;

            $lastDeployedAt = $project->last_deployed_at;

            if (! $lastDeployedAt && $summary?->last_finished_at) {
                $lastDeployedAt = Carbon::parse($summary->last_finished_at);
            }

            return [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'description' => $project->description ?: 'No description added yet.',
                'color' => $project->color ?: Project::DEFAULT_COLOR,
                'repoCount' => (int) $project->repositories_count,
                'packageCount' => (int) ($summary?->package_count ?? 0),
                'lastDeployedLabel' => $lastDeployedAt ? $lastDeployedAt->diffForHumans() : 'Never deployed',
                'lastDeployedAt' => $lastDeployedAt?->toIso8601String(),
            ];
        })->values();

        $recentPackages = $user->deploymentJobs()
            ->with('project:id,name,color')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (DeploymentJob $job) => [
                'id' => $job->id,
                'name' => $job->package_name,
                'projectName' => $job->project?->name ?? $job->project_name,
                'environment' => $job->environment,
                'status' => $job->status,
                'createdAtLabel' => $job->created_at?->diffForHumans() ?? 'Just now',
            ])->values();

        $activeDeployments = $user->deploymentJobs()
            ->with('project:id,name,color')
            ->whereIn('status', ['queued', 'running'])
            ->latest()
            ->take(4)
            ->get()
            ->map(fn (DeploymentJob $job) => [
                'id' => $job->id,
                'serverName' => $job->repo,
                'packageName' => $job->package_name,
                'environment' => $job->environment,
                'status' => $job->status,
                'projectName' => $job->project?->name ?? $job->project_name,
                'deployedAtLabel' => $job->updated_at?->diffForHumans() ?? 'Just now',
            ])->values();

        $packagesThisWeek = $user->deploymentJobs()->where('created_at', '>=', now()->subDays(7))->count();
        $successfulDeploys = $user->deploymentJobs()->where('status', 'completed')->count();
        $activeDeploymentsCount = $user->deploymentJobs()->whereIn('status', ['queued', 'running'])->count();
        $failuresThisWeek = $user->deploymentJobs()->where('status', 'failed')->where('created_at', '>=', now()->subDays(7))->count();
        $repositoryCount = $user->repositories()->count();
        $unassignedRepositoryCount = $user->repositories()->whereNull('project_id')->count();

        $stats = [
            ['label' => 'Packages this week', 'value' => $packagesThisWeek,       'delta' => $projectCards->count().' tracked projects', 'tone' => 'success', 'icon' => 'package'],
            ['label' => 'Successful deploys', 'value' => $successfulDeploys,      'delta' => $repositoryCount.' linked repositories',   'tone' => 'success', 'icon' => 'success'],
            ['label' => 'Active deployments', 'value' => $activeDeploymentsCount, 'delta' => $activeDeploymentsCount > 0 ? 'live' : 'idle', 'tone' => 'running', 'icon' => 'rocket'],
            ['label' => 'Failures (7d)',       'value' => $failuresThisWeek,       'delta' => $failuresThisWeek > 0 ? 'review needed' : 'healthy', 'tone' => 'failed', 'icon' => 'failed'],
        ];

        return view('dashboard', [
            'projectCards' => $projectCards,
            'recentPackages' => $recentPackages,
            'activeDeployments' => $activeDeployments,
            'stats' => $stats,
            'colorOptions' => Project::COLOR_OPTIONS,
            'projectCount' => $projectCards->count(),
            'repositoryCount' => $repositoryCount,
            'unassignedRepositoryCount' => $unassignedRepositoryCount,
        ]);
    }
}
