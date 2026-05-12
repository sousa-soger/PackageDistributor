{{-- ── List view ─────────────────────────────────────────────────────────── --}}
<div x-show="viewMode === 'list'" class="section-card p-0 overflow-hidden">
    <div
        class="grid grid-cols-[minmax(0,2fr)_minmax(0,1.2fr)_7rem_4rem_4rem_6rem] gap-3 px-5 py-2.5 text-[10px] uppercase tracking-wider text-muted-foreground bg-secondary/40 border-b border-border/60 font-semibold">
        <div>Repository</div>
        <div class="hidden md:block">Owner</div>
        <div class="hidden md:block">Provider</div>
        <div class="hidden md:block text-right">Branches</div>
        <div class="hidden md:block text-right">Members</div>
        <div class="text-center">Status</div>
    </div>
    <ul class="divide-y divide-border/60">
        <template x-for="repo in filteredRepositories" :key="'list-' + repo.id">
            <li>
                <button @click="setSelected(repo.id)"
                    class="w-full grid grid-cols-[minmax(0,2fr)_minmax(0,1.2fr)_7rem_4rem_4rem_6rem] gap-3 items-center px-5 py-3 hover:bg-secondary/40 transition-base text-left"
                    :class="selectedId === repo.id ? 'bg-primary/5' : ''">

                    {{-- Repository name + url --}}
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-9 w-9 rounded-md brand-soft-bg flex items-center justify-center text-primary shrink-0"
                            x-html="listProviderIcon(repo.provider)"></div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold font-mono truncate" x-text="repo.label"></div>
                            <div class="text-[11px] text-muted-foreground truncate"
                                x-text="repo.url || repo.name"></div>
                        </div>
                    </div>

                    {{-- Owner --}}
                    <div class="hidden md:flex items-center gap-2 min-w-0">
                        <template x-if="repo.ownerName">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="relative flex overflow-hidden rounded-full h-6 w-6 shrink-0">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-full bg-muted brand-gradient-bg text-[hsl(var(--on-brand))] text-[10px] font-semibold"
                                        x-text="repo.ownerInitials"></span>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-xs font-semibold truncate inline-flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="lucide lucide-crown h-2.5 w-2.5 text-primary">
                                            <path
                                                d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z">
                                            </path>
                                            <path d="M5 21h14"></path>
                                        </svg>
                                        <span x-text="repo.ownerName"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="!repo.ownerName">
                            <span class="text-xs text-muted-foreground">&mdash;</span>
                        </template>
                    </div>

                    {{-- Provider --}}
                    <div class="hidden md:block text-xs text-muted-foreground" x-text="repo.providerLabel"></div>

                    {{-- Branches --}}
                    <div class="hidden md:block text-xs text-muted-foreground tabular-nums text-center"
                        x-text="repo.branchCount"></div>

                    {{-- Members --}}
                    <div class="hidden md:block text-xs text-muted-foreground tabular-nums text-center"
                        x-text="repo.memberCount"></div>

                    {{-- Status badge --}}
                    <div class="text-right">
                        <span
                            class="inline-flex items-center gap-1.5 text-[10px] font-semibold px-2 py-0.5 rounded-md border whitespace-nowrap"
                            :class="statusBadgeClass(repo.status)">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            <span x-text="repo.statusLabel"></span>
                        </span>
                    </div>
                </button>
            </li>
        </template>
    </ul>

    {{-- Empty search result inside list view --}}
    <div x-show="filteredRepositories.length === 0" class="py-10 text-center text-sm text-muted-foreground">
        No repositories match your search.
    </div>
</div>
