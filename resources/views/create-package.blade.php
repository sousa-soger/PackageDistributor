@extends('layouts.app')

@section('title', 'Create Package')
@section('subtitle', 'Generate update and rollback packages in one place.')

@section('content')
    <div class="max-w-7xl mx-auto space-y-6 pt-4 pb-12" x-data="quickCreatePackage({
    repositories: @js($repositories),
    queueUrl: '{{ route('deployments.queue-job') }}',
    gitlessQueueUrl: '{{ route('deployments.queue-gitless-job') }}',
    previewUrl: '{{ route('deployments.preview-changes') }}',
    jobProgressBaseUrl: '{{ url('/deployments/jobs') }}',
    downloadUrl: '{{ route('download.archive') }}',
    csrfToken: '{{ csrf_token() }}',
    completedPackages: @js($packages),
    dbQueuedPackages: @js($queuedPackages),
    selectedRepositoryId: @js($selectedRepositoryId),
    repositoryVersionsBaseUrl: '{{ url('/repositories') }}'
    })" x-init="init()"
    @keydown.escape.window="baseVersionDropdownOpen = false; headVersionDropdownOpen = false">
        <div x-show="phase === 'form'" x-cloak class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">


            <div class="space-y-5">
                <div class="section-card p-2">
                    <div class="grid grid-cols-2  gap-1.5 rounded-lg bg-secondary/75 p-1">
                        <button type="button"
                            class="flex items-center border justify-center gap-2 rounded-md px-3 py-2.5 text-sm font-medium transition-base"
                            :class="sourceMode === 'repository' ? 'border-primary/50 brand-soft-bg shadow-soft' : 'border-transparent section-card hover:border-primary/30 hover:bg-secondary/40'"
                            @click="setSourceMode('repository')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-git-branch h-4 w-4">
                                <line x1="6" x2="6" y1="3" y2="15"></line>
                                <circle cx="18" cy="6" r="3"></circle>
                                <circle cx="6" cy="18" r="3"></circle>
                                <path d="M18 9a9 9 0 0 1-9 9"></path>
                            </svg>
                            Registered repository
                        </button>
                        <button type="button"
                            class="flex items-center border justify-center gap-2 rounded-md px-3 py-2.5 text-sm font-medium transition-base"
                            :class="sourceMode === 'gitless' ? 'border-primary/50 brand-soft-bg shadow-soft' : 'border-transparent section-card hover:border-primary/30 hover:bg-secondary/40'"
                            @click="setSourceMode('gitless')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-folder-open h-4 w-4">
                                <path
                                    d="m6 14 1.5-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.54 6a2 2 0 0 1-1.95 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2">
                                </path>
                            </svg>
                            Gitless folders
                            <span
                                class="ml-1 rounded-full brand-soft-bg px-1.5 py-0.5 text-[10px] font-semibold text-primary">One-time</span>
                        </button>
                    </div>
                    <p class="mt-2 px-2 pb-1 text-[11px] text-muted-foreground"
                        x-text="sourceMode === 'gitless' ? 'Drag & drop two project folders - no git history needed. Great for one-off comparisons.' : &quot;Use a connected repository's branches or tags as base and target.&quot;">
                    </p>
                </div>

                <section class="section-card transition-all duration-300" x-show="sourceMode === 'repository'"
                    id="repository-section"
                    :class="repoSectionHighlighted ? 'ring-1 ring-primary/60 shadow-[0_0_0_4px_hsl(var(--primary)/0.14)] scale-[1.005] animate-section-flash' : ''">
                    <div class="mb-5 flex items-start gap-3">
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg brand-soft-bg text-sm font-semibold text-primary">
                            1</div>
                        <div>
                            <h2 class="text-base font-semibold tracking-tight">Repository</h2>
                            <p class="mt-0.5 text-xs text-muted-foreground">Choose where this package comes from.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto]" x-show="repositories.length > 0">
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none mb-2">Repository</label>
                            <div class="relative" x-data="{ repoDropdownOpen: false }"
                                @keydown.escape.window="repoDropdownOpen = false">
                                <button type="button" role="combobox" :aria-expanded="repoDropdownOpen"
                                    aria-autocomplete="none" :data-state="repoDropdownOpen ? 'open' : 'closed'"
                                    :disabled="repositories.length === 0" @click="repoDropdownOpen = !repoDropdownOpen"
                                    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1">
                                    <span style="pointer-events: none;">
                                        <template x-if="!selectedRepositoryOption">
                                            <span class="text-muted-foreground">Choose repository</span>
                                        </template>
                                        <template x-if="selectedRepositoryOption">
                                            <span class="flex items-center gap-2">
                                                <span x-html="repoDropdownIcon(selectedRepositoryOption.provider)"></span>
                                                <span x-text="selectedRepositoryOption.label"></span>
                                            </span>
                                        </template>
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-chevron-down h-4 w-4 opacity-50"
                                        aria-hidden="true">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                </button>

                                <div x-show="repoDropdownOpen" x-cloak @click.outside="repoDropdownOpen = false"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute left-0 z-50 mt-1 w-full rounded-md border border-border bg-popover text-popover-foreground shadow-md outline-none"
                                    role="listbox">
                                    <div class="p-1 overflow-auto scrollbar-thin max-h-60">
                                        <template x-for="repo in repositories" :key="repo.id">
                                            <div role="option" :aria-selected="selectedRepository === repo.id"
                                                :data-state="selectedRepository === repo.id ? 'checked' : 'unchecked'"
                                                tabindex="-1"
                                                class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground"
                                                @click="selectedRepository = repo.id; repoDropdownOpen = false">
                                                <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                                                    <template x-if="selectedRepository === repo.id">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                            class="lucide lucide-check h-4 w-4">
                                                            <path d="M20 6 9 17l-5-5"></path>
                                                        </svg>
                                                    </template>
                                                </span>
                                                <span class="flex items-center gap-2">
                                                    <span x-html="repoDropdownIcon(repo.provider)"></span>
                                                    <span x-text="repo.label"></span>
                                                </span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <a href="{{ route('repositories') }}"
                            class="inline-flex h-10 items-center justify-center self-end rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                            Manage repositories
                        </a>
                    </div>

                    <template x-if="repositories.length === 0">
                        <a href="{{ route('repositories') }}">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info h-4 w-4">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" x2="12" y1="16" y2="12"></line>
                                    <line x1="12" x2="12.01" y1="8" y2="8"></line>
                                </svg>
                                <p class="text-muted-foreground">
                                    No repository found for your account
                                </p>
                            </div>
                            <div
                                class="mt-4 rounded-md border border-amber-200 bg-amber-500/10 hover:bg-amber-500/18 px-4 py-3 text-sm text-amber-600 flex justify-between items-center">
                                <span>
                                    Register and connect a repository or ask the owner to invite you as a Maintainer or Package Creator.
                                </span>
                                <span class="underline cursor-pointer">
                                    Manage repositories 
                                </span>
                            </div>
                        </a>
                    </template>

                    <div class="flex flex-wrap items-center gap-2 mt-4 text-xs text-muted-foreground"
                        x-show="selectedRepository">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-secondary/60 px-2 py-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="h-3.5 w-3.5">
                                <path
                                    d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4">
                                </path>
                                <path d="M9 18c-4.51 2-5-2-7-2"></path>
                            </svg>
                            <span x-text="selectedRepositoryProviderLabel"></span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-secondary/60 px-2 py-1"
                            x-text="selectedRepositoryLabel"></span>
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-secondary/60 px-2 py-1"
                            x-text="selectedRepositoryRoleLabel"></span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 border border-success/30 text-success bg-success/10">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>Connected
                        </span>
                    </div>
                </section>

                <section class="section-card" x-show="sourceMode === 'gitless'" x-cloak>
                    <div class="flex items-start gap-3 mb-5">
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg brand-soft-bg text-sm font-semibold text-primary">
                            1</div>
                        <div>
                            <h2 class="text-base font-semibold tracking-tight">Project folders</h2>
                            <p class="text-xs text-muted-foreground mt-0.5">Drag &amp; drop the base and target folders.
                                We'll diff them locally - no git required.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] gap-3 items-stretch">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <label for="gitless-base-folder"
                                    class="peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-xs font-medium">Base
                                    folder</label>
                                <span class="text-[10px] text-muted-foreground">Older / current version</span>
                            </div>
                            <label for="gitless-base-folder"
                                class="flex min-h-[140px] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-6 text-center transition-base"
                                :class="gitless.baseDropActive ? 'border-primary/60 bg-secondary/50' : 'border-border/70 bg-secondary/30 hover:border-primary/40 hover:bg-secondary/50'"
                                @dragenter.prevent="gitless.baseDropActive = true"
                                @dragover.prevent="gitless.baseDropActive = true"
                                @dragleave.self.prevent="gitless.baseDropActive = false"
                                @drop.prevent="handleGitlessDrop($event, 'base')">
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-lg bg-running/10 text-running transition-base">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-upload h-5 w-5">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" x2="12" y1="3" y2="15"></line>
                                    </svg>
                                </div>
                                <div class="max-w-full truncate text-sm font-medium"
                                    x-text="gitless.baseArchive ? gitless.baseArchive.name : 'Drop base folder or .zip'">
                                </div>
                                <div class="text-[11px] text-muted-foreground">or click to browse</div>
                                <input id="gitless-base-folder" type="file" class="hidden" multiple webkitdirectory
                                    directory @change="handleGitlessFolderSelection($event, 'base')">
                            </label>
                        </div>

                        <div class="hidden md:flex items-center justify-center">
                            <div class="h-9 w-9 rounded-full brand-soft-bg flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-arrow-right h-4 w-4 text-primary">
                                    <path d="M5 12h14"></path>
                                    <path d="m12 5 7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <label for="gitless-head-folder"
                                    class="peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-xs font-medium">Target
                                    folder</label>
                                <span class="text-[10px] text-muted-foreground">Newer version to ship</span>
                            </div>
                            <label for="gitless-head-folder"
                                class="flex min-h-[140px] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-6 text-center transition-base"
                                :class="gitless.headDropActive ? 'border-primary/60 bg-secondary/50' : 'border-border/70 bg-secondary/30 hover:border-primary/40 hover:bg-secondary/50'"
                                @dragenter.prevent="gitless.headDropActive = true"
                                @dragover.prevent="gitless.headDropActive = true"
                                @dragleave.self.prevent="gitless.headDropActive = false"
                                @drop.prevent="handleGitlessDrop($event, 'head')">
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-lg bg-success/10 text-success transition-base">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-upload h-5 w-5">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" x2="12" y1="3" y2="15"></line>
                                    </svg>
                                </div>
                                <div class="max-w-full truncate text-sm font-medium"
                                    x-text="gitless.headArchive ? gitless.headArchive.name : 'Drop target folder or .zip'">
                                </div>
                                <div class="text-[11px] text-muted-foreground">or click to browse</div>
                                <input id="gitless-head-folder" type="file" class="hidden" multiple webkitdirectory
                                    directory @change="handleGitlessFolderSelection($event, 'head')">
                            </label>
                        </div>
                    </div>

                    <div x-show="gitless.zipLoading" x-cloak class="mt-4 text-sm text-muted-foreground"
                        x-text="gitless.zipProgress"></div>

                    <div x-show="gitless.error" x-cloak
                        class="mt-4 rounded-md border border-failed/30 bg-failed/10 px-3 py-2 text-sm text-failed"
                        x-text="gitless.error"></div>
                </section>

                <section class="section-card" x-show="sourceMode === 'repository'">
                    <div class="flex items-start gap-3 mb-5">
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg brand-soft-bg text-sm font-semibold text-primary">
                            2</div>
                        <div>
                            <h2 class="text-base font-semibold tracking-tight">Version Selection</h2>
                            <p class="text-xs text-muted-foreground mt-0.5">Pick a base and target. We'll detect changes
                                immediately.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] gap-3 items-end">
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none">Base version</label>
                            <div class="relative">
                                <button
                                    class="inline-flex items-center gap-2 whitespace-nowrap rounded-md text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 w-full justify-between font-normal"
                                    type="button" role="combobox"
                                    :aria-expanded="baseVersionDropdownOpen ? 'true' : 'false'" aria-haspopup="dialog"
                                    aria-controls="base-version-dropdown"
                                    :data-state="baseVersionDropdownOpen ? 'open' : 'closed'"
                                    :class="(isLoadingVersions || allRepoVersions.length === 0) ? 'opacity-50 cursor-not-allowed' : ''"
                                    @click="allRepoVersions.length === 0 ? highlightRepoSection() : toggleVersionDropdown('base')">
                                    <span class="flex items-center gap-2 truncate">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="lucide lucide-git-branch h-3.5 w-3.5 text-muted-foreground">
                                            <line x1="6" x2="6" y1="3" y2="15"></line>
                                            <circle cx="18" cy="6" r="3"></circle>
                                            <circle cx="6" cy="18" r="3"></circle>
                                            <path d="M18 9a9 9 0 0 1-9 9"></path>
                                        </svg>
                                        <span class="truncate" :class="selectedBaseLabel ? '' : 'text-muted-foreground'"
                                            x-text="selectedBaseLabel || 'Select base'"></span>
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="lucide lucide-chevrons-up-down h-4 w-4 opacity-50 shrink-0">
                                        <path d="m7 15 5 5 5-5"></path>
                                        <path d="m7 9 5-5 5 5"></path>
                                    </svg>
                                </button>

                                <div id="base-version-dropdown" x-show="baseVersionDropdownOpen" x-cloak
                                    @click.outside="baseVersionDropdownOpen = false"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute left-0 z-50 mt-1 w-full rounded-md border border-border bg-popover p-0 text-popover-foreground shadow-md outline-none"
                                    tabindex="-1" data-side="bottom" data-align="start"
                                    :data-state="baseVersionDropdownOpen ? 'open' : 'closed'" role="dialog">
                                    <div tabindex="-1"
                                        class="flex h-full w-full flex-col overflow-hidden rounded-md bg-popover text-popover-foreground"
                                        cmdk-root="">
                                        <label for="base-version-search" class="sr-only" cmdk-label="">Search base
                                            versions</label>
                                        <div class="flex items-center border-b border-border px-3" cmdk-input-wrapper="">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round"
                                                class="lucide lucide-search mr-2 h-4 w-4 shrink-0 opacity-50">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <path d="m21 21-4.3-4.3"></path>
                                            </svg>
                                            <input id="base-version-search" x-ref="baseVersionSearchInput"
                                                class="flex h-11 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                                placeholder="Search versions..." x-model="baseVersionSearch" cmdk-input=""
                                                autocomplete="off" autocorrect="off" spellcheck="false"
                                                aria-autocomplete="list" role="combobox" aria-expanded="true"
                                                aria-controls="base-version-list" type="text">
                                        </div>

                                        <div id="base-version-list"
                                            class="max-h-[300px] overflow-y-auto overflow-x-hidden scrollbar-thin"
                                            cmdk-list="" role="listbox" tabindex="-1" aria-label="Suggestions">
                                            <div cmdk-list-sizer="">
                                                <template x-for="group in filteredVersionGroups('base')"
                                                    :key="'base-group-' + group.value">
                                                    <div class="overflow-hidden p-1 text-foreground [&_[cmdk-group-heading]]:px-2 [&_[cmdk-group-heading]]:py-1.5 [&_[cmdk-group-heading]]:text-xs [&_[cmdk-group-heading]]:font-medium [&_[cmdk-group-heading]]:text-muted-foreground"
                                                        cmdk-group="" role="presentation" :data-value="group.value">
                                                        <div cmdk-group-heading="" aria-hidden="true" x-text="group.label">
                                                        </div>
                                                        <div cmdk-group-items="" role="group">
                                                            <template x-for="version in group.items"
                                                                :key="'base-dropdown-' + version.unique_key">
                                                                <button type="button"
                                                                    class="relative flex w-full cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground data-[selected=true]:bg-accent data-[selected=true]:text-accent-foreground"
                                                                    role="option"
                                                                    :aria-selected="form.base === version.unique_key"
                                                                    :data-selected="form.base === version.unique_key"
                                                                    :data-value="version.name" cmdk-item=""
                                                                    @click="selectVersion('base', version.unique_key)">
                                                                    <svg x-show="version.type === 'branch'"
                                                                        xmlns="http://www.w3.org/2000/svg" width="24"
                                                                        height="24" viewBox="0 0 24 24" fill="none"
                                                                        stroke="currentColor" stroke-width="2"
                                                                        stroke-linecap="round" stroke-linejoin="round"
                                                                        class="lucide lucide-git-branch mr-2 h-3.5 w-3.5 text-muted-foreground">
                                                                        <line x1="6" x2="6" y1="3" y2="15"></line>
                                                                        <circle cx="18" cy="6" r="3"></circle>
                                                                        <circle cx="6" cy="18" r="3"></circle>
                                                                        <path d="M18 9a9 9 0 0 1-9 9"></path>
                                                                    </svg>
                                                                    <svg x-show="version.type !== 'branch'"
                                                                        xmlns="http://www.w3.org/2000/svg" width="24"
                                                                        height="24" viewBox="0 0 24 24" fill="none"
                                                                        stroke="currentColor" stroke-width="2"
                                                                        stroke-linecap="round" stroke-linejoin="round"
                                                                        class="lucide lucide-tag mr-2 h-3.5 w-3.5 text-muted-foreground">
                                                                        <path
                                                                            d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z">
                                                                        </path>
                                                                        <circle cx="7.5" cy="7.5" r=".5"
                                                                            fill="currentColor"></circle>
                                                                    </svg>
                                                                    <span class="flex-1 truncate text-left"
                                                                        x-text="version.name"></span>
                                                                    <svg x-show="form.base === version.unique_key"
                                                                        xmlns="http://www.w3.org/2000/svg" width="24"
                                                                        height="24" viewBox="0 0 24 24" fill="none"
                                                                        stroke="currentColor" stroke-width="2"
                                                                        stroke-linecap="round" stroke-linejoin="round"
                                                                        class="lucide lucide-check h-4 w-4">
                                                                        <path d="M20 6 9 17l-5-5"></path>
                                                                    </svg>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                <template x-if="filteredVersionGroups('base').length === 0">
                                                    <div class="px-3 py-6 text-center text-sm text-muted-foreground">No
                                                        versions found.</div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[11px] text-muted-foreground">Suggested: last deployed version</p>
                        </div>

                        <div class="hidden md:flex items-center justify-center pb-8">
                            <div class="h-9 w-9 rounded-full brand-soft-bg flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-arrow-right h-4 w-4 text-primary">
                                    <path d="M5 12h14"></path>
                                    <path d="m12 5 7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none">Target version</label>
                            <div class="relative">
                                <button
                                    class="inline-flex items-center gap-2 whitespace-nowrap rounded-md text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 w-full justify-between font-normal"
                                    type="button" role="combobox"
                                    :aria-expanded="headVersionDropdownOpen ? 'true' : 'false'" aria-haspopup="dialog"
                                    aria-controls="head-version-dropdown"
                                    :data-state="headVersionDropdownOpen ? 'open' : 'closed'"
                                    :class="(isLoadingVersions || allRepoVersions.length === 0) ? 'opacity-50 cursor-not-allowed' : ''"
                                    @click="allRepoVersions.length === 0 ? highlightRepoSection() : toggleVersionDropdown('head')">
                                    <span class="flex items-center gap-2 truncate">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="lucide lucide-git-branch h-3.5 w-3.5 text-muted-foreground">
                                            <line x1="6" x2="6" y1="3" y2="15"></line>
                                            <circle cx="18" cy="6" r="3"></circle>
                                            <circle cx="6" cy="18" r="3"></circle>
                                            <path d="M18 9a9 9 0 0 1-9 9"></path>
                                        </svg>
                                        <span class="truncate" :class="selectedHeadLabel ? '' : 'text-muted-foreground'"
                                            x-text="selectedHeadLabel || 'Select target'"></span>
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="lucide lucide-chevrons-up-down h-4 w-4 opacity-50 shrink-0">
                                        <path d="m7 15 5 5 5-5"></path>
                                        <path d="m7 9 5-5 5 5"></path>
                                    </svg>
                                </button>

                                <div id="head-version-dropdown" x-show="headVersionDropdownOpen" x-cloak
                                    @click.outside="headVersionDropdownOpen = false"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute left-0 z-50 mt-1 w-full rounded-md border border-border bg-popover p-0 text-popover-foreground shadow-md outline-none"
                                    tabindex="-1" data-side="bottom" data-align="start"
                                    :data-state="headVersionDropdownOpen ? 'open' : 'closed'" role="dialog">
                                    <div tabindex="-1"
                                        class="flex h-full w-full flex-col overflow-hidden rounded-md bg-popover text-popover-foreground"
                                        cmdk-root="">
                                        <label for="head-version-search" class="sr-only" cmdk-label="">Search target
                                            versions</label>
                                        <div class="flex items-center border-b border-border px-3" cmdk-input-wrapper="">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round"
                                                class="lucide lucide-search mr-2 h-4 w-4 shrink-0 opacity-50">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <path d="m21 21-4.3-4.3"></path>
                                            </svg>
                                            <input id="head-version-search" x-ref="headVersionSearchInput"
                                                class="flex h-11 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                                placeholder="Search versions..." x-model="headVersionSearch" cmdk-input=""
                                                autocomplete="off" autocorrect="off" spellcheck="false"
                                                aria-autocomplete="list" role="combobox" aria-expanded="true"
                                                aria-controls="head-version-list" type="text">
                                        </div>

                                        <div id="head-version-list"
                                            class="max-h-[300px] overflow-y-auto overflow-x-hidden scrollbar-thin"
                                            cmdk-list="" role="listbox" tabindex="-1" aria-label="Suggestions">
                                            <div cmdk-list-sizer="">
                                                <template x-for="group in filteredVersionGroups('head')"
                                                    :key="'head-group-' + group.value">
                                                    <div class="overflow-hidden p-1 text-foreground [&_[cmdk-group-heading]]:px-2 [&_[cmdk-group-heading]]:py-1.5 [&_[cmdk-group-heading]]:text-xs [&_[cmdk-group-heading]]:font-medium [&_[cmdk-group-heading]]:text-muted-foreground"
                                                        cmdk-group="" role="presentation" :data-value="group.value">
                                                        <div cmdk-group-heading="" aria-hidden="true" x-text="group.label">
                                                        </div>
                                                        <div cmdk-group-items="" role="group">
                                                            <template x-for="version in group.items"
                                                                :key="'head-dropdown-' + version.unique_key">
                                                                <button type="button"
                                                                    class="relative flex w-full cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground data-[selected=true]:bg-accent data-[selected=true]:text-accent-foreground"
                                                                    role="option"
                                                                    :aria-selected="form.head === version.unique_key"
                                                                    :data-selected="form.head === version.unique_key"
                                                                    :data-value="version.name" cmdk-item=""
                                                                    @click="selectVersion('head', version.unique_key)">
                                                                    <svg x-show="version.type === 'branch'"
                                                                        xmlns="http://www.w3.org/2000/svg" width="24"
                                                                        height="24" viewBox="0 0 24 24" fill="none"
                                                                        stroke="currentColor" stroke-width="2"
                                                                        stroke-linecap="round" stroke-linejoin="round"
                                                                        class="lucide lucide-git-branch mr-2 h-3.5 w-3.5 text-muted-foreground">
                                                                        <line x1="6" x2="6" y1="3" y2="15"></line>
                                                                        <circle cx="18" cy="6" r="3"></circle>
                                                                        <circle cx="6" cy="18" r="3"></circle>
                                                                        <path d="M18 9a9 9 0 0 1-9 9"></path>
                                                                    </svg>
                                                                    <svg x-show="version.type !== 'branch'"
                                                                        xmlns="http://www.w3.org/2000/svg" width="24"
                                                                        height="24" viewBox="0 0 24 24" fill="none"
                                                                        stroke="currentColor" stroke-width="2"
                                                                        stroke-linecap="round" stroke-linejoin="round"
                                                                        class="lucide lucide-tag mr-2 h-3.5 w-3.5 text-muted-foreground">
                                                                        <path
                                                                            d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z">
                                                                        </path>
                                                                        <circle cx="7.5" cy="7.5" r=".5"
                                                                            fill="currentColor"></circle>
                                                                    </svg>
                                                                    <span class="flex-1 truncate text-left"
                                                                        x-text="version.name"></span>
                                                                    <svg x-show="form.head === version.unique_key"
                                                                        xmlns="http://www.w3.org/2000/svg" width="24"
                                                                        height="24" viewBox="0 0 24 24" fill="none"
                                                                        stroke="currentColor" stroke-width="2"
                                                                        stroke-linecap="round" stroke-linejoin="round"
                                                                        class="lucide lucide-check h-4 w-4">
                                                                        <path d="M20 6 9 17l-5-5"></path>
                                                                    </svg>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                <template x-if="filteredVersionGroups('head').length === 0">
                                                    <div class="px-3 py-6 text-center text-sm text-muted-foreground">No
                                                        versions found.</div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[11px] text-muted-foreground">Suggested: latest tag</p>
                        </div>
                    </div>

                    <template x-if="isLoadingVersions">
                        <div
                            class="mt-4 animate-fade-in overflow-hidden rounded-xl border border-border/70 bg-secondary/30 shadow-soft">
                            <div class="flex items-center gap-3 px-4 py-3">
                                <div
                                    class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full brand-soft-bg text-primary">
                                    <span
                                        class="absolute inset-0 rounded-full bg-primary/10 motion-safe:animate-pulse"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2"
                                        class="relative h-4 w-4 motion-safe:animate-spin">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21 12a9 9 0 1 1-6.219-8.56" />
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 text-sm font-medium text-foreground">
                                        <span>Loading versions</span>
                                    </div>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        Fetching branches, tags, and releases in the background...
                                    </p>
                                </div>
                            </div>

                            <div class="h-1 overflow-hidden bg-border/35">
                                <div class="h-full brand-gradient-bg opacity-80 motion-safe:animate-pulse"></div>
                            </div>
                        </div>
                    </template>

                    <template x-if="identicalVersions">
                        <div class="mt-4 rounded-md border border-failed/30 bg-failed/10 px-3 py-2 text-sm text-failed">
                            Base and target cannot be identical. Choose two different versions.
                        </div>
                    </template>

                    <template x-if="duplicatePackage">
                        <div
                            class="mt-4 rounded-md border border-amber-200 bg-amber-500/10 px-3 py-2 text-sm text-amber-500 flex items-center justify-between gap-3">
                            <span>A package with this repository, environment, base, and target already exists.</span>
                            <a href="{{ route('packages.index') }}"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-amber-400/50 bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-500 transition-colors hover:bg-amber-500/20 whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14" />
                                    <path d="m7.5 4.27 9 5.15" />
                                    <polyline points="3.29 7 12 12 20.71 7" />
                                    <line x1="12" x2="12" y1="22" y2="12" />
                                </svg>
                                View Packages
                            </a>
                        </div>
                    </template>

                    <div class="mt-5 animate-fade-in" x-show="form.base && form.head && !identicalVersions">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-sparkles h-4 w-4 text-primary">
                                    <path
                                        d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z">
                                    </path>
                                    <path d="M20 3v4"></path>
                                    <path d="M22 5h-4"></path>
                                    <path d="M4 17v2"></path>
                                    <path d="M5 18H3"></path>
                                </svg>
                                Detected changes
                            </div>
                            <span class="text-xs text-muted-foreground" x-text="diffPreviewStatusLabel"></span>
                        </div>
                        <p x-show="diffPreviewError" x-cloak class="mb-3 text-xs text-amber-500" x-text="diffPreviewError">
                        </p>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-xl border p-3.5 border-success/25 bg-success/8">
                                <div class="flex items-center gap-2 text-xs font-medium text-success"><svg
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-file-plus2 h-4 w-4">
                                        <path d="M4 22h14a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v4"></path>
                                        <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                        <path d="M3 15h6"></path>
                                        <path d="M6 12v6"></path>
                                    </svg> Added</div>
                                <div class="mt-1.5 text-2xl font-semibold tabular-nums"
                                    :class="isLoadingDiffPreview ? 'animate-pulse' : ''" x-text="diffPreview.added ?? '-'">
                                </div>
                            </div>
                            <div class="rounded-xl border p-3.5 border-running/25 bg-running/8">
                                <div class="flex items-center gap-2 text-xs font-medium text-running"><svg
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-file-pen-line h-4 w-4">
                                        <path
                                            d="m18 5-2.414-2.414A2 2 0 0 0 14.172 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2">
                                        </path>
                                        <path
                                            d="M21.378 12.626a1 1 0 0 0-3.004-3.004l-4.01 4.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z">
                                        </path>
                                        <path d="M8 18h1"></path>
                                    </svg> Modified</div>
                                <div class="mt-1.5 text-2xl font-semibold tabular-nums"
                                    :class="isLoadingDiffPreview ? 'animate-pulse' : ''"
                                    x-text="diffPreview.modified ?? '-'"></div>
                            </div>
                            <div class="rounded-xl border p-3.5 border-failed/25 bg-failed/8">
                                <div class="flex items-center gap-2 text-xs font-medium text-failed"><svg
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-file-minus h-4 w-4">
                                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                        <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                        <path d="M9 15h6"></path>
                                    </svg> Deleted</div>
                                <div class="mt-1.5 text-2xl font-semibold tabular-nums"
                                    :class="isLoadingDiffPreview ? 'animate-pulse' : ''"
                                    x-text="diffPreview.deleted ?? '-'"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="section-card">
                    <div class="flex items-start gap-3 mb-5">
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg brand-soft-bg text-sm font-semibold text-primary">
                            <span x-text="sourceMode === 'gitless' ? '2' : '3'"></span>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold tracking-tight">Environment & Package Settings</h2>
                            <p class="text-xs text-muted-foreground mt-0.5">Where will this package be applied?</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <template x-for="env in ['DEV', 'QA', 'PROD']" :key="env">
                            <button type="button" class="rounded-xl border p-4 text-left transition-base"
                                :class="form.environment === env ? 'border-primary/50 brand-soft-bg shadow-soft' : 'border-border hover:border-primary/30 hover:bg-secondary/40'"
                                @click="form.environment = env; confirmedProd = false; updatePackageName(); checkDuplicate();">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[10px] font-semibold tracking-wider"
                                        :class="env === 'DEV' ? 'bg-running/10 text-running border-running/30' : env === 'QA' ? 'bg-queued/10 text-queued border-queued/30' : 'bg-failed/10 text-failed border-failed/30'">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span><span x-text="env"></span>
                                    </span>
                                    <svg x-show="form.environment === env" xmlns="http://www.w3.org/2000/svg" width="24"
                                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-circle-check h-4 w-4 text-primary">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <path d="m9 12 2 2 4-4"></path>
                                    </svg>
                                </div>
                                <div class="text-sm font-medium"
                                    x-text="env === 'DEV' ? 'Development' : env === 'QA' ? 'Quality assurance' : 'Production'">
                                </div>
                                <div class="text-[11px] text-muted-foreground mt-1"
                                    x-text="env === 'DEV' ? 'Fast deploy, no confirmation' : env === 'QA' ? 'Moderate confirmation' : 'Confirmation required'">
                                </div>
                            </button>
                        </template>
                    </div>

                    <div class="mt-5 space-y-2">
                        <label class="text-sm font-medium leading-none">Package name</label>
                        <input type="text"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-xs font-mono ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm"
                            x-model="form.customName"
                            :placeholder="autoPackageName || 'Auto-generated when versions are picked'">
                        <p class="text-[11px] text-muted-foreground">Leave empty to use the auto-generated name.</p>
                    </div>

                    <template x-if="form.environment === 'PROD'">
                        <div class="mt-5 rounded-md border border-failed/30 bg-failed/10 p-4">
                            <p class="text-sm font-semibold text-failed">Production safety check</p>
                            <p class="mt-1 text-xs text-muted-foreground">Review the summary, then confirm to enable
                                generation.</p>
                            <label class="mt-3 flex items-center gap-2 text-sm text-foreground">
                                <input type="checkbox" class="rounded border-input text-primary focus:ring-primary"
                                    x-model="confirmedProd">
                                I understand this package targets production.
                            </label>
                        </div>
                    </template>
                    <!--
                    <div class="mt-5">
                        <button type="button"
                            class="flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-base"
                            @click="showAdvanced = !showAdvanced">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-chevron-down h-4 w-4 transition-transform"
                                :class="showAdvanced ? 'rotate-180' : ''">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                            Advanced settings
                        </button>

                        <div x-show="showAdvanced"
                            class="mt-4 animate-fade-in rounded-md border border-border bg-secondary/30 p-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium leading-none">Output format</label>
                                    <select
                                        class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        x-model="form.format">
                                        <option value=".zip">ZIP</option>
                                        <option value=".tar.gz">TAR.GZ</option>
                                        <option value="both">Both</option>
                                    </select>
                                </div>
                                <label
                                    class="flex items-center justify-between gap-3 rounded-md border border-border bg-background px-4 py-3">
                                    <span>
                                        <span class="block text-sm font-medium leading-none">Generate rollback
                                            package</span>
                                        <span class="block text-[11px] text-muted-foreground mt-1.5">Your backend currently
                                            generates update and rollback together.</span>
                                    </span>
                                    <input type="checkbox" class="rounded border-input text-primary focus:ring-primary"
                                        x-model="form.rollback" checked>
                                </label>
                            </div>
                        </div>
                    </div>
                    -->
                </section>
            </div>

            <aside class="space-y-5 xl:sticky xl:top-20 xl:self-start">
                <section class="section-card">
                    <div class="mb-4 flex items-center gap-2">
                        <div class="h-2.5 w-2.5 rounded-full brand-gradient-bg"></div>
                        <h3 class="text-sm font-semibold tracking-tight">Live summary</h3>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3"><span class="text-xs text-muted-foreground"
                                x-text="sourceMode === 'gitless' ? 'Source' : 'Repository'"></span><span
                                class="max-w-[60%] truncate font-medium" x-text="selectedRepositoryLabel || '-'"></span>
                        </div>
                        <div class="flex items-center justify-between gap-3"><span
                                class="text-xs text-muted-foreground">Base</span><span
                                class="max-w-[60%] truncate font-mono text-xs font-medium"
                                x-text="selectedBaseLabel || '—'"></span></div>
                        <div class="flex items-center justify-between gap-3"><span
                                class="text-xs text-muted-foreground">Target</span><span
                                class="max-w-[60%] truncate font-mono text-xs font-medium"
                                x-text="selectedHeadLabel || '—'"></span></div>
                        <div class="flex items-center justify-between gap-3"><span
                                class="text-xs text-muted-foreground">Environment</span><span
                                class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[10px] font-semibold tracking-wider"
                                :class="form.environment === 'DEV' ? 'bg-running/10 text-running border-running/30' : form.environment === 'QA' ? 'bg-queued/10 text-queued border-queued/30' : 'bg-failed/10 text-failed border-failed/30'"><span
                                    class="h-1.5 w-1.5 rounded-full bg-current"></span><span
                                    x-text="form.environment"></span></span></div>
                        <div class="flex items-center justify-between gap-3"><span
                                class="text-xs text-muted-foreground">Rollback</span><span class="font-medium"
                                x-text="form.rollback ? 'Included' : 'Skipped'"></span></div>
                        <div class="flex items-center justify-between gap-3"><span
                                class="text-xs text-muted-foreground">Format</span><span class="font-medium"
                                x-text="form.format === 'both' ? 'Both' : form.format.toUpperCase()"></span></div>
                    </div>

                    <div class="my-4 h-px bg-border/50"></div>

                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Package name</p>
                        <p class="mt-2 break-all font-mono text-xs leading-relaxed" x-text="finalPackageName || '—'"></p>
                    </div>

                    <button type="button"
                        class="mt-5 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md px-8 w-full h-12 text-base font-semibold brand-gradient-bg shadow-soft transition-colors hover:brightness-105 active:brightness-95 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"
                        :disabled="!canGenerate || isQueuing" @click="startPackaging()">
                        <span x-show="isQueuing" class="animate-spin">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-loader-2 h-4 w-4">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                            </svg>
                        </span>
                        <span x-show="!isQueuing">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-zap h-4 w-4">
                                <path
                                    d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
                                </path>
                            </svg>
                        </span>
                        <span x-text="isQueuing ? 'Queuing...' : 'Generate Package'"></span>
                    </button>

                    <p x-show="!canGenerate && form.environment === 'PROD'"
                        class="mt-2 text-center text-[10px] text-muted-foreground">
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

                    <button type="button"
                        class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground px-3 text-sm font-medium shadow-sm transition-colors"
                        @click="cancelJob()">
                        Cancel
                    </button>
                </div>

                <div class="mb-6">
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-medium" x-text="packagingMessage || activeStageLabel"></span>
                        <span class="font-mono text-muted-foreground" x-text="Math.floor(packagingProgress) + '%'"></span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-secondary">
                        <div class="h-full rounded-full bg-primary transition-all duration-500"
                            :style="`width:${packagingProgress}%`"></div>
                    </div>
                </div>

                <ol class="space-y-2.5">
                    <template x-for="stage in stages" :key="stage.key">
                        <li class="flex items-center gap-3 text-sm">
                            <span
                                class="flex h-5 w-5 items-center justify-center rounded-full border text-[10px] font-bold transition-colors"
                                :class="stage.value <= packagingProgress ? 'border-transparent bg-primary text-primary-foreground' : 'border-border text-muted-foreground'">✓</span>
                            <span :class="stage.value <= packagingProgress ? 'font-medium' : 'text-muted-foreground'"
                                x-text="stage.label"></span>
                        </li>
                    </template>
                </ol>
            </section>
        </div>

        <div x-show="phase === 'done'" x-cloak class="mx-auto max-w-3xl">
            <section class="section-card text-center">
                <div
                    class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl brand-soft-bg text-3xl text-primary shadow-soft">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-check h-8 w-8">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold tracking-tight">Package ready</h2>
                <p class="mt-2 break-all text-sm text-muted-foreground" x-text="finalPackageName"></p>

                <div class="mt-6 grid grid-cols-3 gap-3">
                    <div class="rounded-xl border border-border bg-secondary/20 p-4">
                        <p class="text-lg font-semibold tracking-tight" x-text="packagingResult?.zip_size || '—'"></p>
                        <p class="mt-1 text-xs text-muted-foreground">ZIP size</p>
                    </div>
                    <div class="rounded-xl border border-border bg-secondary/20 p-4">
                        <p class="text-lg font-semibold tracking-tight" x-text="packagingResult?.targz_size || '—'"></p>
                        <p class="mt-1 text-xs text-muted-foreground">TAR.GZ size</p>
                    </div>
                    <div class="rounded-xl border border-border bg-secondary/20 p-4">
                        <p class="text-lg font-semibold tracking-tight"
                            x-text="packagingResult?.summary?.total_changes ?? '—'"></p>
                        <p class="mt-1 text-xs text-muted-foreground">Changes</p>
                    </div>
                </div>

                <div class="mt-7 flex flex-col justify-center gap-2 sm:flex-row">
                    <button type="button"
                        class="inline-flex h-9 items-center justify-center rounded-md bg-primary text-primary-foreground px-4 text-sm font-medium shadow hover:bg-primary/90 transition-colors">Deploy
                        Package</button>
                    <button type="button" x-show="packagingResult?.zip_size"
                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md border border-running/30 bg-card px-2.5 text-xs font-semibold text-running transition-colors hover:bg-running/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        @click="downloadPackage('.zip')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-download h-3.5 w-3.5">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" x2="12" y1="15" y2="3"></line>
                        </svg>
                        zip
                    </button>
                    <button type="button" x-show="packagingResult?.targz_size"
                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md border border-running/30 bg-card px-2.5 text-xs font-semibold text-running transition-colors hover:bg-running/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        @click="downloadPackage('.tar.gz')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-download h-3.5 w-3.5">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" x2="12" y1="15" y2="3"></line>
                        </svg>
                        tar.gz
                    </button>
                    <button type="button"
                        class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground px-4 text-sm font-medium shadow-sm transition-colors"
                        @click="resetForm()">Create Another</button>
                </div>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-2" x-show="phase === 'form'">
            @includeIf('components.packaging-wizardV3.active-jobs-card')
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" defer></script>
    <script>
        function quickCreatePackage({
            repositories,
            queueUrl,
            gitlessQueueUrl,
            previewUrl,
            jobProgressBaseUrl,
            downloadUrl,
            csrfToken,
            completedPackages,
            dbQueuedPackages,
            selectedRepositoryId,
            repositoryVersionsBaseUrl,
        }) {
            repositories = repositories.map(repo => ({
                ...repo,
                id: String(repo.id),
            }));
            const initialRepositoryId = selectedRepositoryId ? String(selectedRepositoryId) : '';

            return {
                repositories,
                completedPackages,
                dbQueuedPackages,
                unifiedQueue: dbQueuedPackages.map(dbJob => ({
                    jobId: dbJob.id,
                    status: dbJob.status,
                    created_at: dbJob.created_at,
                    progress: dbJob.progress ?? null,
                    statusMessage: dbJob.message ?? '',
                    errorMessage: dbJob.error_message ?? '',
                    row: {
                        environment: dbJob.environment,
                        project_name: dbJob.project_name,
                        base_version: dbJob.base_version,
                        head_version: dbJob.head_version,
                        name: dbJob.package_name,
                    },
                })),
                queueUrl,
                gitlessQueueUrl,
                previewUrl,
                jobProgressBaseUrl,
                downloadUrl,
                csrfToken,
                repositoryVersionsBaseUrl,

                phase: 'form',
                sourceMode: 'repository',
                selectedRepository: initialRepositoryId,

                repoBranches: [],
                repoTags: [],
                repoReleases: [],
                isLoadingVersions: false,
                repoSectionHighlighted: false,
                baseVersionDropdownOpen: false,
                headVersionDropdownOpen: false,
                baseVersionSearch: '',
                headVersionSearch: '',
                gitless: {
                    baseArchive: null,
                    headArchive: null,
                    baseDropActive: false,
                    headDropActive: false,
                    zipLoading: false,
                    zipProgress: '',
                    error: '',
                },

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
                diffPreview: {
                    added: null,
                    deleted: null,
                    modified: null,
                    total: null,
                },
                diffPreviewAbortController: null,
                diffPreviewError: '',
                diffPreviewRequestId: 0,
                isLoadingDiffPreview: false,

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
                    this.$watch('selectedRepository', async () => {
                        this.resetVersionState();
                        if (this.sourceMode === 'repository' && this.selectedRepository) {
                            await this.fetchRepoVersions();
                        }
                    });

                    if (this.selectedRepository) {
                        this.fetchRepoVersions();
                    }
                },

                highlightRepoSection() {
                    const el = document.getElementById('repository-section');
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    this.repoSectionHighlighted = true;
                    setTimeout(() => {
                        this.repoSectionHighlighted = false;
                    }, 1500);
                },

                setSourceMode(mode) {
                    if (this.sourceMode === mode) {
                        return;
                    }

                    this.cancelDiffPreviewRequest();
                    this.resetDiffPreview();
                    this.sourceMode = mode;
                    this.baseVersionDropdownOpen = false;
                    this.headVersionDropdownOpen = false;
                    this.confirmedProd = false;
                    this.duplicatePackage = null;
                    this.packagingError = '';

                    if (mode === 'repository' && this.selectedRepository && this.allRepoVersions.length === 0) {
                        this.fetchRepoVersions();
                    }
                },

                get selectedRepositoryOption() {
                    return this.repositories.find(r => r.id === String(this.selectedRepository)) || null;
                },

                get selectedRepositoryLabel() {
                    if (this.sourceMode === 'gitless') {
                        return 'Gitless folders';
                    }

                    return this.selectedRepositoryOption?.label || '';
                },

                get sourceSummaryLabel() {
                    return this.sourceMode === 'gitless' ? 'Gitless folders' : this.selectedRepositoryLabel;
                },

                get packageProjectLabel() {
                    return this.sourceMode === 'gitless' ? 'Gitless folders' : this.selectedRepositoryLabel;
                },

                get selectedRepositoryProvider() {
                    return this.selectedRepositoryOption?.provider || '';
                },

                get selectedRepositoryProviderLabel() {
                    return this.selectedRepositoryOption?.providerLabel || '';
                },

                get selectedRepositoryRoleLabel() {
                    const role = this.selectedRepositoryOption?.role;
                    if (role === 'owner') return 'Owner credentials';
                    if (role === 'maintainer') return 'Maintainer';
                    if (role === 'creator') return 'Package Creator';

                    return 'Repository access';
                },

                repoDropdownIcon(provider) {
                    if (provider === 'github') {
                        return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-github h-3.5 w-3.5"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"></path><path d="M9 18c-4.51 2-5-2-7-2"></path></svg>`;
                    }
                    if (provider === 'gitlab') {
                        return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gitlab h-3.5 w-3.5"><path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"></path></svg>`;
                    }
                    if (provider === 'company-server') {
                        return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-server h-3.5 w-3.5"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect><rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect><line x1="6" x2="6.01" y1="6" y2="6"></line><line x1="6" x2="6.01" y1="18" y2="18"></line></svg>`;
                    }
                    return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hard-drive h-3.5 w-3.5"><line x1="22" x2="2" y1="12" y2="12"></line><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path><line x1="6" x2="6.01" y1="16" y2="16"></line><line x1="10" x2="10.01" y1="16" y2="16"></line></svg>`;
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

                filteredVersionGroups(field) {
                    const search = String(field === 'head' ? this.headVersionSearch : this.baseVersionSearch)
                        .trim()
                        .toLowerCase();

                    return [
                        { value: 'Tags', label: 'Tags', type: 'tag' },
                        { value: 'Branches', label: 'Branches', type: 'branch' },
                        { value: 'Releases', label: 'Releases', type: 'release' },
                    ].map(group => ({
                        ...group,
                        items: this.allRepoVersions.filter(version => {
                            if (version.type !== group.type) {
                                return false;
                            }

                            if (!search) {
                                return true;
                            }

                            return [
                                version.name,
                                version.ref,
                                version.typeLabel,
                            ].some(value => String(value || '').toLowerCase().includes(search));
                        }),
                    })).filter(group => group.items.length > 0);
                },

                get selectedBaseObj() {
                    return this.allRepoVersions.find(v => v.unique_key === this.form.base) || null;
                },

                get selectedHeadObj() {
                    return this.allRepoVersions.find(v => v.unique_key === this.form.head) || null;
                },

                get selectedBaseLabel() {
                    if (this.sourceMode === 'gitless') {
                        return this.gitless.baseArchive?.name || '';
                    }

                    return this.selectedBaseObj ? this.selectedBaseObj.name : '';
                },

                get selectedHeadLabel() {
                    if (this.sourceMode === 'gitless') {
                        return this.gitless.headArchive?.name || '';
                    }

                    return this.selectedHeadObj ? this.selectedHeadObj.name : '';
                },

                get baseRef() {
                    if (this.sourceMode === 'gitless') {
                        return this.gitless.baseArchive ? 'base-folder' : '';
                    }

                    return this.selectedBaseObj ? this.selectedBaseObj.ref : '';
                },

                get headRef() {
                    if (this.sourceMode === 'gitless') {
                        return this.gitless.headArchive ? 'target-folder' : '';
                    }

                    return this.selectedHeadObj ? this.selectedHeadObj.ref : '';
                },

                get identicalVersions() {
                    return this.form.base && this.form.head && this.form.base === this.form.head;
                },

                get canPreviewDiff() {
                    return this.sourceMode === 'repository' &&
                        this.selectedRepository &&
                        this.baseRef &&
                        this.headRef &&
                        !this.identicalVersions;
                },

                get diffPreviewStatusLabel() {
                    if (!this.canPreviewDiff) {
                        return '';
                    }

                    if (this.isLoadingDiffPreview) {
                        return 'Calculating...';
                    }

                    if (this.diffPreviewError) {
                        return 'Preview unavailable';
                    }

                    if (this.diffPreview.total === null) {
                        return 'Choose two versions';
                    }

                    if (this.diffPreview.total === 0) {
                        return 'No file changes';
                    }

                    return `${this.diffPreview.total} change${this.diffPreview.total === 1 ? '' : 's'}`;
                },

                get autoPackageName() {
                    const safe = value => String(value).replace(/[^\w.\-]+/g, '_');
                    const now = new Date();
                    const pad = n => String(n).padStart(2, '0');
                    const ts = `${String(now.getFullYear()).slice(-2)}${pad(now.getMonth() + 1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}`;

                    if (this.sourceMode === 'gitless') {
                        if (!this.form.environment || !this.gitless.baseArchive || !this.gitless.headArchive) return '';

                        const baseName = this.gitlessPackageNamePart(this.gitless.baseArchive.name);
                        const headName = this.gitlessPackageNamePart(this.gitless.headArchive.name);

                        if (baseName === headName) {
                            return `${this.form.environment}-${baseName}-${ts}`;
                        }

                        return `${this.form.environment}-${baseName}-to-${headName}-${ts}`;
                    }

                    if (!this.packageProjectLabel || !this.baseRef || !this.headRef) return '';

                    return `${this.form.environment}-${safe(this.packageProjectLabel)}-${safe(this.baseRef)}-to-${safe(this.headRef)}-${ts}`;
                },

                gitlessPackageNamePart(fileName) {
                    return String(fileName || '')
                        .replace(/\.zip$/i, '')
                        .replace(/[^\w.\-]+/g, '_')
                        .replace(/^_+|_+$/g, '') || 'folder';
                },

                get finalPackageName() {
                    return this.form.customName.trim() || this.autoPackageName;
                },

                get canGenerate() {
                    if (this.sourceMode === 'gitless') {
                        return !!(
                            this.gitless.baseArchive &&
                            this.gitless.headArchive &&
                            this.form.environment &&
                            this.finalPackageName &&
                            (this.form.environment !== 'PROD' || this.confirmedProd)
                        );
                    }

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

                async fetchRepoVersions() {
                    this.isLoadingVersions = true;
                    try {
                        const url = `${this.repositoryVersionsBaseUrl}/${encodeURIComponent(this.selectedRepository)}/versions`;

                        const res = await fetch(url, { headers: { Accept: 'application/json' } });
                        const data = await res.json();

                        if (!res.ok) {
                            throw new Error(data.message || 'Failed to load repository versions.');
                        }

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

                    this.handleVersionChange();
                },

                resetVersionState() {
                    this.cancelDiffPreviewRequest();
                    this.resetDiffPreview();
                    this.repoBranches = [];
                    this.repoTags = [];
                    this.repoReleases = [];
                    this.baseVersionDropdownOpen = false;
                    this.headVersionDropdownOpen = false;
                    this.baseVersionSearch = '';
                    this.headVersionSearch = '';
                    this.form.base = '';
                    this.form.head = '';
                    this.form.customName = '';
                    this.duplicatePackage = null;
                },

                handleVersionChange() {
                    this.checkDuplicate();
                    this.previewDetectedChanges();
                },

                toggleVersionDropdown(field) {
                    const isBase = field === 'base';

                    this.baseVersionDropdownOpen = isBase ? !this.baseVersionDropdownOpen : false;
                    this.headVersionDropdownOpen = isBase ? false : !this.headVersionDropdownOpen;

                    if (isBase && this.baseVersionDropdownOpen) {
                        this.baseVersionSearch = '';
                    }

                    if (!isBase && this.headVersionDropdownOpen) {
                        this.headVersionSearch = '';
                    }

                    if (this.baseVersionDropdownOpen || this.headVersionDropdownOpen) {
                        this.$nextTick(() => {
                            const ref = isBase ? this.$refs.baseVersionSearchInput : this.$refs.headVersionSearchInput;
                            ref?.focus();
                        });
                    }
                },

                selectVersion(field, uniqueKey) {
                    this.form[field] = uniqueKey;
                    this.baseVersionDropdownOpen = false;
                    this.headVersionDropdownOpen = false;
                    this.baseVersionSearch = '';
                    this.headVersionSearch = '';
                    this.handleVersionChange();
                },

                updatePackageName() {
                    this.checkDuplicate();
                },

                checkDuplicate() {
                    if (this.sourceMode === 'gitless') {
                        this.duplicatePackage = null;
                        return;
                    }

                    if (!this.selectedRepository || !this.baseRef || !this.headRef) {
                        this.duplicatePackage = null;
                        return;
                    }

                    this.duplicatePackage = this.completedPackages.find(pkg => {
                        const matchesRepository = String(pkg.repository_id || '') === String(this.selectedRepository)
                            || (this.selectedRepositoryOption && pkg.repo === this.selectedRepositoryOption.name);

                        return matchesRepository &&
                            pkg.base_version === this.baseRef &&
                            pkg.head_version === this.headRef &&
                            pkg.environment === this.form.environment;
                    }) || null;
                },

                resetDiffPreview() {
                    this.diffPreview = {
                        added: null,
                        deleted: null,
                        modified: null,
                        total: null,
                    };
                    this.diffPreviewError = '';
                    this.isLoadingDiffPreview = false;
                },

                cancelDiffPreviewRequest() {
                    if (this.diffPreviewAbortController) {
                        this.diffPreviewAbortController.abort();
                        this.diffPreviewAbortController = null;
                    }
                },

                async previewDetectedChanges() {
                    this.cancelDiffPreviewRequest();

                    if (!this.canPreviewDiff) {
                        this.resetDiffPreview();
                        return;
                    }

                    const requestId = ++this.diffPreviewRequestId;
                    const controller = new AbortController();

                    this.diffPreviewAbortController = controller;
                    this.isLoadingDiffPreview = true;
                    this.diffPreviewError = '';
                    this.diffPreview = {
                        added: null,
                        deleted: null,
                        modified: null,
                        total: null,
                    };

                    try {
                        const res = await fetch(this.previewUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                base_version: this.baseRef,
                                head_version: this.headRef,
                                repository_id: this.selectedRepository,
                            }),
                            signal: controller.signal,
                        });

                        const data = await res.json();

                        if (requestId !== this.diffPreviewRequestId) {
                            return;
                        }

                        if (!res.ok) {
                            throw new Error(data.message || 'Unable to preview changes.');
                        }

                        this.diffPreview = {
                            added: Number(data.summary?.added ?? 0),
                            deleted: Number(data.summary?.deleted ?? 0),
                            modified: Number(data.summary?.modified ?? 0),
                            total: Number(data.summary?.total ?? 0),
                        };
                    } catch (error) {
                        if (error.name === 'AbortError' || requestId !== this.diffPreviewRequestId) {
                            return;
                        }

                        this.diffPreviewError = error.message || 'Unable to preview changes.';
                        this.diffPreview = {
                            added: null,
                            deleted: null,
                            modified: null,
                            total: null,
                        };
                    } finally {
                        if (requestId === this.diffPreviewRequestId) {
                            this.isLoadingDiffPreview = false;

                            if (this.diffPreviewAbortController === controller) {
                                this.diffPreviewAbortController = null;
                            }
                        }
                    }
                },

                async handleGitlessDrop(event, side) {
                    const items = Array.from(event.dataTransfer?.items || []);
                    const files = Array.from(event.dataTransfer?.files || []);

                    this.gitless[`${side}DropActive`] = false;

                    const entries = items
                        .map((item) => item.webkitGetAsEntry?.())
                        .filter(Boolean);
                    const directories = entries.filter((entry) => entry.isDirectory);

                    if (directories.length) {
                        const fileEntries = [];

                        for (const directory of directories) {
                            fileEntries.push(...await this.collectGitlessFiles(directory));
                        }

                        await this.zipGitlessFiles(side, fileEntries);
                        return;
                    }

                    const archive = files.find((file) => /\.zip$/i.test(file.name));

                    if (archive) {
                        this.setGitlessArchive(side, archive);
                        return;
                    }

                    await this.zipGitlessLocalFiles(side, files);
                },

                async handleGitlessFolderSelection(event, side) {
                    const files = Array.from(event.target.files || []);
                    if (!files.length) return;

                    await this.zipGitlessLocalFiles(side, files);
                    event.target.value = '';
                },

                setGitlessArchive(side, file) {
                    if (!/\.zip$/i.test(file.name)) {
                        this.gitless.error = 'Upload a ZIP archive or choose a folder.';
                        return;
                    }

                    this.gitless[`${side}Archive`] = file;
                    this.gitless.error = '';
                },

                async zipGitlessLocalFiles(side, files) {
                    await this.zipGitlessFiles(side, files.map((file) => ({
                        file,
                        path: file.webkitRelativePath || file.name,
                    })));
                },

                async zipGitlessFiles(side, fileEntries) {
                    if (!fileEntries.length) return;

                    if (!window.JSZip) {
                        this.gitless.error = 'The ZIP helper could not load. Try uploading ZIP archives instead.';
                        return;
                    }

                    this.gitless.zipLoading = true;
                    this.gitless.error = '';

                    try {
                        const zip = new JSZip();
                        fileEntries.forEach((entry) => {
                            zip.file(entry.path, entry.file);
                        });

                        const rootName = (fileEntries[0].path || '').split('/')[0] || (side === 'base' ? 'base' : 'target');
                        const blob = await zip.generateAsync({ type: 'blob' }, (metadata) => {
                            this.gitless.zipProgress = `Zipping ${fileEntries.length} files... ${Math.round(metadata.percent)}%`;
                        });

                        this.setGitlessArchive(side, new File([blob], `${rootName}.zip`, { type: 'application/zip' }));
                    } catch (error) {
                        this.gitless.error = 'Could not zip the selected folder.';
                    } finally {
                        this.gitless.zipLoading = false;
                    }
                },

                async collectGitlessFiles(entry, pathPrefix = '') {
                    if (entry.isFile) {
                        return new Promise((resolve, reject) => {
                            entry.file(
                                (file) => resolve([{ file, path: `${pathPrefix}${file.name}` }]),
                                reject,
                            );
                        });
                    }

                    if (!entry.isDirectory) {
                        return [];
                    }

                    const reader = entry.createReader();
                    const children = await new Promise((resolve, reject) => {
                        const entries = [];
                        const readEntries = () => {
                            reader.readEntries((results) => {
                                if (!results.length) {
                                    resolve(entries);
                                    return;
                                }

                                entries.push(...results);
                                readEntries();
                            }, reject);
                        };

                        readEntries();
                    });

                    const nestedEntries = await Promise.all(
                        children.map((child) => this.collectGitlessFiles(child, `${pathPrefix}${entry.name}/`)),
                    );

                    return nestedEntries.flat();
                },

                async startPackaging() {
                    if (!this.canGenerate || this.isQueuing) return;

                    if (this.sourceMode === 'gitless') {
                        await this.startGitlessPackaging();
                        return;
                    }

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
                                repository_id: this.selectedRepository,
                                repo: this.selectedRepositoryOption?.name || '',
                                package_name: this.finalPackageName,
                                vcs_provider: this.selectedRepositoryProvider,
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

                async startGitlessPackaging() {
                    this.isQueuing = true;
                    this.gitless.error = '';
                    this.packagingError = '';
                    this.packagingResult = null;
                    this.packagingProgress = 0;
                    this.packagingMessage = 'Uploading folders...';

                    const formData = new FormData();
                    formData.append('base_archive', this.gitless.baseArchive);
                    formData.append('head_archive', this.gitless.headArchive);
                    formData.append('environment', this.form.environment);
                    formData.append('project_name', this.packageProjectLabel);
                    formData.append('package_name', this.finalPackageName);

                    try {
                        const res = await fetch(this.gitlessQueueUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const data = await res.json();
                        if (!res.ok || !data.job_id) {
                            throw new Error(data.message || 'Failed to queue gitless package job.');
                        }

                        this.currentJobId = data.job_id;
                        this.phase = 'progress';
                        this.packagingMessage = 'Job queued. Waiting for worker...';
                        this.startPolling();
                    } catch (error) {
                        this.packagingError = error.message || 'Unknown error.';
                        this.gitless.error = this.packagingError;
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
                    this.cancelDiffPreviewRequest();
                    this.phase = 'form';
                    this.baseVersionDropdownOpen = false;
                    this.headVersionDropdownOpen = false;
                    this.baseVersionSearch = '';
                    this.headVersionSearch = '';
                    this.confirmedProd = false;
                    this.packagingResult = null;
                    this.packagingError = '';
                    this.packagingProgress = 0;
                    this.packagingMessage = '';
                    this.currentJobId = null;
                    this.form.customName = '';
                    this.resetDiffPreview();
                    this.gitless = {
                        baseArchive: null,
                        headArchive: null,
                        baseDropActive: false,
                        headDropActive: false,
                        zipLoading: false,
                        zipProgress: '',
                        error: '',
                    };

                    if (this.canPreviewDiff) {
                        this.previewDetectedChanges();
                    }
                },
            };
        }
    </script>
@endpush