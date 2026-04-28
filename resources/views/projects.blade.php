@extends('layouts.app')

@section('title', 'Repositories')
@section('subtitle', 'GitHub, GitLab, company servers and local repositories.')

@section('topbar_actions')
    <div
        x-data="connectRepositoryModal({
            gitlabConnected: @js($gitlabConnected),
            gitlabOauthUrl: '{{ route('gitlab.oauth.redirect') }}'
        })"
        @open-connect-repository.window="open()"
    >
        <button
            @click="open()"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft hover:shadow-glow hover:brightness-[1.03] active:brightness-95 transition-base h-9 rounded-md px-3"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package-plus h-4 w-4">
                <path d="M16 16h6"></path>
                <path d="M19 13v6"></path>
                <path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"></path>
                <path d="m7.5 4.27 9 5.15"></path>
                <polyline points="3.29 7 12 12 20.71 7"></polyline>
                <line x1="12" x2="12" y1="22" y2="12"></line>
            </svg>
            Connect Repository
        </button>

        <template x-teleport="body">
            <div x-show="show" x-cloak class="relative z-50">
                <div
                    x-show="show"
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
                        x-show="show"
                        @click.away="close()"
                        @keydown.escape.window="close()"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="w-full max-w-xl grid gap-4 border border-border bg-background shadow-lg sm:rounded-lg p-0 overflow-hidden relative"
                        role="dialog"
                        aria-modal="true"
                    >
                        <div class="brand-soft-bg px-6 py-5 border-b border-border/60">
                            <div class="flex flex-col space-y-1.5 text-center sm:text-left">
                                <h2 class="font-semibold tracking-tight text-xl">Connect Repository</h2>
                                <p class="text-sm text-muted-foreground">This Laravel build is wired for GitLab OAuth today. The other providers are kept as UI placeholders from the original Cybix Craft page.</p>
                            </div>
                        </div>

                        <div class="px-6 py-5 max-h-[60vh] overflow-y-auto">
                            <div class="space-y-2">
                                <p class="text-sm text-muted-foreground mb-3">Pick a source for your repository.</p>

                                <template x-if="gitlabConnected">
                                    <div class="rounded-xl border border-success/20 bg-success/10 px-4 py-3 text-sm text-success">
                                        GitLab is already connected for this account. Use the page below to browse repositories or disconnect.
                                    </div>
                                </template>

                                <a
                                    x-show="!gitlabConnected"
                                    :href="gitlabOauthUrl"
                                    class="w-full text-left flex items-center gap-3 rounded-xl border border-border/70 bg-card p-3 hover:shadow-soft hover:border-primary/40 transition-base group"
                                >
                                    <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary shrink-0 transition-base group-hover:-translate-y-0.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gitlab h-5 w-5">
                                            <path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"></path>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold">GitLab</div>
                                        <div class="text-xs text-muted-foreground truncate">Connect a GitLab.com or self-hosted GitLab repository with OAuth.</div>
                                    </div>
                                    <div class="text-[10px] font-medium text-muted-foreground uppercase tracking-wider">live</div>
                                </a>

                                <button
                                    type="button"
                                    x-show="gitlabConnected"
                                    disabled
                                    class="w-full text-left flex items-center gap-3 rounded-xl border border-success/30 bg-success/10 p-3 text-success cursor-not-allowed"
                                >
                                    <div class="h-10 w-10 rounded-lg bg-white/70 flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gitlab h-5 w-5">
                                            <path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"></path>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold">GitLab</div>
                                        <div class="text-xs text-success/80 truncate">Already connected for this account.</div>
                                    </div>
                                    <div class="text-[10px] font-medium uppercase tracking-wider">connected</div>
                                </button>

                                <button type="button" disabled class="w-full text-left flex items-center gap-3 rounded-xl border border-border/70 bg-card p-3 opacity-60 cursor-not-allowed">
                                    <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-github h-5 w-5">
                                            <path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"></path>
                                            <path d="M9 18c-4.51 2-5-2-7-2"></path>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold">GitHub</div>
                                        <div class="text-xs text-muted-foreground truncate">Kept from the original design, but not wired into this page yet.</div>
                                    </div>
                                    <div class="text-[10px] font-medium text-muted-foreground uppercase tracking-wider">soon</div>
                                </button>

                                <button type="button" disabled class="w-full text-left flex items-center gap-3 rounded-xl border border-border/70 bg-card p-3 opacity-60 cursor-not-allowed">
                                    <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-server h-5 w-5">
                                            <rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect>
                                            <rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect>
                                            <line x1="6" x2="6.01" y1="6" y2="6"></line>
                                            <line x1="6" x2="6.01" y1="18" y2="18"></line>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold">Company Server</div>
                                        <div class="text-xs text-muted-foreground truncate">UI scaffold kept for later SSH repository support.</div>
                                    </div>
                                    <div class="text-[10px] font-medium text-muted-foreground uppercase tracking-wider">soon</div>
                                </button>

                                <button type="button" disabled class="w-full text-left flex items-center gap-3 rounded-xl border border-border/70 bg-card p-3 opacity-60 cursor-not-allowed">
                                    <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hard-drive h-5 w-5">
                                            <line x1="22" x2="2" y1="12" y2="12"></line>
                                            <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
                                            <line x1="6" x2="6.01" y1="16" y2="16"></line>
                                            <line x1="10" x2="10.01" y1="16" y2="16"></line>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold">Local PC</div>
                                        <div class="text-xs text-muted-foreground truncate">Reserved for future local repository indexing.</div>
                                    </div>
                                    <div class="text-[10px] font-medium text-muted-foreground uppercase tracking-wider">soon</div>
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row sm:space-x-2 px-6 py-4 border-t border-border/60 bg-muted/30 sm:justify-between gap-2">
                            <span class="text-xs text-muted-foreground">Compatible Blade port of the Cybix Craft repositories screen.</span>
                            <div class="flex items-center gap-2">
                                <button @click="close()" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">Close</button>
                            </div>
                        </div>

                        <button @click="close()" type="button" class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-colors hover:bg-muted hover:text-foreground hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none p-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x h-4 w-4">
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg>
                            <span class="sr-only">Close</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection

@section('content')
    <div
        class="space-y-6"
        x-data="repositoriesPage({
            gitlabConnected: @js($gitlabConnected),
            gitlabUsername: @js($gitlabUsername),
            gitlabName: @js(auth()->user()->gitlab_name),
            gitlabAvatar: @js(auth()->user()->gitlab_avatar),
            gitlabConnectedAt: @js(auth()->user()->gitlab_connected_at?->toIso8601String()),
            gitlabProjectsUrl: '{{ route('gitlab.projects') }}',
            gitlabExploreUrl: '{{ route('gitlab.explore') }}'
        })"
        x-init="init()"
    >
        <section x-show="!gitlabConnected" x-cloak class="section-card overflow-hidden">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:items-center">
                <div class="space-y-4">
                    <span class="inline-flex items-center gap-2 rounded-full border border-border/70 bg-background/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                        Repositories
                    </span>
                    <div class="space-y-2">
                        <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight">Turn the Cybix Craft repository page into a real Laravel screen.</h2>
                        <p class="max-w-2xl text-sm text-muted-foreground">Your layout is ready, but this page needs a live GitLab connection before it can replace the original mock repository cards. Once connected, the grid below will be fed by your existing Laravel routes instead of hardcoded sample data.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            @click="window.dispatchEvent(new CustomEvent('open-connect-repository'))"
                            class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft hover:shadow-glow hover:brightness-[1.03] active:brightness-95 transition-base h-10 rounded-md px-4"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"></path>
                            </svg>
                            Connect GitLab
                        </button>
                        <span class="text-xs text-muted-foreground">GitHub, SSH, and local repository flows are still UI placeholders on this page for now.</span>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute inset-0 rounded-[2rem] brand-soft-bg blur-3xl opacity-70"></div>
                    <div class="relative section-card p-5 bg-background/85 backdrop-blur-sm">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-border/70 bg-card px-4 py-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Laravel</div>
                                <div class="mt-1 text-sm font-semibold">Blade-ready layout</div>
                                <div class="mt-1 text-xs text-muted-foreground">Topbar, sidebar, gradients, and repository card styles already match your app shell.</div>
                            </div>
                            <div class="rounded-2xl border border-border/70 bg-card px-4 py-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Data source</div>
                                <div class="mt-1 text-sm font-semibold">GitLab routes</div>
                                <div class="mt-1 text-xs text-muted-foreground">Uses `/gitlab/projects` and `/gitlab/explore` instead of the React mock arrays.</div>
                            </div>
                            <div class="rounded-2xl border border-border/70 bg-card px-4 py-3 sm:col-span-2">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Next state</div>
                                <div class="mt-1 text-sm font-semibold">Browse real repositories after OAuth</div>
                                <div class="mt-1 text-xs text-muted-foreground">The repository grid, filters, and account summary activate as soon as GitLab is connected.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div x-show="gitlabConnected" x-cloak class="space-y-6 animate-fade-in">
            <section class="section-card overflow-hidden">
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-center">
                    <div class="flex items-start gap-4">
                        <div class="h-14 w-14 rounded-2xl brand-soft-bg flex items-center justify-center overflow-hidden shadow-soft shrink-0">
                            <template x-if="gitlabAvatar">
                                <img :src="gitlabAvatar" :alt="accountDisplayName" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!gitlabAvatar">
                                <span class="text-base font-semibold text-primary" x-text="accountInitials"></span>
                            </template>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-semibold tracking-tight">GitLab connection is live</h2>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-success/30 bg-success/10 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-success">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    Active
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                Signed in as <span class="font-medium text-foreground" x-text="accountDisplayName"></span>
                                <template x-if="gitlabUsername">
                                    <span class="text-muted-foreground">(<span x-text="'@' + gitlabUsername"></span>)</span>
                                </template>
                                <template x-if="gitlabConnectedAt">
                                    <span class="text-muted-foreground">. Connected <span x-text="formattedConnectedAt"></span>.</span>
                                </template>
                            </p>
                            <p class="text-xs text-muted-foreground">This Blade page now mirrors the Cybix Craft repositories screen, but it is backed by your Laravel GitLab routes and filters.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-secondary/70 px-3 py-1.5 text-xs font-medium text-foreground/80">
                            <span x-text="allRepositories.length"></span>
                            repositories
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-secondary/70 px-3 py-1.5 text-xs font-medium text-foreground/80">
                            <span x-text="personalCount"></span>
                            personal
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-secondary/70 px-3 py-1.5 text-xs font-medium text-foreground/80">
                            <span x-text="sharedCount"></span>
                            shared
                        </span>
                        <form action="{{ route('gitlab.oauth.disconnect') }}" method="POST" class="inline-flex">
                            @csrf
                            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                                Disconnect
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
                    <div class="space-y-3">
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="h-4 w-4 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z"></path>
                                </svg>
                            </div>
                            <input
                                type="search"
                                x-model="search"
                                placeholder="Search repositories by path, name, or description..."
                                class="w-full rounded-xl border border-border/70 bg-background py-3 pl-10 pr-4 text-sm text-foreground placeholder:text-muted-foreground shadow-sm outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20"
                            >
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                @click="activeFilter = 'all'"
                                :class="activeFilter === 'all' ? 'brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft' : 'border border-border bg-background text-muted-foreground hover:bg-secondary/50'"
                                class="inline-flex h-9 items-center justify-center rounded-full px-4 text-sm font-medium transition-base"
                            >
                                All
                            </button>
                            <button
                                type="button"
                                @click="activeFilter = 'personal'"
                                :class="activeFilter === 'personal' ? 'brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft' : 'border border-border bg-background text-muted-foreground hover:bg-secondary/50'"
                                class="inline-flex h-9 items-center justify-center rounded-full px-4 text-sm font-medium transition-base"
                            >
                                Personal
                            </button>
                            <button
                                type="button"
                                @click="activeFilter = 'shared'"
                                :class="activeFilter === 'shared' ? 'brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft' : 'border border-border bg-background text-muted-foreground hover:bg-secondary/50'"
                                class="inline-flex h-9 items-center justify-center rounded-full px-4 text-sm font-medium transition-base"
                            >
                                Shared
                            </button>
                        </div>
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl border border-border/70 bg-background/80 px-4 py-3 shadow-sm">
                        <input type="checkbox" x-model="showExplore" class="mt-1 h-4 w-4 rounded border-border text-primary focus:ring-ring">
                        <span class="space-y-1">
                            <span class="block text-sm font-medium">Include discoverable internal projects</span>
                            <span class="block text-xs text-muted-foreground">Loads GitLab projects you can see even when you are not a direct member.</span>
                        </span>
                    </label>
                </div>

                <div x-show="showExplore && loadingExplore" x-cloak class="mt-4 rounded-xl border border-queued/30 bg-queued/10 px-4 py-3 text-sm text-queued">
                    Loading discoverable internal repositories...
                </div>
            </section>

            <div x-show="error" x-cloak class="rounded-2xl border border-failed/30 bg-failed/10 px-4 py-3 text-sm text-failed flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <span x-text="error"></span>
                <button
                    type="button"
                    @click="loadRepositories()"
                    class="inline-flex h-9 items-center justify-center rounded-md border border-failed/30 bg-background px-3 text-sm font-medium text-failed transition-colors hover:bg-failed/5"
                >
                    Try again
                </button>
            </div>

            <div x-show="loading && memberRepositories.length === 0" x-cloak class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="index in 6" :key="'skeleton-' + index">
                    <div class="section-card p-5 animate-pulse space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="h-10 w-10 rounded-lg bg-secondary"></div>
                            <div class="h-6 w-20 rounded-md bg-secondary"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-4 w-3/4 rounded bg-secondary"></div>
                            <div class="h-3 w-1/2 rounded bg-secondary"></div>
                            <div class="h-3 w-full rounded bg-secondary"></div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <div class="h-5 w-20 rounded bg-secondary"></div>
                            <div class="h-5 w-24 rounded bg-secondary"></div>
                            <div class="h-5 w-28 rounded bg-secondary"></div>
                        </div>
                        <div class="h-3 w-1/3 rounded bg-secondary"></div>
                    </div>
                </template>
            </div>

            <div x-show="!loading && filteredRepositories.length === 0" x-cloak class="section-card text-center py-12">
                <div class="mx-auto h-12 w-12 rounded-2xl brand-soft-bg flex items-center justify-center text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 12 3l9 4.5M3 7.5V16.5L12 21m-9-13.5L12 12m9-4.5V16.5L12 21m0-9v9"></path>
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold">No repositories match the current filters</h3>
                <p class="mt-2 text-sm text-muted-foreground">Try another search term, switch to a different filter, or disable discoverable internal projects.</p>
            </div>

            <div x-show="filteredRepositories.length > 0" x-cloak class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="repo in filteredRepositories" :key="repo.source + '-' + repo.id">
                    <div class="section-card p-5">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"></path>
                                </svg>
                            </div>

                            <span
                                class="text-[11px] font-medium px-2 py-0.5 rounded-md border"
                                :class="statusClasses(repo)"
                                x-text="statusLabel(repo)"
                            ></span>
                        </div>

                        <div class="space-y-1.5">
                            <div class="text-sm font-semibold truncate" :title="repo.path" x-text="repo.path"></div>
                            <div class="text-xs text-muted-foreground truncate" :title="repo.name" x-text="repo.name"></div>
                        </div>

                        <p
                            class="mt-3 text-xs text-muted-foreground h-10 overflow-hidden"
                            :title="repo.description || 'No description provided.'"
                            x-text="repo.description || 'No description provided.'"
                        ></p>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground" x-text="categoryLabel(repo.category)"></span>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground" x-text="visibilityLabel(repo.visibility)"></span>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground" x-text="defaultBranchLabel(repo)"></span>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3 text-xs">
                            <span class="text-muted-foreground" x-text="'Updated ' + timeAgo(repo.lastActivity)"></span>
                            <a
                                :href="repo.web_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 text-primary font-medium hover:underline"
                            >
                                Open
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 7h10v10"></path>
                                    <path d="M7 17 17 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function connectRepositoryModal({ gitlabConnected, gitlabOauthUrl }) {
    return {
        show: false,
        gitlabConnected,
        gitlabOauthUrl,

        open() {
            this.show = true;
        },

        close() {
            this.show = false;
        },
    };
}

function repositoriesPage({
    gitlabConnected,
    gitlabUsername,
    gitlabName,
    gitlabAvatar,
    gitlabConnectedAt,
    gitlabProjectsUrl,
    gitlabExploreUrl,
}) {
    return {
        gitlabConnected,
        gitlabUsername,
        gitlabName,
        gitlabAvatar,
        gitlabConnectedAt,
        gitlabProjectsUrl,
        gitlabExploreUrl,

        loading: false,
        loadingExplore: false,
        error: '',
        search: '',
        activeFilter: 'all',
        showExplore: false,
        memberRepositories: [],
        exploreRepositories: [],

        init() {
            if (!this.gitlabConnected) {
                return;
            }

            this.loadRepositories();

            this.$watch('showExplore', (value) => {
                if (value && this.exploreRepositories.length === 0 && !this.loadingExplore) {
                    this.loadExploreRepositories();
                }
            });
        },

        get accountDisplayName() {
            return this.gitlabName || this.gitlabUsername || 'GitLab account';
        },

        get accountInitials() {
            const source = this.accountDisplayName.trim();

            if (!source) {
                return 'GL';
            }

            return source
                .split(/\s+/)
                .slice(0, 2)
                .map((part) => part.charAt(0).toUpperCase())
                .join('');
        },

        get formattedConnectedAt() {
            if (!this.gitlabConnectedAt) {
                return '';
            }

            return new Date(this.gitlabConnectedAt).toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            });
        },

        get allRepositories() {
            if (!this.showExplore) {
                return this.memberRepositories;
            }

            return [...this.memberRepositories, ...this.exploreRepositories];
        },

        get filteredRepositories() {
            const query = this.search.trim().toLowerCase();

            return this.allRepositories.filter((repo) => {
                const matchesSearch = !query ||
                    repo.path.toLowerCase().includes(query) ||
                    repo.name.toLowerCase().includes(query) ||
                    (repo.description || '').toLowerCase().includes(query);

                const matchesFilter = this.activeFilter === 'all' || repo.category === this.activeFilter;

                return matchesSearch && matchesFilter;
            });
        },

        get personalCount() {
            return this.allRepositories.filter((repo) => repo.category === 'personal').length;
        },

        get sharedCount() {
            return this.allRepositories.filter((repo) => repo.category === 'shared').length;
        },

        async loadRepositories() {
            this.loading = true;
            this.error = '';

            try {
                const response = await fetch(this.gitlabProjectsUrl, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to load GitLab repositories.');
                }

                this.memberRepositories = data;

                if (this.showExplore && this.exploreRepositories.length === 0) {
                    await this.loadExploreRepositories();
                }
            } catch (error) {
                this.error = error.message || 'Failed to load GitLab repositories.';
                this.memberRepositories = [];
            } finally {
                this.loading = false;
            }
        },

        async loadExploreRepositories() {
            this.loadingExplore = true;
            this.error = '';

            try {
                const response = await fetch(this.gitlabExploreUrl, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to load discoverable repositories.');
                }

                const memberIds = new Set(this.memberRepositories.map((repo) => repo.id));
                this.exploreRepositories = data.filter((repo) => !memberIds.has(repo.id));
            } catch (error) {
                this.error = error.message || 'Failed to load discoverable repositories.';
                this.exploreRepositories = [];
            } finally {
                this.loadingExplore = false;
            }
        },

        statusLabel(repo) {
            if (repo.source === 'explore') {
                return 'Visible';
            }

            return this.accessLevelLabel(repo.access_level);
        },

        statusClasses(repo) {
            if (repo.source === 'explore') {
                return 'bg-queued/10 text-queued border-queued/30';
            }

            if (repo.access_level >= 40) {
                return 'bg-success/10 text-success border-success/30';
            }

            if (repo.access_level >= 30) {
                return 'bg-running/10 text-running border-running/30';
            }

            return 'bg-secondary text-muted-foreground border-border';
        },

        accessLevelLabel(level) {
            const labels = {
                10: 'Guest',
                20: 'Reporter',
                30: 'Developer',
                40: 'Maintainer',
                50: 'Owner',
            };

            return labels[level] || 'Connected';
        },

        categoryLabel(category) {
            return category === 'personal' ? 'Personal' : 'Shared';
        },

        visibilityLabel(visibility) {
            if (!visibility) {
                return 'Unknown';
            }

            return visibility.charAt(0).toUpperCase() + visibility.slice(1);
        },

        defaultBranchLabel(repo) {
            return repo.default_branch ? `default: ${repo.default_branch}` : 'default: n/a';
        },

        timeAgo(dateString) {
            if (!dateString) {
                return 'recently';
            }

            const now = new Date();
            const then = new Date(dateString);
            const seconds = Math.floor((now - then) / 1000);

            if (seconds < 60) {
                return 'just now';
            }

            const minutes = Math.floor(seconds / 60);

            if (minutes < 60) {
                return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
            }

            const hours = Math.floor(minutes / 60);

            if (hours < 24) {
                return `${hours} hour${hours === 1 ? '' : 's'} ago`;
            }

            const days = Math.floor(hours / 24);

            if (days < 30) {
                return `${days} day${days === 1 ? '' : 's'} ago`;
            }

            const months = Math.floor(days / 30);

            if (months < 12) {
                return `${months} month${months === 1 ? '' : 's'} ago`;
            }

            const years = Math.floor(months / 12);

            return `${years} year${years === 1 ? '' : 's'} ago`;
        },
    };
}
</script>
@endpush
