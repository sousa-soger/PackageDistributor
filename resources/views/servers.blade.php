@extends('layouts.app')

@section('title', 'Servers')
@section('subtitle', 'Manage deployment targets for development, QA, and production packages.')

@section('topbar_actions')
    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-create-server'))"
        class="inline-flex h-9 items-center justify-center gap-2 rounded-md brand-gradient-bg px-3 text-sm font-medium text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03] active:brightness-95">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14" />
            <path d="M12 5v14" />
        </svg>
        New Server
    </button>
@endsection

@section('content')
    @php
        $environmentStyles = [
            'DEV' => 'border-running/30 bg-running/10 text-running',
            'QA' => 'border-queued/30 bg-queued/10 text-queued',
            'PROD' => 'border-failed/30 bg-failed/10 text-failed',
        ];

        $statusStyles = [
            'pending' => 'border-border/70 bg-muted/50 text-muted-foreground',
            'online' => 'border-success/30 bg-success/10 text-success',
            'offline' => 'border-failed/30 bg-failed/10 text-failed',
            'deploying' => 'border-running/30 bg-running/10 text-running',
        ];

        $oldServerId = old('server_id');
        $oldServer = $oldServerId ? $servers->firstWhere('id', (int) $oldServerId) : null;

        $oldServerPayload = [
            'id' => $oldServer?->id,
            'form_mode' => old('form_mode', 'create'),
            'name' => old('name', ''),
            'project_id' => filled(old('project_id')) ? (int) old('project_id') : null,
            'environment' => old('environment', 'DEV'),
            'host' => old('host', ''),
            'ssh_user' => old('ssh_user', 'deploy'),
            'port' => (int) old('port', 22),
            'deploy_path' => old('deploy_path', '/var/www/app'),
            'health_check_url' => old('health_check_url', ''),
            'auto_deploy_enabled' => (bool) old('auto_deploy_enabled', false),
            'auto_deploy_strategy' => old('auto_deploy_strategy', 'on_package_ready'),
            'production_approval_required' => (bool) old('production_approval_required', true),
            'notes' => old('notes', ''),
            'update_url' => $oldServer ? route('servers.update', $oldServer) : null,
        ];
    @endphp

    <div class="animate-fade-in space-y-5" x-data="serversPage({
        servers: @js($serverClientIndex),
        oldServer: @js($oldServerPayload),
        hasErrors: @js($errors->any()),
        storeUrl: @js(route('servers.store')),
    })" @open-create-server.window="openCreate()">
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1.3fr_0.7fr]">
            <section class="section-card p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold">Deployment Pipeline</h2>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Add servers per environment, then decide which package promotions can deploy automatically.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($environmentOptions as $environment => $label)
                            @php
                                $stats = $environmentStats[$environment] ?? ['count' => 0, 'automated' => 0];
                            @endphp
                            <div class="rounded-lg border border-border/70 bg-background/60 px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-2 w-2 rounded-full {{ str_contains($environmentStyles[$environment] ?? '', 'text-running') ? 'bg-running' : (str_contains($environmentStyles[$environment] ?? '', 'text-queued') ? 'bg-queued' : 'bg-failed') }}"></span>
                                    <span class="text-[10px] font-semibold tracking-wider text-muted-foreground">{{ $environment }}</span>
                                </div>
                                <div class="mt-1 text-lg font-semibold tabular-nums">{{ $stats['count'] }}</div>
                                <div class="text-[10px] text-muted-foreground">{{ $stats['automated'] }} auto</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="rounded-lg border border-border/70 bg-secondary/30 p-3">
                        <div class="flex items-center gap-2 text-xs font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-running" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                            Dev
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Safe target for immediate package-ready automation.</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-secondary/30 p-3">
                        <div class="flex items-center gap-2 text-xs font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-queued" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                            </svg>
                            QA
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Best target for approval checks before production.</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-secondary/30 p-3">
                        <div class="flex items-center gap-2 text-xs font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-failed" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10" />
                                <path d="M12 8v4" />
                                <path d="M12 16h.01" />
                            </svg>
                            Production
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Keep approval required unless the release path is proven.</p>
                    </div>
                </div>
            </section>

            <section class="section-card p-5">
                <h2 class="text-sm font-semibold">Automation Guardrails</h2>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-lg brand-soft-bg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-primary" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                                <path d="m3.3 7 8.7 5 8.7-5" />
                                <path d="M12 22V12" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-xs font-semibold">Immutable packages</div>
                            <p class="mt-0.5 text-xs text-muted-foreground">Each server should receive a selected package release, not a mutable folder.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-lg brand-soft-bg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-primary" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M10 2v2" />
                                <path d="M14 2v2" />
                                <path d="M4 7h16" />
                                <path d="M6 22h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z" />
                                <path d="M9 14h6" />
                                <path d="M9 18h3" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-xs font-semibold">Auditable deployments</div>
                            <p class="mt-0.5 text-xs text-muted-foreground">The next slice can attach package jobs and logs to each server.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-lg brand-soft-bg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-primary" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="m3 7 5 5-5 5" />
                                <path d="M21 7v10a4 4 0 0 1-4 4H8" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-xs font-semibold">Rollback-first</div>
                            <p class="mt-0.5 text-xs text-muted-foreground">Production targets remain approval-gated by default.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <div class="relative max-w-md flex-1">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
                <input type="search" x-model="search"
                    class="flex h-10 w-full rounded-md border border-input bg-card px-3 py-2 pl-9 text-sm text-foreground ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    placeholder="Search servers, hosts, projects...">
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <select x-model="environmentFilter"
                    class="h-10 rounded-md border border-input bg-card px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="all">All environments</option>
                    @foreach ($environmentOptions as $environment => $label)
                        <option value="{{ $environment }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select x-model="automationFilter"
                    class="h-10 rounded-md border border-input bg-card px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="all">All automation</option>
                    <option value="enabled">Auto deploy on</option>
                    <option value="disabled">Auto deploy off</option>
                </select>
            </div>
        </div>

        @if ($servers->isEmpty())
            <section class="section-card p-10 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl brand-soft-bg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect width="20" height="8" x="2" y="2" rx="2" />
                        <rect width="20" height="8" x="2" y="14" rx="2" />
                        <line x1="6" x2="6.01" y1="6" y2="6" />
                        <line x1="6" x2="6.01" y1="18" y2="18" />
                    </svg>
                </div>
                <h2 class="text-sm font-semibold">No servers configured</h2>
                <p class="mx-auto mt-1 max-w-md text-xs text-muted-foreground">
                    Add the first development, QA, or production target before connecting packages to automated deploys.
                </p>
                <button type="button" @click="openCreate()"
                    class="mt-4 inline-flex h-9 items-center justify-center gap-2 rounded-md brand-gradient-bg px-4 text-sm font-medium text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14" />
                        <path d="M12 5v14" />
                    </svg>
                    Add Server
                </button>
            </section>
        @else
            <div x-show="!hasVisibleServers()" x-cloak class="section-card p-8 text-center text-sm text-muted-foreground">
                No servers match the current filters.
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                @foreach ($servers as $server)
                    @php
                        $environmentClass = $environmentStyles[$server->environment] ?? 'border-border/70 bg-muted/50 text-muted-foreground';
                        $statusClass = $statusStyles[$server->status] ?? 'border-border/70 bg-muted/50 text-muted-foreground';
                        $strategyLabel = $autoDeployStrategies[$server->auto_deploy_strategy] ?? $server->auto_deploy_strategy;
                        $statusLabel = $statusOptions[$server->status] ?? $server->status;
                    @endphp
                    <article x-show="serverMatches({{ $server->id }})" x-cloak
                        class="section-card overflow-hidden p-0 transition-base">
                        <div class="flex items-start justify-between gap-4 border-b border-border/60 bg-secondary/30 px-5 py-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="truncate text-sm font-semibold">{{ $server->name }}</h2>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[10px] font-semibold tracking-wider {{ $environmentClass }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ $server->environment }}
                                    </span>
                                </div>
                                <p class="mt-1 truncate font-mono text-xs text-muted-foreground">{{ $server->ssh_user }}@{{ $server->host }}:{{ $server->port }}</p>
                            </div>

                            <div class="flex items-center gap-1">
                                <button type="button" @click="openEdit(serverById({{ $server->id }}))"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                                    title="Edit server">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('servers.destroy', $server) }}"
                                    onsubmit="return confirm('Remove this server?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-failed transition-colors hover:bg-failed/10"
                                        title="Remove server">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M3 6h18" />
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-[1fr_0.9fr]">
                            <div class="space-y-3">
                                <div>
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Deploy Path</div>
                                    <div class="mt-1 truncate font-mono text-xs">{{ $server->deploy_path }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Project</div>
                                    <div class="mt-1 text-xs">{{ $server->project?->name ?? 'Unassigned target' }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Health Check</div>
                                    @if ($server->health_check_url)
                                        <a href="{{ $server->health_check_url }}" class="mt-1 block truncate text-xs text-primary hover:underline"
                                            target="_blank" rel="noreferrer">{{ $server->health_check_url }}</a>
                                    @else
                                        <div class="mt-1 text-xs text-muted-foreground">Not configured</div>
                                    @endif
                                </div>
                            </div>

                            <div class="space-y-3 rounded-lg border border-border/70 bg-background/60 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-xs text-muted-foreground">Connection</span>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[10px] font-semibold {{ $statusClass }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-xs text-muted-foreground">Auto deploy</span>
                                    <span
                                        class="inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-semibold {{ $server->auto_deploy_enabled ? 'border-success/30 bg-success/10 text-success' : 'border-border/70 bg-muted/50 text-muted-foreground' }}">
                                        {{ $server->auto_deploy_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-xs text-muted-foreground">Strategy</span>
                                    <span class="max-w-[11rem] text-right text-xs">{{ $strategyLabel }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-xs text-muted-foreground">Production approval</span>
                                    <span class="text-xs">{{ $server->production_approval_required ? 'Required' : 'Optional' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-xs text-muted-foreground">Last deployed</span>
                                    <span class="text-xs">{{ $server->last_deployed_at?->diffForHumans() ?? 'Never' }}</span>
                                </div>
                            </div>
                        </div>

                        @if ($server->notes)
                            <div class="border-t border-border/60 px-5 py-3 text-xs text-muted-foreground">
                                {{ $server->notes }}
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        <template x-teleport="body">
            <div x-show="showModal" x-cloak class="relative z-50">
                <div x-show="showModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-background/80 backdrop-blur-sm"></div>

                <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4">
                    <form method="POST" :action="formAction" x-show="showModal" @keydown.escape.window="closeModal()"
                        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="my-8 w-full max-w-3xl overflow-hidden rounded-2xl border border-border bg-background shadow-lg"
                        role="dialog" aria-modal="true">
                        @csrf
                        <input type="hidden" name="form_mode" :value="modalMode">
                        <input type="hidden" name="server_id" :value="field.id || ''">
                        <template x-if="modalMode === 'edit'">
                            <input type="hidden" name="_method" value="PATCH">
                        </template>

                        <div class="flex items-start justify-between gap-4 border-b border-border/60 brand-soft-bg px-6 py-5">
                            <div>
                                <h2 class="text-lg font-semibold" x-text="modalMode === 'edit' ? 'Edit Server' : 'Add Server'"></h2>
                                <p class="mt-1 text-sm text-muted-foreground">Register the SSH target and choose how packages should reach it.</p>
                            </div>
                            <button type="button" @click="closeModal()"
                                class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 6 6 18" />
                                    <path d="m6 6 12 12" />
                                </svg>
                                <span class="sr-only">Close</span>
                            </button>
                        </div>

                        <div class="space-y-5 px-6 py-5">
                            @if ($errors->any())
                                <div class="rounded-lg border border-failed/30 bg-failed/10 p-3 text-sm text-failed">
                                    <div class="font-semibold">Please fix the highlighted fields.</div>
                                    <ul class="mt-1 list-disc space-y-0.5 pl-5 text-xs">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <label class="space-y-1.5">
                                    <span class="text-xs font-medium text-muted-foreground">Server name</span>
                                    <input name="name" x-model="field.name"
                                        class="h-10 w-full rounded-md border border-input bg-card px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                        placeholder="Production App Server">
                                </label>

                                <label class="space-y-1.5">
                                    <span class="text-xs font-medium text-muted-foreground">Project</span>
                                    <select name="project_id" x-model="field.project_id"
                                        class="h-10 w-full rounded-md border border-input bg-card px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                        <option value="">Unassigned</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="space-y-1.5">
                                    <span class="text-xs font-medium text-muted-foreground">Environment</span>
                                    <select name="environment" x-model="field.environment"
                                        class="h-10 w-full rounded-md border border-input bg-card px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                        @foreach ($environmentOptions as $environment => $label)
                                            <option value="{{ $environment }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="space-y-1.5">
                                    <span class="text-xs font-medium text-muted-foreground">Host / IP</span>
                                    <input name="host" x-model="field.host"
                                        class="h-10 w-full rounded-md border border-input bg-card px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                        placeholder="app.example.com">
                                </label>

                                <label class="space-y-1.5">
                                    <span class="text-xs font-medium text-muted-foreground">SSH user</span>
                                    <input name="ssh_user" x-model="field.ssh_user"
                                        class="h-10 w-full rounded-md border border-input bg-card px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                        placeholder="deploy">
                                </label>

                                <label class="space-y-1.5">
                                    <span class="text-xs font-medium text-muted-foreground">Port</span>
                                    <input name="port" type="number" min="1" max="65535" x-model="field.port"
                                        class="h-10 w-full rounded-md border border-input bg-card px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                </label>

                                <label class="space-y-1.5 md:col-span-2">
                                    <span class="text-xs font-medium text-muted-foreground">Deploy path</span>
                                    <input name="deploy_path" x-model="field.deploy_path"
                                        class="h-10 w-full rounded-md border border-input bg-card px-3 font-mono text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                        placeholder="/var/www/site">
                                </label>

                                <label class="space-y-1.5 md:col-span-2">
                                    <span class="text-xs font-medium text-muted-foreground">Health check URL</span>
                                    <input name="health_check_url" x-model="field.health_check_url"
                                        class="h-10 w-full rounded-md border border-input bg-card px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                        placeholder="https://app.example.com/up">
                                </label>
                            </div>

                            <div class="rounded-lg border border-border/70 bg-secondary/30 p-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <label class="flex items-start gap-3">
                                        <input type="hidden" name="auto_deploy_enabled" value="0">
                                        <input type="checkbox" name="auto_deploy_enabled" value="1" x-model="field.auto_deploy_enabled"
                                            class="mt-1 h-4 w-4 rounded border-border text-primary focus:ring-ring">
                                        <span>
                                            <span class="block text-sm font-medium">Enable auto deploy</span>
                                            <span class="block text-xs text-muted-foreground">Allow package events to queue deployment for this target.</span>
                                        </span>
                                    </label>

                                    <label class="flex items-start gap-3">
                                        <input type="hidden" name="production_approval_required" value="0">
                                        <input type="checkbox" name="production_approval_required" value="1"
                                            x-model="field.production_approval_required"
                                            class="mt-1 h-4 w-4 rounded border-border text-primary focus:ring-ring">
                                        <span>
                                            <span class="block text-sm font-medium">Require production approval</span>
                                            <span class="block text-xs text-muted-foreground">Keep a manual gate before production deploys.</span>
                                        </span>
                                    </label>

                                    <label class="space-y-1.5 md:col-span-2">
                                        <span class="text-xs font-medium text-muted-foreground">Automation strategy</span>
                                        <select name="auto_deploy_strategy" x-model="field.auto_deploy_strategy"
                                            class="h-10 w-full rounded-md border border-input bg-card px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                            @foreach ($autoDeployStrategies as $strategy => $label)
                                                <option value="{{ $strategy }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                            </div>

                            <label class="space-y-1.5">
                                <span class="text-xs font-medium text-muted-foreground">Notes</span>
                                <textarea name="notes" x-model="field.notes" rows="3"
                                    class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                    placeholder="Maintenance windows, release notes, deploy caveats..."></textarea>
                            </label>
                        </div>

                        <div class="flex flex-col-reverse gap-2 border-t border-border/60 px-6 py-4 sm:flex-row sm:justify-end">
                            <button type="button" @click="closeModal()"
                                class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm transition-colors hover:bg-accent">
                                Cancel
                            </button>
                            <button type="submit"
                                class="inline-flex h-9 items-center justify-center rounded-md brand-gradient-bg px-4 text-sm font-medium text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03]"
                                x-text="modalMode === 'edit' ? 'Update Server' : 'Save Server'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
@endsection

@push('scripts')
    <script>
        function serversPage(config) {
            return {
                search: '',
                environmentFilter: 'all',
                automationFilter: 'all',
                showModal: Boolean(config.hasErrors),
                modalMode: config.oldServer?.form_mode || 'create',
                field: config.hasErrors ? config.oldServer : null,
                servers: config.servers || [],
                storeUrl: config.storeUrl,

                init() {
                    if (!this.field) {
                        this.field = this.defaultServer();
                    }
                },

                get formAction() {
                    return this.modalMode === 'edit' && this.field.update_url ? this.field.update_url : this.storeUrl;
                },

                defaultServer() {
                    return {
                        id: null,
                        name: '',
                        project_id: '',
                        environment: 'DEV',
                        host: '',
                        ssh_user: 'deploy',
                        port: 22,
                        deploy_path: '/var/www/app',
                        health_check_url: '',
                        auto_deploy_enabled: false,
                        auto_deploy_strategy: 'on_package_ready',
                        production_approval_required: true,
                        notes: '',
                        update_url: null,
                    };
                },

                openCreate() {
                    this.modalMode = 'create';
                    this.field = this.defaultServer();
                    this.showModal = true;
                },

                openEdit(server) {
                    if (!server) {
                        return;
                    }

                    this.modalMode = 'edit';
                    this.field = {
                        ...this.defaultServer(),
                        ...server,
                        project_id: server.project_id || '',
                        auto_deploy_enabled: Boolean(server.auto_deploy_enabled),
                        production_approval_required: Boolean(server.production_approval_required),
                    };
                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                },

                serverById(serverId) {
                    return this.servers.find((server) => Number(server.id) === Number(serverId));
                },

                serverMatches(serverId) {
                    const server = this.serverById(serverId);

                    if (!server) {
                        return false;
                    }

                    if (this.environmentFilter !== 'all' && server.environment !== this.environmentFilter) {
                        return false;
                    }

                    if (this.automationFilter === 'enabled' && !server.auto_deploy_enabled) {
                        return false;
                    }

                    if (this.automationFilter === 'disabled' && server.auto_deploy_enabled) {
                        return false;
                    }

                    const query = this.search.trim().toLowerCase();

                    if (!query) {
                        return true;
                    }

                    return [
                        server.name,
                        server.host,
                        server.ssh_user,
                        server.deploy_path,
                        server.environment,
                    ].filter(Boolean).join(' ').toLowerCase().includes(query);
                },

                hasVisibleServers() {
                    return this.servers.some((server) => this.serverMatches(server.id));
                },
            };
        }
    </script>
@endpush
