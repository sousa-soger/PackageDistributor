@extends('layouts.app')

@section('title', 'Create Package')
@section('subtitle', 'Generate update and rollback packages in one place.')

@section('content')
<div
    class="max-w-7xl mx-auto space-y-6 pt-4 pb-12"
    x-data="quickCreatePackage({
        repositories: @js($repositories),
        queueUrl: '{{ route('deployments.queue-job') }}',
        jobProgressBaseUrl: '{{ url('/deployments/jobs') }}',
        downloadUrl: '{{ route('download.archive') }}',
        csrfToken: '{{ csrf_token() }}',
        completedPackages: @js($packages),
        dbQueuedPackages: @js($queuedPackages),
        vcsProvider: @js($vcsProvider),
        gitlabConnected: @js($gitlabConnected),
        gitlabProjectsUrl: '{{ route('gitlab.projects') }}',
        gitlabVersionsBaseUrl: '{{ url('/gitlab/projects') }}',
        githubVersionsUrl: '{{ route('github.repo-versions') }}'
    })"
    x-init="init()"
>
    <div x-show="phase === 'form'" x-cloak class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        

        <div class="space-y-5">
            <section class="section-card">
                <div class="mb-5 flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg brand-soft-bg text-sm font-semibold text-primary">1</div>
                    <div>
                        <h2 class="text-base font-semibold tracking-tight">Project & Repository</h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">Choose where this package comes from.</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium leading-none">Project</label>
                        <div class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground">
                            Current Laravel version uses repository as the main selector.
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium leading-none">Repository</label>

                        <template x-if="vcsProvider === 'github'">
                            <select
                                class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                x-model="selectedRepository"
                            >
                                <option value="">Choose repository</option>
                                <template x-for="repo in repositories" :key="repo.id">
                                    <option :value="repo.id" x-text="repo.label"></option>
                                </template>
                            </select>
                        </template>

                        <template x-if="vcsProvider === 'gitlab'">
                            <div class="mt-2 space-y-2">
                                <template x-if="!gitlabConnected">
                                    <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                        GitLab is not connected. Connect GitLab first from Projects.
                                    </div>
                                </template>

                                <template x-if="gitlabConnected">
                                    <div class="space-y-2">
                                        <input
                                            type="search"
                                            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                            placeholder="Search GitLab projects..."
                                            x-model="gitlabSearch"
                                        >

                                        <div class="max-h-56 overflow-y-auto rounded-md border border-border bg-popover shadow-md mt-1">
                                            <template x-if="gitlabLoading">
                                                <div class="px-3 py-2 text-sm text-muted-foreground">Loading projects...</div>
                                            </template>

                                            <template x-for="project in filteredGitlabProjects" :key="project.id">
                                                <button
                                                    type="button"
                                                    class="flex w-full items-center justify-between gap-3 border-b border-border/30 px-3 py-2 text-left text-sm transition last:border-b-0 hover:bg-secondary/40"
                                                    :class="selectedRepository == project.id ? 'bg-secondary/60' : ''"
                                                    @click="selectGitlabProject(project)"
                                                >
                                                    <span>
                                                        <span class="block font-semibold" x-text="project.name"></span>
                                                        <span class="block text-[11px] text-muted-foreground mt-0.5" x-text="project.path"></span>
                                                    </span>
                                                    <span class="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-semibold text-foreground/80 lowercase" x-text="project.visibility"></span>
                                                </button>
                                            </template>

                                            <template x-if="!gitlabLoading && filteredGitlabProjects.length === 0">
                                                <div class="px-3 py-2 text-sm text-muted-foreground">No projects found.</div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 mt-4 text-xs text-muted-foreground" x-show="selectedRepository">
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-secondary/60 px-2 py-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"></path><path d="M9 18c-4.51 2-5-2-7-2"></path></svg>
                        <span class="capitalize" x-text="vcsProvider"></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-secondary/60 px-2 py-1" x-text="selectedRepositoryLabel"></span>
                    <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 border border-success/30 text-success bg-success/10">
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>Connected
                    </span>
                </div>
            </section>

            <section class="section-card">
                <div class="flex items-start gap-3 mb-5">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg brand-soft-bg text-sm font-semibold text-primary">2</div>
                    <div>
                        <h2 class="text-base font-semibold tracking-tight">Version Selection</h2>
                        <p class="text-xs text-muted-foreground mt-0.5">Pick a base and target. We'll detect changes immediately.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] gap-3 items-end">
                    <div class="space-y-2">
                        <label class="text-sm font-medium leading-none">Base version</label>
                        <select
                            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            x-model="form.base"
                            :disabled="isLoadingVersions || allRepoVersions.length === 0"
                            @change="handleVersionChange()"
                        >
                            <option value="">Select base</option>
                            <template x-for="version in allRepoVersions" :key="'base-' + version.unique_key">
                                <option :value="version.unique_key" x-text="version.typeLabel + ' · ' + version.name"></option>
                            </template>
                        </select>
                        <p class="text-[11px] text-muted-foreground">Suggested: last deployed version</p>
                    </div>

                    <div class="hidden md:flex items-center justify-center pb-8">
                        <div class="h-9 w-9 rounded-full brand-soft-bg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right h-4 w-4 text-primary"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium leading-none">Target version</label>
                        <select
                            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            x-model="form.head"
                            :disabled="isLoadingVersions || allRepoVersions.length === 0"
                            @change="handleVersionChange()"
                        >
                            <option value="">Select target</option>
                            <template x-for="version in allRepoVersions" :key="'head-' + version.unique_key">
                                <option :value="version.unique_key" x-text="version.typeLabel + ' · ' + version.name"></option>
                            </template>
                        </select>
                        <p class="text-[11px] text-muted-foreground">Suggested: latest tag</p>
                    </div>
                </div>

                <template x-if="isLoadingVersions">
                    <div class="mt-4 rounded-md border border-border bg-secondary/30 px-3 py-2 text-sm text-muted-foreground">
                        Loading branches, tags, and releases...
                    </div>
                </template>

                <template x-if="identicalVersions">
                    <div class="mt-4 rounded-md border border-failed/30 bg-failed/10 px-3 py-2 text-sm text-failed">
                        Base and target cannot be identical. Choose two different versions.
                    </div>
                </template>

                <template x-if="duplicatePackage">
                    <div class="mt-4 rounded-md border border-amber-200 bg-amber-500/10 px-3 py-2 text-sm text-amber-500">
                        A package with this repository, environment, base, and target already exists.
                    </div>
                </template>

                <div class="mt-5 animate-fade-in" x-show="form.base && form.head && !identicalVersions">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2 text-sm font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-4 w-4 text-primary"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path><path d="M4 17v2"></path><path d="M5 18H3"></path></svg>
                            Detected changes
                        </div>
                        <span class="text-xs text-muted-foreground">Calculating...</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-xl border p-3.5 border-success/25 bg-success/8"><div class="flex items-center gap-2 text-xs font-medium text-success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-plus2 h-4 w-4"><path d="M4 22h14a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v4"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M3 15h6"></path><path d="M6 12v6"></path></svg> Added</div><div class="mt-1.5 text-2xl font-semibold tabular-nums">—</div></div>
                        <div class="rounded-xl border p-3.5 border-running/25 bg-running/8"><div class="flex items-center gap-2 text-xs font-medium text-running"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-pen-line h-4 w-4"><path d="m18 5-2.414-2.414A2 2 0 0 0 14.172 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2"></path><path d="M21.378 12.626a1 1 0 0 0-3.004-3.004l-4.01 4.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"></path><path d="M8 18h1"></path></svg> Modified</div><div class="mt-1.5 text-2xl font-semibold tabular-nums">—</div></div>
                        <div class="rounded-xl border p-3.5 border-failed/25 bg-failed/8"><div class="flex items-center gap-2 text-xs font-medium text-failed"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-minus h-4 w-4"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M9 15h6"></path></svg> Deleted</div><div class="mt-1.5 text-2xl font-semibold tabular-nums">—</div></div>
                    </div>
                </div>
            </section>

            <section class="section-card">
                <div class="flex items-start gap-3 mb-5">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg brand-soft-bg text-sm font-semibold text-primary">3</div>
                    <div>
                        <h2 class="text-base font-semibold tracking-tight">Environment & Package Settings</h2>
                        <p class="text-xs text-muted-foreground mt-0.5">Where will this package be applied?</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <template x-for="env in ['DEV', 'QA', 'PROD']" :key="env">
                        <button
                            type="button"
                            class="rounded-xl border p-4 text-left transition-base"
                            :class="form.environment === env ? 'border-primary/50 brand-soft-bg shadow-soft' : 'border-border hover:border-primary/30 hover:bg-secondary/40'"
                            @click="form.environment = env; confirmedProd = false; updatePackageName(); checkDuplicate();"
                        >
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[10px] font-semibold tracking-wider"
                                      :class="env === 'DEV' ? 'bg-running/10 text-running border-running/30' : env === 'QA' ? 'bg-queued/10 text-queued border-queued/30' : 'bg-failed/10 text-failed border-failed/30'">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span><span x-text="env"></span>
                                </span>
                                <svg x-show="form.environment === env" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check h-4 w-4 text-primary"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>
                            </div>
                            <div class="text-sm font-medium" x-text="env === 'DEV' ? 'Development' : env === 'QA' ? 'Quality assurance' : 'Production'"></div>
                            <div class="text-[11px] text-muted-foreground mt-1" x-text="env === 'DEV' ? 'Fast deploy, no confirmation' : env === 'QA' ? 'Moderate confirmation' : 'Confirmation required'"></div>
                        </button>
                    </template>
                </div>

                <div class="mt-5 space-y-2">
                    <label class="text-sm font-medium leading-none">Package name</label>
                    <input
                        type="text"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-xs font-mono ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm"
                        x-model="form.customName"
                        :placeholder="autoPackageName || 'Auto-generated when versions are picked'"
                    >
                    <p class="text-[11px] text-muted-foreground">Leave empty to use the auto-generated name.</p>
                </div>

                <template x-if="form.environment === 'PROD'">
                    <div class="mt-5 rounded-md border border-failed/30 bg-failed/10 p-4">
                        <p class="text-sm font-semibold text-failed">Production safety check</p>
                        <p class="mt-1 text-xs text-muted-foreground">Review the summary, then confirm to enable generation.</p>
                        <label class="mt-3 flex items-center gap-2 text-sm text-foreground">
                            <input type="checkbox" class="rounded border-input text-primary focus:ring-primary" x-model="confirmedProd">
                            I understand this package targets production.
                        </label>
                    </div>
                </template>

                <div class="mt-5">
                    <button
                        type="button"
                        class="flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-base"
                        @click="showAdvanced = !showAdvanced"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down h-4 w-4 transition-transform" :class="showAdvanced ? 'rotate-180' : ''"><path d="m6 9 6 6 6-6"></path></svg>
                        Advanced settings
                    </button>

                    <div x-show="showAdvanced" class="mt-4 animate-fade-in rounded-md border border-border bg-secondary/30 p-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-medium leading-none">Output format</label>
                                <select class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" x-model="form.format">
                                    <option value=".zip">ZIP</option>
                                    <option value=".tar.gz">TAR.GZ</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>
                            <label class="flex items-center justify-between gap-3 rounded-md border border-border bg-background px-4 py-3">
                                <span>
                                    <span class="block text-sm font-medium leading-none">Generate rollback package</span>
                                    <span class="block text-[11px] text-muted-foreground mt-1.5">Your backend currently generates update and rollback together.</span>
                                </span>
                                <input type="checkbox" class="rounded border-input text-primary focus:ring-primary" x-model="form.rollback" checked>
                            </label>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-5 xl:sticky xl:top-20 xl:self-start">
            <section class="section-card">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-2.5 w-2.5 rounded-full brand-gradient-bg"></div>
                    <h3 class="text-sm font-semibold tracking-tight">Live summary</h3>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3"><span class="text-xs text-muted-foreground">Repository</span><span class="max-w-[60%] truncate font-medium" x-text="selectedRepositoryLabel || '—'"></span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-xs text-muted-foreground">Base</span><span class="max-w-[60%] truncate font-mono text-xs font-medium" x-text="selectedBaseLabel || '—'"></span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-xs text-muted-foreground">Target</span><span class="max-w-[60%] truncate font-mono text-xs font-medium" x-text="selectedHeadLabel || '—'"></span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-xs text-muted-foreground">Environment</span><span class="rounded-full bg-secondary px-2.5 py-1 text-xs font-semibold" x-text="form.environment"></span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-xs text-muted-foreground">Rollback</span><span class="font-medium" x-text="form.rollback ? 'Included' : 'Skipped'"></span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-xs text-muted-foreground">Format</span><span class="font-medium" x-text="form.format === 'both' ? 'Both' : form.format.toUpperCase()"></span></div>
                </div>

                <div class="my-4 h-px bg-border/50"></div>

                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Package name</p>
                    <p class="mt-2 break-all font-mono text-xs leading-relaxed" x-text="finalPackageName || '—'"></p>
                </div>

                <button
                    type="button"
                    class="mt-5 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md px-8 w-full h-12 text-base font-semibold brand-gradient-bg shadow-soft transition-colors hover:brightness-105 active:brightness-95 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"
                    :disabled="!canGenerate || isQueuing"
                    @click="startPackaging()"
                >
                    <span x-show="isQueuing" class="animate-spin">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-loader-2 h-4 w-4">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                        </svg>
                    </span>
                    <span x-show="!isQueuing">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap h-4 w-4">
                            <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path>
                        </svg>
                    </span>
                    <span x-text="isQueuing ? 'Queuing...' : 'Generate Package'"></span>
                </button>

                <p x-show="!canGenerate && form.environment === 'PROD'" class="mt-2 text-center text-[10px] text-muted-foreground">
                    Confirm production safety to continue.
                </p>
            </section>
        </aside>
    </div>

    <div x-show="phase === 'progress'" x-cloak class="mx-auto max-w-3xl">
        <section class="section-card">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <div class="mb-1 flex items-center gap-2">
                        <span class="inline-block h-3 w-3 animate-pulse-soft rounded-full bg-primary/80"></span>
                        <span class="text-sm font-semibold tracking-tight text-primary">Generating package</span>
                    </div>
                    <h2 class="break-all text-xl font-bold tracking-tight" x-text="finalPackageName"></h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        <span x-text="selectedRepositoryLabel"></span>
                        <span> · </span>
                        <span x-text="form.environment"></span>
                    </p>
                </div>

                <button type="button" class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground px-3 text-sm font-medium shadow-sm transition-colors" @click="cancelJob()">
                    Cancel
                </button>
            </div>

            <div class="mb-6">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="font-medium" x-text="packagingMessage || activeStageLabel"></span>
                    <span class="font-mono text-muted-foreground" x-text="Math.floor(packagingProgress) + '%'"></span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-secondary">
                    <div class="h-full rounded-full bg-primary transition-all duration-500" :style="`width:${packagingProgress}%`"></div>
                </div>
            </div>

            <ol class="space-y-2.5">
                <template x-for="stage in stages" :key="stage.key">
                    <li class="flex items-center gap-3 text-sm">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full border text-[10px] font-bold transition-colors"
                            :class="stage.value <= packagingProgress ? 'border-transparent bg-primary text-primary-foreground' : 'border-border text-muted-foreground'">✓</span>
                        <span :class="stage.value <= packagingProgress ? 'font-medium' : 'text-muted-foreground'" x-text="stage.label"></span>
                    </li>
                </template>
            </ol>
        </section>
    </div>

    <div x-show="phase === 'done'" x-cloak class="mx-auto max-w-3xl">
        <section class="section-card text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl brand-soft-bg text-3xl text-primary shadow-soft">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check h-8 w-8"><path d="M20 6 9 17l-5-5"></path></svg>
            </div>
            <h2 class="text-2xl font-semibold tracking-tight">Package ready</h2>
            <p class="mt-2 break-all text-sm text-muted-foreground" x-text="finalPackageName"></p>

            <div class="mt-6 grid grid-cols-3 gap-3">
                <div class="rounded-xl border border-border bg-secondary/20 p-4"><p class="text-lg font-semibold tracking-tight" x-text="packagingResult?.zip_size || '—'"></p><p class="mt-1 text-xs text-muted-foreground">ZIP size</p></div>
                <div class="rounded-xl border border-border bg-secondary/20 p-4"><p class="text-lg font-semibold tracking-tight" x-text="packagingResult?.targz_size || '—'"></p><p class="mt-1 text-xs text-muted-foreground">TAR.GZ size</p></div>
                <div class="rounded-xl border border-border bg-secondary/20 p-4"><p class="text-lg font-semibold tracking-tight" x-text="packagingResult?.summary?.total_changes ?? '—'"></p><p class="mt-1 text-xs text-muted-foreground">Changes</p></div>
            </div>

            <div class="mt-7 flex flex-col justify-center gap-2 sm:flex-row">
                <button type="button" class="inline-flex h-9 items-center justify-center rounded-md bg-primary text-primary-foreground px-4 text-sm font-medium shadow hover:bg-primary/90 transition-colors">Deploy Package</button>
                <button type="button" class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground px-4 text-sm font-medium shadow-sm transition-colors" @click="downloadPackage(form.format)">Download</button>
                <button type="button" class="inline-flex h-9 items-center justify-center rounded-md px-4 text-sm font-medium text-muted-foreground hover:bg-secondary/80 transition-colors" @click="resetForm()">Create Another</button>
            </div>
        </section>
    </div>

    <div class="grid gap-6 lg:grid-cols-2" x-show="phase === 'form'">
        @includeIf('components.packaging-wizardV3.active-jobs-card')
    </div>
</div>
@endsection

@push('scripts')
<script>
function quickCreatePackage({
    repositories,
    queueUrl,
    jobProgressBaseUrl,
    downloadUrl,
    csrfToken,
    completedPackages,
    dbQueuedPackages,
    vcsProvider,
    gitlabConnected,
    gitlabProjectsUrl,
    gitlabVersionsBaseUrl,
    githubVersionsUrl,
}) {
    return {
        repositories,
        completedPackages,
        dbQueuedPackages,
        vcsProvider,
        gitlabConnected,
        queueUrl,
        jobProgressBaseUrl,
        downloadUrl,
        csrfToken,
        gitlabProjectsUrl,
        gitlabVersionsBaseUrl,
        githubVersionsUrl,

        phase: 'form',
        selectedRepository: '',
        selectedRepositoryLabelOverride: '',
        gitlabSelectedPath: '',
        gitlabProjects: [],
        gitlabSearch: '',
        gitlabLoading: false,

        repoBranches: [],
        repoTags: [],
        repoReleases: [],
        isLoadingVersions: false,

        form: {
            environment: 'DEV',
            base: '',
            head: '',
            customName: '',
            format: '.zip',
            rollback: true,
        },

        showAdvanced: false,
        confirmedProd: false,
        duplicatePackage: null,
        isQueuing: false,
        currentJobId: null,
        pollIntervalId: null,
        packagingProgress: 0,
        packagingMessage: '',
        packagingResult: null,
        packagingError: '',

        stages: [
            { key: 'queued', label: 'Queued', value: 5 },
            { key: 'downloading', label: 'Downloading', value: 15 },
            { key: 'extracting', label: 'Extracting', value: 35 },
            { key: 'comparing', label: 'Comparing', value: 55 },
            { key: 'generating', label: 'Generating packages', value: 75 },
            { key: 'compressing', label: 'Compressing', value: 90 },
            { key: 'completed', label: 'Completed', value: 100 },
        ],

        init() {
            if (this.vcsProvider === 'gitlab' && this.gitlabConnected) {
                this.loadGitlabProjects();
            }

            this.$watch('selectedRepository', async () => {
                this.resetVersionState();
                if (this.selectedRepository) {
                    await this.fetchRepoVersions();
                }
            });
        },

        get filteredGitlabProjects() {
            const q = this.gitlabSearch.toLowerCase();
            return this.gitlabProjects.filter(project => {
                return !q || project.name.toLowerCase().includes(q) || (project.path || '').toLowerCase().includes(q);
            });
        },

        get selectedRepositoryLabel() {
            if (this.selectedRepositoryLabelOverride) return this.selectedRepositoryLabelOverride;
            const repo = this.repositories.find(r => r.id === this.selectedRepository);
            return repo ? repo.label : this.selectedRepository;
        },

        get allRepoVersions() {
            const releases = this.repoReleases.map(r => ({
                unique_key: `release:${r.id ?? r.tag_name ?? r.name}`,
                type: 'release',
                typeLabel: 'Release',
                name: r.name || r.tag_name,
                ref: r.tag_name || r.name,
            }));

            const tags = this.repoTags.map(t => ({
                unique_key: `tag:${t.name}`,
                type: 'tag',
                typeLabel: 'Tag',
                name: t.name,
                ref: t.name,
            }));

            const branches = this.repoBranches.map(b => ({
                unique_key: `branch:${b.name}`,
                type: 'branch',
                typeLabel: 'Branch',
                name: b.name,
                ref: b.name,
            }));

            return [...releases, ...tags, ...branches];
        },

        get selectedBaseObj() {
            return this.allRepoVersions.find(v => v.unique_key === this.form.base) || null;
        },

        get selectedHeadObj() {
            return this.allRepoVersions.find(v => v.unique_key === this.form.head) || null;
        },

        get selectedBaseLabel() {
            return this.selectedBaseObj ? this.selectedBaseObj.name : '';
        },

        get selectedHeadLabel() {
            return this.selectedHeadObj ? this.selectedHeadObj.name : '';
        },

        get baseRef() {
            return this.selectedBaseObj ? this.selectedBaseObj.ref : '';
        },

        get headRef() {
            return this.selectedHeadObj ? this.selectedHeadObj.ref : '';
        },

        get identicalVersions() {
            return this.form.base && this.form.head && this.form.base === this.form.head;
        },

        get autoPackageName() {
            if (!this.selectedRepositoryLabel || !this.baseRef || !this.headRef) return '';
            const safe = value => String(value).replace(/[^\w.\-]+/g, '_');
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const ts = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}`;
            return `${this.form.environment}-${safe(this.selectedRepositoryLabel)}-${safe(this.baseRef)}-to-${safe(this.headRef)}-${ts}`;
        },

        get finalPackageName() {
            return this.form.customName.trim() || this.autoPackageName;
        },

        get canGenerate() {
            return !!(
                this.selectedRepository &&
                this.form.environment &&
                this.baseRef &&
                this.headRef &&
                this.finalPackageName &&
                !this.identicalVersions &&
                (this.form.environment !== 'PROD' || this.confirmedProd)
            );
        },

        get activeStageLabel() {
            const stage = [...this.stages].reverse().find(s => this.packagingProgress >= s.value);
            return stage ? stage.label : 'Queued';
        },

        async loadGitlabProjects() {
            this.gitlabLoading = true;
            try {
                const res = await fetch(this.gitlabProjectsUrl, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to load GitLab projects.');
                this.gitlabProjects = data;
            } catch (error) {
                this.gitlabProjects = [];
                console.error(error);
            } finally {
                this.gitlabLoading = false;
            }
        },

        selectGitlabProject(project) {
            this.selectedRepository = project.id;
            this.gitlabSelectedPath = project.path || String(project.id);
            this.selectedRepositoryLabelOverride = project.name;
        },

        async fetchRepoVersions() {
            this.isLoadingVersions = true;
            try {
                let url = '';
                if (this.vcsProvider === 'gitlab') {
                    url = `${this.gitlabVersionsBaseUrl}/${encodeURIComponent(this.selectedRepository)}/versions`;
                } else {
                    url = `${this.githubVersionsUrl}?repo=${encodeURIComponent(this.selectedRepository)}`;
                }

                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = res.ok ? await res.json() : {};

                this.repoBranches = data.branches || [];
                this.repoTags = data.tags || [];
                this.repoReleases = data.releases || [];

                this.autoPickVersions();
            } catch (error) {
                this.repoBranches = [];
                this.repoTags = [];
                this.repoReleases = [];
                console.error(error);
            } finally {
                this.isLoadingVersions = false;
            }
        },

        autoPickVersions() {
            const versions = this.allRepoVersions;
            if (versions.length === 0) return;

            this.form.head = versions[0]?.unique_key || '';
            this.form.base = versions[1]?.unique_key || versions[0]?.unique_key || '';

            if (this.form.base === this.form.head && versions[1]) {
                this.form.base = versions[1].unique_key;
            }

            this.checkDuplicate();
        },

        resetVersionState() {
            this.repoBranches = [];
            this.repoTags = [];
            this.repoReleases = [];
            this.form.base = '';
            this.form.head = '';
            this.form.customName = '';
            this.duplicatePackage = null;
        },

        handleVersionChange() {
            this.checkDuplicate();
        },

        updatePackageName() {
            this.checkDuplicate();
        },

        checkDuplicate() {
            if (!this.selectedRepository || !this.baseRef || !this.headRef) {
                this.duplicatePackage = null;
                return;
            }

            this.duplicatePackage = this.completedPackages.find(pkg => {
                return pkg.repo === this.selectedRepository &&
                    pkg.base_version === this.baseRef &&
                    pkg.head_version === this.headRef &&
                    pkg.environment === this.form.environment;
            }) || null;
        },

        async startPackaging() {
            if (!this.canGenerate || this.isQueuing) return;

            this.isQueuing = true;
            this.packagingError = '';
            this.packagingResult = null;
            this.packagingProgress = 0;
            this.packagingMessage = 'Submitting job to queue...';

            try {
                const res = await fetch(this.queueUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        environment: this.form.environment,
                        project_name: this.selectedRepositoryLabel,
                        base_version: this.baseRef,
                        head_version: this.headRef,
                        repo: this.vcsProvider === 'gitlab' ? this.gitlabSelectedPath : this.selectedRepository,
                        package_name: this.finalPackageName,
                        vcs_provider: this.vcsProvider,
                    }),
                });

                const data = await res.json();
                if (!res.ok || !data.job_id) {
                    throw new Error(data.message || 'Failed to queue package job.');
                }

                this.currentJobId = data.job_id;
                this.phase = 'progress';
                this.packagingMessage = 'Job queued. Waiting for worker...';
                this.startPolling();
            } catch (error) {
                this.packagingError = error.message || 'Unknown error.';
                this.packagingMessage = 'Failed to queue job.';
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: this.packagingError } }));
            } finally {
                this.isQueuing = false;
            }
        },

        startPolling() {
            this.stopPolling();

            this.pollIntervalId = setInterval(async () => {
                if (!this.currentJobId) {
                    this.stopPolling();
                    return;
                }

                try {
                    const res = await fetch(`${this.jobProgressBaseUrl}/${this.currentJobId}/progress?t=${Date.now()}`, {
                        cache: 'no-store',
                        headers: { Accept: 'application/json', 'Cache-Control': 'no-cache' },
                    });

                    if (!res.ok) return;
                    const payload = await res.json();
                    const progress = payload.progress || {};

                    this.packagingProgress = Math.max(this.packagingProgress, Number(progress.packagingProgress || 0));
                    if (progress.packagingMessage) this.packagingMessage = progress.packagingMessage;

                    if (payload.status === 'completed') {
                        this.stopPolling();
                        this.packagingProgress = 100;
                        this.packagingMessage = 'Package created successfully.';
                        this.packagingResult = payload.result;
                        this.phase = 'done';
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Package created successfully.' } }));
                    }

                    if (payload.status === 'failed') {
                        this.stopPolling();
                        this.packagingError = payload.error || 'Job failed.';
                        this.packagingMessage = 'Packaging failed.';
                        this.phase = 'form';
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: this.packagingError } }));
                    }

                    if (payload.status === 'cancelled') {
                        this.stopPolling();
                        this.packagingMessage = 'Job was cancelled.';
                        this.phase = 'form';
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'warning', message: 'Job was cancelled.' } }));
                    }
                } catch (error) {
                    // Keep polling through temporary network hiccups.
                }
            }, 1500);
        },

        stopPolling() {
            if (this.pollIntervalId) {
                clearInterval(this.pollIntervalId);
                this.pollIntervalId = null;
            }
        },

        async cancelJob() {
            if (!this.currentJobId) {
                this.phase = 'form';
                return;
            }

            try {
                await fetch(`${this.jobProgressBaseUrl}/${this.currentJobId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                });
            } catch (error) {
                console.error(error);
            }

            this.stopPolling();
            this.phase = 'form';
            this.packagingProgress = 0;
            this.packagingMessage = '';
        },

        downloadPackage(format = '.zip') {
            if (!this.packagingResult?.folder_name) return;
            const folder = encodeURIComponent(this.packagingResult.folder_name);

            if (format === 'both') {
                window.location.href = `${this.downloadUrl}?folder=${folder}&format=.zip`;
                setTimeout(() => {
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = `${this.downloadUrl}?folder=${folder}&format=.tar.gz`;
                    document.body.appendChild(iframe);
                    setTimeout(() => iframe.remove(), 10000);
                }, 800);
                return;
            }

            window.location.href = `${this.downloadUrl}?folder=${folder}&format=${encodeURIComponent(format)}`;
        },

        resetForm() {
            this.phase = 'form';
            this.confirmedProd = false;
            this.packagingResult = null;
            this.packagingError = '';
            this.packagingProgress = 0;
            this.packagingMessage = '';
            this.currentJobId = null;
            this.form.customName = '';
        },
    };
}
</script>
@endpush
