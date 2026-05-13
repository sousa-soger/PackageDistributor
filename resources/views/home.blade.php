@extends('layouts.app')

@section('title', 'Home')
@section('subtitle', 'Choose what you want to do next in your deployment workspace.')

@section('content')
    @php
        $user = auth()->user();
        $displayName = $user?->name
            ?? $user?->ldap_username
            ?? $user?->email
            ?? 'User';
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">

        {{-- Welcome Section --}}
        <section class="relative overflow-hidden rounded-2xl border border-border/70 bg-card p-6 shadow-soft lg:p-8">
            <div class="pointer-events-none absolute inset-0 brand-soft-bg opacity-90"></div>
            <div
                class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full brand-gradient-bg opacity-25 blur-3xl">
            </div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-border/60 bg-background/70 px-3 py-1 text-xs font-medium text-muted-foreground backdrop-blur">
                        <span class="status-dot bg-success animate-pulse-soft"></span>
                        Deployment workspace ready
                    </div>

                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-foreground md:text-4xl">
                        Welcome back, <span class="brand-gradient-text">{{ $displayName }}</span>
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-muted-foreground md:text-base">
                        Start your package workflow, connect a repository, review queued jobs, or manage your project
                        workspace from one clean starting point.
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row lg:flex-col xl:flex-row">
                    <a href="{{ route('create-package') }}"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md brand-gradient-bg px-4 text-sm font-medium text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03] active:brightness-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M12 5v14"></path>
                            <path d="M5 12h14"></path>
                        </svg>
                        Create Package
                    </a>

                    <a href="{{ route('repositories') }}"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M15 6a9 9 0 0 0-9 9V3"></path>
                            <circle cx="18" cy="6" r="3"></circle>
                            <circle cx="6" cy="18" r="3"></circle>
                        </svg>
                        Connect Repository
                    </a>
                </div>
            </div>
        </section>

        {{-- Main Actions --}}
        <section>
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-foreground">
                        What would you like to do?
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        The most common actions are placed here for faster access.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

                <a href="{{ route('create-package') }}"
                    class="group section-card relative overflow-hidden p-5 transition-base hover:-translate-y-0.5">
                    <div class="absolute inset-x-0 top-0 h-1 brand-gradient-bg"></div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl brand-soft-bg text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M16 16h6"></path>
                            <path d="M19 13v6"></path>
                            <path
                                d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14">
                            </path>
                            <path d="m7.5 4.27 9 5.15"></path>
                            <polyline points="3.29 7 12 12 20.71 7"></polyline>
                            <line x1="12" x2="12" y1="22" y2="12"></line>
                        </svg>
                    </div>

                    <h3 class="mt-4 text-base font-semibold tracking-tight text-foreground">
                        Create Package
                    </h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                        Generate an update or rollback package from your selected repository version.
                    </p>

                    <div class="mt-4 inline-flex items-center gap-1 text-xs font-medium text-primary">
                        Start packaging
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-3 w-3 transition-transform group-hover:translate-x-0.5" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </div>
                </a>

                <a href="{{ route('repositories') }}"
                    class="group section-card relative overflow-hidden p-5 transition-base hover:-translate-y-0.5">
                    <div class="absolute inset-x-0 top-0 h-1 brand-gradient-bg"></div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl brand-soft-bg text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z">
                            </path>
                            <path d="M8 10v4"></path>
                            <path d="M12 10v2"></path>
                            <path d="M16 10v6"></path>
                        </svg>
                    </div>

                    <h3 class="mt-4 text-base font-semibold tracking-tight text-foreground">
                        Repositories
                    </h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                        Connect GitHub, GitLab, SSH, or uploaded repositories for package generation.
                    </p>

                    <div class="mt-4 inline-flex items-center gap-1 text-xs font-medium text-primary">
                        Manage sources
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-3 w-3 transition-transform group-hover:translate-x-0.5" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </div>
                </a>

                <a href="{{ route('packages.queue') }}"
                    class="group section-card relative overflow-hidden p-5 transition-base hover:-translate-y-0.5">
                    <div class="absolute inset-x-0 top-0 h-1 brand-gradient-bg"></div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl brand-soft-bg text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M10 2v2"></path>
                            <path d="M14 2v2"></path>
                            <path d="M16 8a4 4 0 0 1 0 8"></path>
                            <path d="M8 8a4 4 0 0 0 0 8"></path>
                            <path d="M12 12h.01"></path>
                            <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                        </svg>
                    </div>

                    <h3 class="mt-4 text-base font-semibold tracking-tight text-foreground">
                        Package Queue
                    </h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                        Check queued and running package jobs before starting another deployment task.
                    </p>

                    <div class="mt-4 inline-flex items-center gap-1 text-xs font-medium text-primary">
                        View queue
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-3 w-3 transition-transform group-hover:translate-x-0.5" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </div>
                </a>

                <a href="{{ route('packages.done') }}"
                    class="group section-card relative overflow-hidden p-5 transition-base hover:-translate-y-0.5">
                    <div class="absolute inset-x-0 top-0 h-1 brand-gradient-bg"></div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl brand-soft-bg text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>

                    <h3 class="mt-4 text-base font-semibold tracking-tight text-foreground">
                        Completed Packages
                    </h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                        Review previous packages and download completed deployment archives.
                    </p>

                    <div class="mt-4 inline-flex items-center gap-1 text-xs font-medium text-primary">
                        View history
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-3 w-3 transition-transform group-hover:translate-x-0.5" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
            </div>
        </section>

        {{-- Workflow and Secondary Links --}}
        <section class="grid grid-cols-1 gap-5 lg:grid-cols-[1.4fr_0.6fr]">

            <div class="section-card p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold tracking-tight text-foreground">
                            Recommended workflow
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Follow this order when preparing a package for deployment.
                        </p>
                    </div>

                    <div class="hidden h-10 w-10 items-center justify-center rounded-xl brand-soft-bg text-primary sm:flex">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M3 3v18h18"></path>
                            <path d="m19 9-5 5-4-4-3 3"></path>
                        </svg>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="rounded-2xl border border-border/70 bg-secondary/30 p-4">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-background text-sm font-semibold text-primary shadow-sm">
                            1
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-foreground">
                            Prepare repository
                        </h3>
                        <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                            Connect or update the source repository before creating a package.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-border/70 bg-secondary/30 p-4">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-background text-sm font-semibold text-primary shadow-sm">
                            2
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-foreground">
                            Select versions
                        </h3>
                        <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                            Choose the base and head version so the system can compare the changes.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-border/70 bg-secondary/30 p-4">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-background text-sm font-semibold text-primary shadow-sm">
                            3
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-foreground">
                            Generate package
                        </h3>
                        <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                            Queue the package job, monitor progress, then download the completed archive.
                        </p>
                    </div>
                </div>
            </div>

            <div class="section-card p-6">
                <h2 class="text-lg font-semibold tracking-tight text-foreground">
                    Workspace links
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Other areas you may need.
                </p>

                <div class="mt-5 space-y-2">
                    <a href="{{ route('projects') }}"
                        class="flex items-center justify-between rounded-xl border border-border/70 bg-background px-4 py-3 text-sm font-medium transition-base hover:bg-secondary/50">
                        <span>Projects</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-muted-foreground" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </a>

                    <a href="{{ route('team') }}"
                        class="flex items-center justify-between rounded-xl border border-border/70 bg-background px-4 py-3 text-sm font-medium transition-base hover:bg-secondary/50">
                        <span>Team</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-muted-foreground" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </a>

                    <a href="{{ route('packages.index') }}"
                        class="flex items-center justify-between rounded-xl border border-border/70 bg-background px-4 py-3 text-sm font-medium transition-base hover:bg-secondary/50">
                        <span>All Packages</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-muted-foreground" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </a>

                    <a href="{{ route('settings') }}"
                        class="flex items-center justify-between rounded-xl border border-border/70 bg-background px-4 py-3 text-sm font-medium transition-base hover:bg-secondary/50">
                        <span>Settings</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-muted-foreground" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection