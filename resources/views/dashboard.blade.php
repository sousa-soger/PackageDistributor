@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Organize repositories, packages, and deployment activity by project.')

@section('topbar_actions')
    <div
        x-data="{ showCreateProjectModal: @js($errors->any()), selectedColor: @js(old('color', $colorOptions[0] ?? 'from-brand-rose to-brand-iris')) }"
        @open-create-project.window="showCreateProjectModal = true"
    >
        <button
            type="button"
            @click="showCreateProjectModal = true"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft hover:shadow-glow hover:brightness-[1.03] active:brightness-95 transition-base h-9 rounded-md px-3"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"></path>
                <path d="M12 5v14"></path>
            </svg>
            New Project
        </button>

        <template x-teleport="body">
            <div x-show="showCreateProjectModal" x-cloak class="relative z-50">
                <div
                    x-show="showCreateProjectModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-background/80 backdrop-blur-sm"
                ></div>

                <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
                    <div
                        x-show="showCreateProjectModal"
                        @click.away="showCreateProjectModal = false"
                        @keydown.escape.window="showCreateProjectModal = false"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="w-full max-w-2xl border border-border bg-background shadow-lg sm:rounded-2xl overflow-hidden"
                        role="dialog"
                        aria-modal="true"
                    >
                        <div class="brand-soft-bg px-6 py-5 border-b border-border/60">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-xl font-semibold tracking-tight">Create Project</h2>
                                    <p class="mt-1 text-sm text-muted-foreground">Create a project bucket first, then link repositories to it from the repositories page.</p>
                                </div>
                                <button
                                    type="button"
                                    @click="showCreateProjectModal = false"
                                    class="rounded-sm opacity-70 ring-offset-background transition-colors hover:bg-muted hover:text-foreground hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 p-1.5"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 6 6 18"></path>
                                        <path d="m6 6 12 12"></path>
                                    </svg>
                                    <span class="sr-only">Close</span>
                                </button>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('dashboard.store') }}" class="px-6 py-5 space-y-5">
                            @csrf

                            <div class="space-y-2">
                                <label for="project-name" class="text-sm font-medium leading-none">Project Name</label>
                                <input
                                    id="project-name"
                                    name="name"
                                    type="text"
                                    value="{{ old('name') }}"
                                    placeholder="Atlas Web"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                >
                                @error('name')
                                    <p class="text-xs text-failed">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="project-description" class="text-sm font-medium leading-none">Description</label>
                                <textarea
                                    id="project-description"
                                    name="description"
                                    rows="4"
                                    placeholder="Customer-facing storefront and marketing site"
                                    class="flex w-full rounded-xl border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                >{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="text-xs text-failed">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <label class="text-sm font-medium leading-none">Accent Gradient</label>
                                    <span class="text-xs text-muted-foreground">Uses the tokens already defined in [resources/css/app.css](/C:/xampp/htdocs/cyb-pack-dist/resources/css/app.css:1).</span>
                                </div>
                                <input type="hidden" name="color" :value="selectedColor">
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    @foreach ($colorOptions as $colorOption)
                                        <button
                                            type="button"
                                            @click="selectedColor = '{{ $colorOption }}'"
                                            class="rounded-2xl border p-3 text-left transition-base"
                                            :class="selectedColor === '{{ $colorOption }}' ? 'border-primary/50 bg-accent shadow-soft' : 'border-border hover:border-primary/30 hover:bg-secondary/40'"
                                        >
                                            <div class="h-10 rounded-xl bg-gradient-to-br {{ $colorOption }}"></div>
                                            <div class="mt-2 text-[11px] font-medium text-muted-foreground">{{ str_replace(['from-', 'to-'], ['', ''], $colorOption) }}</div>
                                        </button>
                                    @endforeach
                                </div>
                                @error('color')
                                    <p class="text-xs text-failed">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-col-reverse sm:flex-row sm:justify-between gap-2 border-t border-border/60 pt-4">
                                <p class="text-xs text-muted-foreground">After creating the project, connect or re-save repositories with a `project_id` to group them here.</p>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="showCreateProjectModal = false"
                                        class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        class="inline-flex h-9 items-center justify-center rounded-md brand-gradient-bg px-4 text-sm font-medium text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03]"
                                    >
                                        Save Project
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection

@section('content')
    <div
        class="space-y-6"
        x-data="projectsPage({
            projects: @js($projectCards)
        })"
    >
        <div class="hidden from-brand-rose to-brand-iris from-brand-teal to-brand-iris from-brand-iris to-brand-teal to-brand-rose to-brand-teal"></div>

        <section class="relative overflow-hidden rounded-2xl border border-border/70 bg-card p-6 lg:p-8 shadow-soft">
            <div class="absolute inset-0 brand-soft-bg opacity-90 pointer-events-none"></div>
            <div class="absolute -top-24 -right-20 h-72 w-72 rounded-full brand-gradient-bg opacity-25 blur-3xl pointer-events-none"></div>
            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-background/70 backdrop-blur px-3 py-1 text-xs font-medium border border-border/60">
                            <span class="status-dot bg-success animate-pulse-soft"></span>
                            {{ $projectCount > 0 ? 'Project workspace active' : 'Ready to organize' }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-background/70 backdrop-blur px-3 py-1 text-xs font-medium border border-border/60">
                            {{ $projectCount }} projects
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-background/70 backdrop-blur px-3 py-1 text-xs font-medium border border-border/60">
                            {{ $repositoryCount }} repositories
                        </span>
                    </div>

                    <h2 class="mt-3 text-2xl lg:text-3xl font-semibold tracking-tight">
                        Map repositories to the right <span class="brand-gradient-text">project</span>.
                    </h2>
                    <p class="mt-1.5 max-w-2xl text-sm text-muted-foreground">
                        This page ports the Cybix Craft project dashboard UI into Blade and wires it to your Laravel models, recent package history, and deployment activity.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="window.dispatchEvent(new CustomEvent('open-create-project'))"
                        class="inline-flex h-10 items-center justify-center rounded-md brand-gradient-bg px-4 text-sm font-medium text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03]"
                    >
                        Create Project
                    </button>
                    <a
                        href="{{ route('repositories') }}"
                        class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        Manage Repositories
                    </a>
                </div>
            </div>

            @if ($unassignedRepositoryCount > 0)
                <div class="relative mt-5 rounded-xl border border-queued/30 bg-background/80 px-4 py-3 text-sm text-foreground backdrop-blur-sm">
                    <span class="font-medium">{{ $unassignedRepositoryCount }} repositories</span>
                    are still unassigned. Create a project, then reconnect or update those repositories with a `project_id` to have them counted here.
                </div>
            @endif
        </section>

        <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($stats as $stat)
                <div class="section-card p-5">
                    <div class="flex items-center justify-between">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg
                            {{ $stat['tone'] === 'success' ? 'bg-success/10 text-success' : '' }}
                            {{ $stat['tone'] === 'running' ? 'bg-running/10 text-running' : '' }}
                            {{ $stat['tone'] === 'failed' ? 'bg-failed/10 text-failed' : '' }}"
                        >
                            @switch($stat['icon'])
                                @case('package')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 16h6"></path>
                                        <path d="M19 13v6"></path>
                                        <path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"></path>
                                        <path d="m7.5 4.27 9 5.15"></path>
                                        <polyline points="3.29 7 12 12 20.71 7"></polyline>
                                        <line x1="12" x2="12" y1="22" y2="12"></line>
                                    </svg>
                                    @break
                                @case('success')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <path d="m9 12 2 2 4-4"></path>
                                    </svg>
                                    @break
                                @case('rocket')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4.5 16.5c-1.5 1.26-1.5 2.74-1.5 4.5 1.76 0 3.24 0 4.5-1.5"></path>
                                        <path d="m12 15-3-3a21.7 21.7 0 0 1 3-7.5c2.1-3.27 5.13-4.5 9-4.5 0 3.87-1.23 6.9-4.5 9a21.7 21.7 0 0 1-7.5 3"></path>
                                        <path d="M9 12H4.5C3 12 2 13 2 14.5c0 1.34.63 2.77 1.5 3.5L6 20.5c.73.87 2.16 1.5 3.5 1.5C11 22 12 21 12 19.5V15"></path>
                                        <path d="M9 9V4.5C9 3 10 2 11.5 2c1.34 0 2.77.63 3.5 1.5L17.5 6c.87.73 1.5 2.16 1.5 3.5C19 11 18 12 16.5 12H12"></path>
                                    </svg>
                                    @break
                                @default
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <path d="m15 9-6 6"></path>
                                        <path d="m9 9 6 6"></path>
                                    </svg>
                            @endswitch
                        </div>
                        <span
                            class="text-xs font-medium inline-flex items-center gap-1
                            {{ $stat['tone'] === 'success' ? 'text-success' : '' }}
                            {{ $stat['tone'] === 'running' ? 'text-running' : '' }}
                            {{ $stat['tone'] === 'failed' ? 'text-failed' : '' }}"
                        >
                            @if ($stat['tone'] !== 'running')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m22 7-8.5 8.5-5-5L2 17"></path>
                                    <path d="M16 7h6v6"></path>
                                </svg>
                            @endif
                            {{ $stat['delta'] }}
                        </span>
                    </div>

                    <div class="mt-4 text-3xl font-semibold tracking-tight tabular-nums">{{ $stat['value'] }}</div>
                    <div class="mt-1 text-xs text-muted-foreground">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </section>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <section class="section-card p-0 xl:col-span-2 overflow-hidden">
                <header class="flex items-center justify-between p-5 border-b border-border/60">
                    <div>
                        <h3 class="text-sm font-semibold">Recent packages</h3>
                        <p class="text-xs text-muted-foreground">Latest update and rollback bundles</p>
                    </div>
                    <a href="{{ route('packages.index') }}" class="text-xs font-medium text-primary inline-flex items-center gap-1 hover:underline">
                        View all
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7 7h10v10"></path>
                            <path d="M7 17 17 7"></path>
                        </svg>
                    </a>
                </header>

                <ul class="divide-y divide-border/60">
                    @forelse ($recentPackages as $package)
                        <li class="flex items-center gap-4 p-4 hover:bg-secondary/40 transition-base">
                            <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 16h6"></path>
                                    <path d="M19 13v6"></path>
                                    <path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"></path>
                                    <path d="m7.5 4.27 9 5.15"></path>
                                    <polyline points="3.29 7 12 12 20.71 7"></polyline>
                                    <line x1="12" x2="12" y1="22" y2="12"></line>
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                                    <span class="text-sm font-medium truncate">{{ $package['projectName'] ?: 'Unassigned project' }}</span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold tracking-wider
                                        {{ $package['environment'] === 'DEV' ? 'bg-running/10 text-running border border-running/20' : '' }}
                                        {{ $package['environment'] === 'QA' ? 'bg-queued/10 text-queued border border-queued/20' : '' }}
                                        {{ $package['environment'] === 'PROD' ? 'bg-failed/10 text-failed border border-failed/20' : '' }}">
                                        {{ $package['environment'] }}
                                    </span>
                                </div>
                                <div class="font-mono text-[11px] text-muted-foreground truncate">{{ $package['name'] }}</div>
                            </div>

                            <div class="hidden md:flex items-center gap-2 text-xs text-muted-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                {{ $package['createdAtLabel'] }}
                            </div>

                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide
                                {{ $package['status'] === 'completed' ? 'bg-success/10 text-success border border-success/20' : '' }}
                                {{ $package['status'] === 'running' ? 'bg-running/10 text-running border border-running/20' : '' }}
                                {{ $package['status'] === 'queued' ? 'bg-queued/10 text-queued border border-queued/20' : '' }}
                                {{ in_array($package['status'], ['failed', 'cancelled']) ? 'bg-failed/10 text-failed border border-failed/20' : '' }}">
                                {{ strtoupper($package['status']) }}
                            </span>
                        </li>
                    @empty
                        <li class="p-8 text-sm text-muted-foreground">No packages yet. Start from <a href="{{ route('create-package') }}" class="text-primary hover:underline">Create Package</a>.</li>
                    @endforelse
                </ul>
            </section>

            <section class="section-card p-0 overflow-hidden">
                <header class="flex items-center justify-between p-5 border-b border-border/60">
                    <div>
                        <h3 class="text-sm font-semibold">Active deployments</h3>
                        <p class="text-xs text-muted-foreground">In flight right now</p>
                    </div>
                    <a href="{{ route('packages.index') }}" class="text-xs font-medium text-primary inline-flex items-center gap-1 hover:underline">
                        All
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7 7h10v10"></path>
                            <path d="M7 17 17 7"></path>
                        </svg>
                    </a>
                </header>

                <ul class="divide-y divide-border/60">
                    @forelse ($activeDeployments as $deployment)
                        <li class="p-4">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium truncate max-w-[60%]">{{ $deployment['serverName'] }}</span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide
                                    {{ $deployment['status'] === 'running' ? 'bg-running/10 text-running border border-running/20' : '' }}
                                    {{ $deployment['status'] === 'queued' ? 'bg-queued/10 text-queued border border-queued/20' : '' }}">
                                    {{ strtoupper($deployment['status']) }}
                                </span>
                            </div>
                            <div class="font-mono text-[11px] text-muted-foreground truncate">{{ $deployment['packageName'] }}</div>
                            <div class="mt-2 flex items-center justify-between gap-3 text-[11px] text-muted-foreground">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 font-semibold tracking-wider
                                    {{ $deployment['environment'] === 'DEV' ? 'bg-running/10 text-running border border-running/20' : '' }}
                                    {{ $deployment['environment'] === 'QA' ? 'bg-queued/10 text-queued border border-queued/20' : '' }}
                                    {{ $deployment['environment'] === 'PROD' ? 'bg-failed/10 text-failed border border-failed/20' : '' }}">
                                    {{ $deployment['environment'] }}
                                </span>
                                <span>{{ $deployment['projectName'] ?: 'Unassigned project' }} · {{ $deployment['deployedAtLabel'] }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="p-8 text-sm text-muted-foreground">No active deployments right now.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <section class="mt-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3">
                <div>
                    <h3 class="text-sm font-semibold">Projects</h3>
                    <p class="text-xs text-muted-foreground">Project cards adapted from the Cybix Craft dashboard UI.</p>
                </div>

                <div class="relative w-full sm:w-80">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <svg class="h-4 w-4 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z"></path>
                        </svg>
                    </div>
                    <input
                        type="search"
                        x-model="search"
                        placeholder="Search projects..."
                        class="w-full rounded-xl border border-border/70 bg-background py-2.5 pl-10 pr-4 text-sm text-foreground placeholder:text-muted-foreground shadow-sm outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20"
                    >
                </div>
            </div>

            <div x-show="projects.length === 0" x-cloak class="section-card text-center py-12">
                <div class="mx-auto h-12 w-12 rounded-2xl brand-soft-bg flex items-center justify-center text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"></path>
                        <path d="M8 10v4"></path>
                        <path d="M12 10v2"></path>
                        <path d="M16 10v6"></path>
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold">No projects yet</h3>
                <p class="mt-2 text-sm text-muted-foreground">Create your first project to start grouping repositories and package history.</p>
                <button
                    type="button"
                    @click="window.dispatchEvent(new CustomEvent('open-create-project'))"
                    class="mt-4 inline-flex h-10 items-center justify-center rounded-md brand-gradient-bg px-4 text-sm font-medium text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03]"
                >
                    Create Project
                </button>
            </div>

            <div x-show="projects.length > 0 && filteredProjects.length === 0" x-cloak class="section-card text-center py-12">
                <h3 class="text-lg font-semibold">No projects match your search</h3>
                <p class="mt-2 text-sm text-muted-foreground">Try a different keyword or clear the search field.</p>
                <button
                    type="button"
                    @click="clearSearch()"
                    class="mt-4 inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                >
                    Clear Search
                </button>
            </div>

            <div x-show="filteredProjects.length > 0" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <template x-for="project in filteredProjects" :key="project.id">
                    <div class="section-card p-5">
                        <div class="h-10 w-10 rounded-lg bg-gradient-to-br shadow-soft mb-3" :class="project.color"></div>
                        <div class="text-sm font-semibold" x-text="project.name"></div>
                        <div class="mt-1 text-xs text-muted-foreground min-h-[2.5rem]" x-text="project.description"></div>
                        <div class="mt-3 flex items-center justify-between text-[11px] text-muted-foreground">
                            <span x-text="project.repoCount + ' repositories'"></span>
                            <span x-text="project.lastDeployedLabel"></span>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-secondary/70 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-foreground/80">
                                <span x-text="project.packageCount"></span>
                                packages
                            </span>
                            <a href="{{ route('repositories') }}" class="text-xs font-medium text-primary inline-flex items-center gap-1 hover:underline">
                                Manage repos
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 7h10v10"></path>
                                    <path d="M7 17 17 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
function projectsPage({ projects }) {
    return {
        search: '',
        projects,

        get filteredProjects() {
            const query = this.search.trim().toLowerCase();

            if (!query) {
                return this.projects;
            }

            return this.projects.filter((project) => {
                return project.name.toLowerCase().includes(query) ||
                    project.slug.toLowerCase().includes(query) ||
                    project.description.toLowerCase().includes(query);
            });
        },

        clearSearch() {
            this.search = '';
        },
    };
}
</script>
@endpush
