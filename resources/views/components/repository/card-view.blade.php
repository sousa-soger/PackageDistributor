{{-- ── Card grid view ────────────────────────────────────────────────────── --}}
<div x-show="viewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="repo-grid">
    <template x-for="repo in filteredRepositories" :key="repo.id">
        <article @click="setSelected(repo.id)"
            class="section-card p-5 group cursor-pointer text-left relative overflow-hidden transition-all duration-300 hover:shadow-soft"
            :class="selectedId === repo.id ? 'ring-[1px] ring-primary shadow-[0_0_0_4px_hsl(var(--primary)/0.25)] opacity-70' : ''">
            <div class="absolute -top-12 -right-12 h-32 w-32 rounded-full bg-primary/10 blur-2xl pointer-events-none"></div>

            <div class="relative">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div
                            class="h-12 w-12 rounded-lg brand-soft-bg flex items-center justify-center border border-primary/30 flex-shrink-0 text-primary"
                            x-html="providerIcon(repo.provider)">
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold truncate" style="color:hsl(var(--foreground))"
                                x-text="repo.label">
                            </div>
                            <div class="text-xs mt-0.5 truncate" style="color:hsl(var(--muted-foreground))">
                                <span x-text="repo.providerLabel"></span>
                                <span x-show="repo.serverHost" x-text="` - ${repo.serverHost}`"></span>
                            </div>
                        </div>
                    </div>

                    <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border shrink-0"
                        :class="statusBadgeClass(repo.status)" x-text="repo.statusLabel"></span>
                </div>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    <span x-show="repo.ownerName"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold pl-1.5 pr-2.5 py-1 rounded-full border border-primary/5 bg-primary/10 text-foreground">
                        <span class="relative flex shrink-0 overflow-hidden rounded-full h-5 w-5">
                            <span
                                class="flex h-full w-full items-center justify-center rounded-full bg-muted brand-gradient-bg text-[hsl(var(--on-brand))] text-[9px] font-semibold"
                                x-text="repo.ownerInitials"></span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-crown h-3 w-3 text-primary">
                            <path
                                d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z">
                            </path>
                            <path d="M5 21h14"></path>
                        </svg>
                        <span x-text="`Owner - ${repo.ownerName}`"></span>
                    </span>
                </div>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded"
                        style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))"
                        x-text="`${repo.branchCount} branches`"></span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded"
                        style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))"
                        x-text="`${repo.tagCount} tags`"></span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded"
                        style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))"
                        x-text="`default - ${repo.defaultBranch}`"></span>
                </div>

                <div class="mt-4 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-base">
                    <a x-show="repo.canCreatePackage" :href="createPackageUrl(repo)" @click.stop
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-base brand-gradient-bg text-[hsl(var(--on-brand))]"
                        title="Create package">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 16h6M19 13v6M21 10V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l2-1.14M7.5 4.27l9 5.15">
                            </path>
                            <polyline stroke-linecap="round" stroke-linejoin="round" points="3.29 7 12 12 20.71 7">
                            </polyline>
                            <line stroke-linecap="round" stroke-linejoin="round" x1="12" x2="12" y1="22" y2="12"></line>
                        </svg>
                        Create Package
                    </a>

                    <button type="button" x-show="repo.canManageRepository" @click.stop="handleRepositoryRefresh(repo)"
                        :disabled="syncing === repo.id"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-base"
                        style="background:hsl(var(--secondary));color:hsl(var(--foreground))"
                        :title="repositoryRefreshTitle(repo)">
                        <svg class="h-3.5 w-3.5" :class="syncing === repo.id ? 'animate-spin' : ''" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span
                            x-text="syncing === repo.id ? repositoryRefreshLoadingLabel(repo) : repositoryRefreshLabel(repo)"></span>
                    </button>

                    <button type="button" x-show="repo.canManageRepository" @click.stop="removeRepo(repo)"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-base"
                        style="background:hsl(var(--failed)/0.08);color:hsl(var(--failed))" title="Remove repository">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Remove
                    </button>
                </div>
            </div>
        </article>
    </template>

    {{-- "Add another" card --}}
    <button @click="window.dispatchEvent(new CustomEvent('open-repo-modal'))"
        class="section-card p-5 border-dashed flex flex-col items-center justify-center gap-3 min-h-[160px] transition-spring hover:shadow-soft cursor-pointer w-full text-center"
        style="border-style:dashed;border-color:hsl(var(--border))">
        <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center"
            style="color:hsl(var(--primary))">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
        </div>
        <div>
            <p class="text-sm font-semibold" style="color:hsl(var(--foreground))">Connect Repository</p>
            <p class="text-xs mt-0.5" style="color:hsl(var(--muted-foreground))">GitHub, GitLab, or custom server</p>
        </div>
    </button>

    {{-- Empty search state --}}
    <template x-if="filteredRepositories.length === 0 && searchQuery.trim() !== ''">
        <div class="col-span-full py-10 text-center text-sm text-muted-foreground">
            No repositories match your search.
        </div>
    </template>
</div>
