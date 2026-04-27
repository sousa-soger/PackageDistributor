@extends('layouts.app')

@section('title', 'New Package')

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
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">Cybix Deployer</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950">Create package</h1>
            <p class="mt-1 text-sm text-slate-500">Generate update and rollback packages in one place.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 shadow-sm backdrop-blur">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Source mode</p>
            <p class="mt-1 text-sm font-semibold text-slate-800" x-text="vcsProvider.toUpperCase()"></p>
        </div>
    </div>

    <div x-show="phase === 'form'" x-cloak class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <div class="space-y-5">
            <section class="rounded-3xl border border-slate-200 bg-white/85 p-5 shadow-sm backdrop-blur sm:p-6">
                <div class="mb-5 flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#7FB7C9]/15 text-sm font-bold text-[#3A7E92]">1</div>
                    <div>
                        <h2 class="text-base font-semibold tracking-tight text-slate-950">Project & Repository</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Choose where this package comes from.</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Project</label>
                        <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                            Current Laravel version uses repository as the main selector.
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Repository</label>

                        <template x-if="vcsProvider === 'github'">
                            <select
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 shadow-sm outline-none transition focus:border-[#7FB7C9] focus:ring-4 focus:ring-[#7FB7C9]/15"
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
                                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                        GitLab is not connected. Connect GitLab first from Projects.
                                    </div>
                                </template>

                                <template x-if="gitlabConnected">
                                    <div class="space-y-2">
                                        <input
                                            type="search"
                                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#7FB7C9] focus:ring-4 focus:ring-[#7FB7C9]/15"
                                            placeholder="Search GitLab projects..."
                                            x-model="gitlabSearch"
                                        >

                                        <div class="max-h-56 overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                                            <template x-if="gitlabLoading">
                                                <div class="px-4 py-3 text-sm text-slate-500">Loading projects...</div>
                                            </template>

                                            <template x-for="project in filteredGitlabProjects" :key="project.id">
                                                <button
                                                    type="button"
                                                    class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left text-sm transition last:border-b-0 hover:bg-slate-50"
                                                    :class="selectedRepository == project.id ? 'bg-[#7FB7C9]/10' : ''"
                                                    @click="selectGitlabProject(project)"
                                                >
                                                    <span>
                                                        <span class="block font-semibold text-slate-800" x-text="project.name"></span>
                                                        <span class="block text-xs text-slate-500" x-text="project.path"></span>
                                                    </span>
                                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600" x-text="project.visibility"></span>
                                                </button>
                                            </template>

                                            <template x-if="!gitlabLoading && filteredGitlabProjects.length === 0">
                                                <div class="px-4 py-3 text-sm text-slate-500">No projects found.</div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-500" x-show="selectedRepository">
                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 font-medium" x-text="selectedRepositoryLabel"></span>
                    <span class="inline-flex items-center rounded-lg bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700">Connected</span>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/85 p-5 shadow-sm backdrop-blur sm:p-6">
                <div class="mb-5 flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#7FB7C9]/15 text-sm font-bold text-[#3A7E92]">2</div>
                    <div>
                        <h2 class="text-base font-semibold tracking-tight text-slate-950">Version Selection</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Pick a base and target. The real changes are generated by the backend job.</p>
                    </div>
                </div>

                <div class="grid items-end gap-3 md:grid-cols-[1fr_auto_1fr]">
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Base version</label>
                        <select
                            class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 shadow-sm outline-none transition focus:border-[#7FB7C9] focus:ring-4 focus:ring-[#7FB7C9]/15 disabled:bg-slate-50 disabled:text-slate-400"
                            x-model="form.base"
                            :disabled="isLoadingVersions || allRepoVersions.length === 0"
                            @change="handleVersionChange()"
                        >
                            <option value="">Select base</option>
                            <template x-for="version in allRepoVersions" :key="'base-' + version.unique_key">
                                <option :value="version.unique_key" x-text="version.typeLabel + ' · ' + version.name"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">Usually the currently deployed version.</p>
                    </div>

                    <div class="hidden items-center justify-center pb-3 md:flex">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#7FB7C9]/15 text-[#3A7E92]">→</div>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Target version</label>
                        <select
                            class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 shadow-sm outline-none transition focus:border-[#7FB7C9] focus:ring-4 focus:ring-[#7FB7C9]/15 disabled:bg-slate-50 disabled:text-slate-400"
                            x-model="form.head"
                            :disabled="isLoadingVersions || allRepoVersions.length === 0"
                            @change="handleVersionChange()"
                        >
                            <option value="">Select target</option>
                            <template x-for="version in allRepoVersions" :key="'head-' + version.unique_key">
                                <option :value="version.unique_key" x-text="version.typeLabel + ' · ' + version.name"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">Usually the latest target tag or branch.</p>
                    </div>
                </div>

                <template x-if="isLoadingVersions">
                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                        Loading branches, tags, and releases...
                    </div>
                </template>

                <template x-if="identicalVersions">
                    <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        Base and target cannot be identical. Choose two different versions.
                    </div>
                </template>

                <template x-if="duplicatePackage">
                    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        A package with this repository, environment, base, and target already exists.
                    </div>
                </template>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Detected changes</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Your current backend calculates this during package generation. Add a compare-preendpoint later if you want true pre-generation counts.
                            </p>
                        </div>
                        <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">Preview pending</span>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3">
                            <p class="text-xs font-semibold text-emerald-700">Added</p>
                            <p class="mt-1 text-2xl font-bold text-emerald-800">—</p>
                        </div>
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-3">
                            <p class="text-xs font-semibold text-blue-700">Modified</p>
                            <p class="mt-1 text-2xl font-bold text-blue-800">—</p>
                        </div>
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-3">
                            <p class="text-xs font-semibold text-red-700">Deleted</p>
                            <p class="mt-1 text-2xl font-bold text-red-800">—</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/85 p-5 shadow-sm backdrop-blur sm:p-6">
                <div class="mb-5 flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#7FB7C9]/15 text-sm font-bold text-[#3A7E92]">3</div>
                    <div>
                        <h2 class="text-base font-semibold tracking-tight text-slate-950">Environment & Package Settings</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Keep it simple, with advanced controls hidden.</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <template x-for="env in ['DEV', 'QA', 'PROD']" :key="env">
                        <button
                            type="button"
                            class="rounded-2xl border p-4 text-left transition"
                            :class="form.environment === env ? 'border-[#7FB7C9] bg-[#7FB7C9]/10 shadow-sm' : 'border-slate-200 bg-white hover:border-[#7FB7C9]/40'"
                            @click="form.environment = env; confirmedProd = false; updatePackageName(); checkDuplicate();"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold"
                                    :class="env === 'DEV' ? 'bg-blue-50 text-blue-700' : env === 'QA' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700'"
                                    x-text="env"></span>
                                <span x-show="form.environment === env" class="text-[#3A7E92]">✓</span>
                            </div>
                            <p class="mt-2 text-sm font-semibold text-slate-800" x-text="env === 'DEV' ? 'Development' : env === 'QA' ? 'Quality assurance' : 'Production'"></p>
                            <p class="mt-1 text-[11px] text-slate-500" x-text="env === 'DEV' ? 'Fastest flow' : env === 'QA' ? 'Review before deploy' : 'Extra confirmation'"></p>
                        </button>
                    </template>
                </div>

                <div class="mt-5">
                    <label class="text-sm font-semibold text-slate-700">Package name</label>
                    <input
                        type="text"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 font-mono text-xs text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-[#7FB7C9] focus:ring-4 focus:ring-[#7FB7C9]/15"
                        x-model="form.customName"
                        :placeholder="autoPackageName || 'Auto-generated when versions are picked'"
                    >
                    <p class="mt-1 text-[11px] text-slate-400">Leave empty to use the auto-generated name.</p>
                </div>

                <template x-if="form.environment === 'PROD'">
                    <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4">
                        <p class="text-sm font-semibold text-red-700">Production safety check</p>
                        <p class="mt-1 text-xs text-slate-600">Review the summary, then confirm to enable generation.</p>
                        <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" class="rounded border-slate-300 text-[#7FB7C9] focus:ring-[#7FB7C9]" x-model="confirmedProd">
                            I understand this package targets production.
                        </label>
                    </div>
                </template>

                <div class="mt-5">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-slate-900"
                        @click="showAdvanced = !showAdvanced"
                    >
                        <span x-text="showAdvanced ? '⌃' : '⌄'"></span>
                        Advanced settings
                    </button>

                    <div x-show="showAdvanced" class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-slate-700">Output format</label>
                                <select class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm" x-model="form.format">
                                    <option value=".zip">ZIP</option>
                                    <option value=".tar.gz">TAR.GZ</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>
                            <label class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <span>
                                    <span class="block text-sm font-semibold text-slate-800">Generate rollback package</span>
                                    <span class="block text-[11px] text-slate-500">Your backend currently generates update and rollback together.</span>
                                </span>
                                <input type="checkbox" class="rounded border-slate-300 text-[#7FB7C9] focus:ring-[#7FB7C9]" x-model="form.rollback" checked>
                            </label>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-5 xl:sticky xl:top-20 xl:self-start">
            <section class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm backdrop-blur">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-2.5 w-2.5 rounded-full bg-[linear-gradient(135deg,#C7A3B1,#7FB7C9,#8D93C7)]"></div>
                    <h3 class="text-sm font-bold text-slate-900">Live summary</h3>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3"><span class="text-xs text-slate-500">Repository</span><span class="max-w-[60%] truncate font-semibold text-slate-800" x-text="selectedRepositoryLabel || '—'"></span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-xs text-slate-500">Base</span><span class="max-w-[60%] truncate font-mono text-xs font-semibold text-slate-800" x-text="selectedBaseLabel || '—'"></span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-xs text-slate-500">Target</span><span class="max-w-[60%] truncate font-mono text-xs font-semibold text-slate-800" x-text="selectedHeadLabel || '—'"></span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-xs text-slate-500">Environment</span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700" x-text="form.environment"></span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-xs text-slate-500">Rollback</span><span class="font-semibold text-slate-800" x-text="form.rollback ? 'Included' : 'Skipped'"></span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-xs text-slate-500">Format</span><span class="font-semibold text-slate-800" x-text="form.format === 'both' ? 'Both' : form.format.toUpperCase()"></span></div>
                </div>

                <div class="my-4 h-px bg-slate-200"></div>

                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Package name</p>
                    <p class="mt-2 break-all font-mono text-[11px] leading-relaxed text-slate-700" x-text="finalPackageName || '—'"></p>
                </div>

                <button
                    type="button"
                    class="mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-[linear-gradient(135deg,#C7A3B1,#7FB7C9,#8D93C7)] px-5 text-base font-bold text-white shadow-sm transition hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-45"
                    :disabled="!canGenerate || isQueuing"
                    @click="startPackaging()"
                >
                    <span x-show="!isQueuing">⚡</span>
                    <span x-show="isQueuing" class="animate-spin">⟳</span>
                    <span x-text="isQueuing ? 'Queuing...' : 'Generate Package'"></span>
                </button>

                <p x-show="!canGenerate && form.environment === 'PROD'" class="mt-2 text-center text-[11px] text-slate-500">
                    Confirm production safety to continue.
                </p>
            </section>
        </aside>
    </div>

    <div x-show="phase === 'progress'" x-cloak class="mx-auto max-w-3xl">
        <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm backdrop-blur">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <div class="mb-1 flex items-center gap-2">
                        <span class="inline-block h-3 w-3 animate-pulse rounded-full bg-[#7FB7C9]"></span>
                        <span class="text-sm font-bold text-[#3A7E92]">Generating package</span>
                    </div>
                    <h2 class="break-all text-xl font-bold tracking-tight text-slate-950" x-text="finalPackageName"></h2>
                    <p class="mt-1 text-sm text-slate-500">
                        <span x-text="selectedRepositoryLabel"></span>
                        <span> · </span>
                        <span x-text="form.environment"></span>
                    </p>
                </div>

                <button type="button" class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-900" @click="cancelJob()">
                    Cancel
                </button>
            </div>

            <div class="mb-6">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="font-semibold text-slate-800" x-text="packagingMessage || activeStageLabel"></span>
                    <span class="font-mono text-slate-500" x-text="Math.floor(packagingProgress) + '%'"></span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-[linear-gradient(135deg,#C7A3B1,#7FB7C9,#8D93C7)] transition-all" :style="`width:${packagingProgress}%`"></div>
                </div>
            </div>

            <ol class="space-y-2.5">
                <template x-for="stage in stages" :key="stage.key">
                    <li class="flex items-center gap-3 text-sm">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border text-[11px] font-bold"
                            :class="stage.value <= packagingProgress ? 'border-transparent bg-[#7FB7C9] text-white' : 'border-slate-200 text-slate-400'">✓</span>
                        <span :class="stage.value <= packagingProgress ? 'font-semibold text-slate-800' : 'text-slate-500'" x-text="stage.label"></span>
                    </li>
                </template>
            </ol>
        </section>
    </div>

    <div x-show="phase === 'done'" x-cloak class="mx-auto max-w-3xl">
        <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 text-center shadow-sm backdrop-blur">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-3xl bg-[linear-gradient(135deg,#C7A3B1,#7FB7C9,#8D93C7)] text-3xl text-white shadow-sm">✓</div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-950">Package ready</h2>
            <p class="mt-2 break-all text-sm text-slate-500" x-text="finalPackageName"></p>

            <div class="mt-6 grid grid-cols-3 gap-3">
                <div class="rounded-2xl bg-slate-50 p-4"><p class="text-lg font-bold" x-text="packagingResult?.zip_size || '—'"></p><p class="mt-1 text-xs text-slate-500">ZIP size</p></div>
                <div class="rounded-2xl bg-slate-50 p-4"><p class="text-lg font-bold" x-text="packagingResult?.targz_size || '—'"></p><p class="mt-1 text-xs text-slate-500">TAR.GZ size</p></div>
                <div class="rounded-2xl bg-slate-50 p-4"><p class="text-lg font-bold" x-text="packagingResult?.summary?.total_changes ?? '—'"></p><p class="mt-1 text-xs text-slate-500">Changes</p></div>
            </div>

            <div class="mt-7 flex flex-col justify-center gap-2 sm:flex-row">
                <button type="button" class="rounded-2xl bg-[linear-gradient(135deg,#C7A3B1,#7FB7C9,#8D93C7)] px-5 py-3 text-sm font-bold text-white shadow-sm">Deploy Package</button>
                <button type="button" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50" @click="downloadPackage(form.format)">Download</button>
                <button type="button" class="rounded-2xl px-5 py-3 text-sm font-bold text-slate-500 hover:bg-slate-50" @click="resetForm()">Create Another</button>
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
