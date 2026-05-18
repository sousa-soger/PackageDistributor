<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServerRequest;
use App\Http\Requests\UpdateServerRequest;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Server::class);

        $user = $request->user();
        $servers = $user->servers()
            ->with('project')
            ->orderBy('environment')
            ->orderBy('name')
            ->get();

        $projects = $user->projects()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('servers', [
            'autoDeployStrategies' => Server::AUTO_DEPLOY_STRATEGIES,
            'environmentOptions' => Server::ENVIRONMENTS,
            'environmentStats' => $this->environmentStats($servers),
            'projects' => $projects,
            'serverClientIndex' => $servers->map(fn (Server $server): array => $this->serverPayload($server))->values(),
            'servers' => $servers,
            'statusOptions' => Server::STATUSES,
        ]);
    }

    public function store(StoreServerRequest $request): RedirectResponse
    {
        $request->user()->servers()->create($this->validatedPayload($request->validated(), $request));

        return redirect()
            ->route('servers.index')
            ->with('success', 'Server added successfully.');
    }

    public function update(UpdateServerRequest $request, Server $server): RedirectResponse
    {
        $server->update($this->validatedPayload($request->validated(), $request));

        return redirect()
            ->route('servers.index')
            ->with('success', 'Server updated successfully.');
    }

    public function destroy(Server $server): RedirectResponse
    {
        $this->authorize('delete', $server);

        $server->delete();

        return redirect()
            ->route('servers.index')
            ->with('success', 'Server removed.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function validatedPayload(array $validated, Request $request): array
    {
        $validated['project_id'] = filled($validated['project_id'] ?? null) ? $validated['project_id'] : null;
        $validated['auto_deploy_enabled'] = $request->boolean('auto_deploy_enabled');
        $validated['production_approval_required'] = $request->boolean('production_approval_required');
        $validated['auto_deploy_strategy'] = ($validated['auto_deploy_strategy'] ?? null) ?: Server::DEFAULT_AUTO_DEPLOY_STRATEGY;

        return $validated;
    }

    /**
     * @param  iterable<Server>  $servers
     * @return array<string, array{count: int, automated: int}>
     */
    private function environmentStats(iterable $servers): array
    {
        $stats = collect(Server::ENVIRONMENTS)
            ->mapWithKeys(fn (string $label, string $environment): array => [
                $environment => ['count' => 0, 'automated' => 0],
            ])
            ->all();

        foreach ($servers as $server) {
            $stats[$server->environment]['count']++;

            if ($server->auto_deploy_enabled) {
                $stats[$server->environment]['automated']++;
            }
        }

        return $stats;
    }

    /**
     * @return array<string, mixed>
     */
    private function serverPayload(Server $server): array
    {
        return [
            'id' => $server->id,
            'auto_deploy_enabled' => $server->auto_deploy_enabled,
            'auto_deploy_strategy' => $server->auto_deploy_strategy,
            'deploy_path' => $server->deploy_path,
            'environment' => $server->environment,
            'health_check_url' => $server->health_check_url,
            'host' => $server->host,
            'name' => $server->name,
            'notes' => $server->notes,
            'port' => $server->port,
            'production_approval_required' => $server->production_approval_required,
            'project_id' => $server->project_id,
            'ssh_user' => $server->ssh_user,
            'update_url' => route('servers.update', $server),
        ];
    }
}
