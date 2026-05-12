@extends('layouts.app')

@section('title', 'Repositories')
@section('subtitle', 'GitHub, GitLab, company servers and local repositories.')

@section('topbar_actions')
  <button onclick="window.dispatchEvent(new CustomEvent('open-repo-modal'))"
    class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft hover:shadow-glow hover:brightness-[1.03] active:brightness-95 transition-base h-9 rounded-md px-3">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
    </svg>
    Connect Repository
  </button>
@endsection

@section('content')
  <div class="animate-fade-in px-4 sm:px-6 lg:px-8 py-6 lg:py-8" x-data="repositoriesPage({
                           repositories: @js($repositoryCards),
                           oauthConnections: @js($oauthConnections),
                           oauthProvider: @js(request('oauth_provider')),
                           roleOptions: @js($repositoryRoleOptions),
                           csrfToken: @js(csrf_token()),
                           createPackageBaseUrl: @js(route('create-package')),
                           oauthReconnectUrls: {
                             github: @js(route('github.oauth.redirect', ['return_to' => 'repositories'])),
                             gitlab: @js(route('gitlab.oauth.redirect', ['return_to' => 'repositories'])),
                           },
                         })" x-init="init()">

    {{-- ── Empty state ─────────────────────────────────────────────────────── --}}
    @if($repositories->isEmpty())
      <div class="section-card p-12 text-center">
        <div class="mx-auto mb-5 h-16 w-16 rounded-2xl brand-soft-bg flex items-center justify-center">
          <svg class="h-8 w-8" style="color:hsl(var(--primary))" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
          </svg>
        </div>
        <p class="text-sm font-semibold" style="color:hsl(var(--foreground))">No repositories yet</p>
        <p class="text-xs mt-1 max-w-sm mx-auto leading-relaxed" style="color:hsl(var(--muted-foreground))">
          Connect your first repository to start generating deployment packages.
          Supports GitHub, GitLab, company servers, SSH mirrors, and uploads.
        </p>
        <button @click="window.dispatchEvent(new CustomEvent('open-repo-modal'))"
          class="inline-flex items-center gap-1.5 mt-5 px-4 py-2 rounded-lg text-sm font-semibold transition-base hover:opacity-90"
          style="background:var(--gradient-brand)">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          Connect Repository
        </button>
      </div>
    @endif

    {{-- ── Search bar + view toggle ─────────────────────────────────────────── --}}
    @if($repositories->isNotEmpty())
      <div class="flex flex-wrap items-center gap-3 mb-5">
        <div class="relative flex-1 min-w-[220px] max-w-md">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-search absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.3-4.3"></path>
          </svg>
          <input type="search" x-model.debounce.200ms="searchQuery" placeholder="Search repositories…"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm pl-9">
        </div>
        <div class="ml-auto inline-flex items-center rounded-lg border border-border/70 bg-card p-1 shadow-sm">
          <button @click="setViewMode('cards')"
            :class="viewMode === 'cards' ? 'brand-soft-bg text-foreground shadow-soft' : 'text-muted-foreground hover:text-foreground'"
            class="px-2.5 py-1.5 rounded-md text-xs font-semibold inline-flex items-center gap-1.5 transition-base"
            aria-label="Card view">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              class="lucide lucide-layout-grid h-3.5 w-3.5">
              <rect width="7" height="7" x="3" y="3" rx="1"></rect>
              <rect width="7" height="7" x="14" y="3" rx="1"></rect>
              <rect width="7" height="7" x="14" y="14" rx="1"></rect>
              <rect width="7" height="7" x="3" y="14" rx="1"></rect>
            </svg>
            Cards
          </button>
          <button @click="setViewMode('list')"
            :class="viewMode === 'list' ? 'brand-soft-bg text-foreground shadow-soft' : 'text-muted-foreground hover:text-foreground'"
            class="px-2.5 py-1.5 rounded-md text-xs font-semibold inline-flex items-center gap-1.5 transition-base"
            aria-label="List view">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              class="lucide lucide-list h-3.5 w-3.5">
              <path d="M3 12h.01"></path>
              <path d="M3 18h.01"></path>
              <path d="M3 6h.01"></path>
              <path d="M8 12h13"></path>
              <path d="M8 18h13"></path>
              <path d="M8 6h13"></path>
            </svg>
            List
          </button>
        </div>
      </div>

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
                    class="h-12 w-12 rounded-lg brand-soft-bg flex items-center justify-center flex-shrink-0 text-primary"
                    x-html="providerIcon(repo.provider)">
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold truncate" style="color:hsl(var(--foreground))" x-text="repo.label">
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
                  class="inline-flex items-center gap-1.5 text-[11px] font-semibold pl-1 pr-2 py-0.5 rounded-full border border-primary/30 bg-primary/5 text-foreground">
                  <span class="relative flex shrink-0 overflow-hidden rounded-full h-4 w-4">
                    <span
                      class="flex h-full w-full items-center justify-center rounded-full bg-muted brand-gradient-bg text-[hsl(var(--on-brand))] text-[8px] font-semibold"
                      x-text="repo.ownerInitials"></span>
                  </span>
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-crown h-2.5 w-2.5 text-primary">
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
                  <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M16 16h6M19 13v6M21 10V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l2-1.14M7.5 4.27l9 5.15">
                    </path>
                    <polyline stroke-linecap="round" stroke-linejoin="round" points="3.29 7 12 12 20.71 7"></polyline>
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
                    <div class="text-[11px] text-muted-foreground truncate" x-text="repo.url || repo.name"></div>
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
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
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
    @endif

    <template x-if="selectedRepository">
      <div>
        <template x-teleport="body">
          <div class="fixed inset-0 z-40" @keydown.escape.window="selectedId = null" @click="selectedId = null">
            <div class="absolute inset-0 bg-black/65 backdrop-blur-[2px]" @click="selectedId = null"></div>

            <div
              class="absolute inset-x-0 bottom-0 flex max-h-screen items-end justify-center px-3 pt-8 sm:px-6 sm:pt-12">
              <article @click.stop
                class="section-card text-left relative overflow-x-hidden overflow-y-auto ring-primary shadow-soft p-0 z-10 w-full max-w-7xl h-[92vh] max-h-[calc(100vh-1rem)] rounded-t-2xl sm:rounded-2xl flex flex-col"
                role="dialog" aria-modal="true">
                <div class="absolute -top-12 -right-12 h-32 w-32 rounded-full bg-primary/10 blur-2xl pointer-events-none">
                </div>

                {{-- Header --}}
                <div class="relative p-5 border-b border-border/60 brand-soft-bg">
                  <div class="flex items-start justify-between gap-3 mb-3">
                    <div
                      class="rounded-lg brand-soft-bg shadow-soft flex items-center justify-center shrink-0 h-14 w-14 text-primary"
                      x-html="providerIcon(selectedRepository.provider)">
                    </div>
                    <div class="min-w-0 flex-1">
                      <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-2xl font-semibold truncate" x-text="selectedRepository.label"></h2>
                        <span
                          class="inline-flex items-center gap-1.5 text-[10px] font-semibold px-2 py-0.5 rounded-md border"
                          :class="statusBadgeClass(selectedRepository.status)">
                          <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                          <span x-text="selectedRepository.statusLabel"></span>
                        </span>
                      </div>

                      <a x-show="selectedRepository.url" :href="selectedRepository.url" target="_blank" rel="noreferrer"
                        class="text-sm text-muted-foreground hover:text-primary inline-flex items-center gap-1 mt-1 break-all"
                        x-text="selectedRepository.url">
                      </a>

                      <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        <span x-show="selectedRepository.ownerName"
                          class="inline-flex items-center gap-1.5 text-[11px] font-semibold pl-1 pr-2 py-0.5 rounded-full border border-primary/30 bg-primary/5 text-foreground">
                          <span class="relative flex shrink-0 overflow-hidden rounded-full h-4 w-4">
                            <span
                              class="flex h-full w-full items-center justify-center rounded-full bg-muted brand-gradient-bg text-[hsl(var(--on-brand))] text-[8px] font-semibold"
                              x-text="selectedRepository.ownerInitials"></span>
                          </span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-crown h-2.5 w-2.5 text-primary">
                            <path
                              d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z">
                            </path>
                            <path d="M5 21h14"></path>
                          </svg>
                          <span x-text="`Owner - ${selectedRepository.ownerName}`"></span>
                        </span>
                        <span
                          class="text-[11px] font-medium px-2 py-0.5 rounded bg-secondary/70 text-muted-foreground inline-flex items-center gap-1">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-git-branch h-3 w-3">
                            <line x1="6" x2="6" y1="3" y2="15"></line>
                            <circle cx="18" cy="6" r="3"></circle>
                            <circle cx="6" cy="18" r="3"></circle>
                            <path d="M18 9a9 9 0 0 1-9 9"></path>
                          </svg>
                          <span x-text="selectedRepository.branchCount + ' branches'"></span>
                        </span>
                        <span
                          class="text-[11px] font-medium px-2 py-0.5 rounded bg-secondary/70 text-muted-foreground inline-flex items-center gap-1">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-tag h-3 w-3">
                            <path
                              d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z">
                            </path>
                            <circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle>
                          </svg>
                          <span x-text="selectedRepository.tagCount + ' tags'"></span>
                        </span>
                        <span
                          class="text-[11px] font-medium px-2 py-0.5 rounded bg-secondary/70 text-muted-foreground inline-flex items-center gap-1">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-users h-3 w-3">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                          </svg>
                          <span x-text="memberLabel(selectedRepository)"></span>
                        </span>
                      </div>
                    </div>
                    <div class="flex items-center gap-1" @click.stop>
                      <a x-show="selectedRepository.canCreatePackage" :href="createPackageUrl(selectedRepository)"
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
                        Create package
                      </a>
                      <button type="button" x-show="selectedRepository.canManageRepository"
                        @click="handleRepositoryRefresh(selectedRepository)" :disabled="syncing === selectedRepository.id"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm hover:bg-accent hover:text-accent-foreground transition-colors disabled:opacity-50"
                        :title="repositoryRefreshTitle(selectedRepository)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                          :class="syncing === selectedRepository.id ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none"
                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                          <path d="M3 3v5h5" />
                          <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
                          <path d="M16 16h5v5" />
                        </svg>
                      </button>
                      <button type="button" x-show="selectedRepository.canManageRepository"
                        @click="removeRepo(selectedRepository)"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm text-failed hover:bg-failed/10 transition-colors"
                        title="Remove repository">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M3 6h18" />
                          <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                          <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                        </svg>
                      </button>
                      <button type="button" @click="selectedId = null"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm hover:bg-accent hover:text-accent-foreground transition-colors"
                        title="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M18 6 6 18" />
                          <path d="m6 6 12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <div
                  class="grid grid-cols-1 lg:grid-cols-10 divide-y lg:divide-y-0 lg:divide-x divide-border/60 flex-1 overflow-hidden">
                  {{-- Connection information --}}
                  <div class="lg:col-span-3 p-5 overflow-y-auto scrollbar-thin">
                    <div class="flex items-center justify-between mb-3">
                      <h4 class="text-sm font-semibold inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 24 24"
                          fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                          stroke-linejoin="round">
                          <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                          <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                        </svg>
                        Connection
                      </h4>
                    </div>

                    <dl class="space-y-3 text-xs">
                      <div class="rounded-lg border border-border/60 p-3">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Connection
                          type</dt>
                        <dd class="mt-1 font-medium" x-text="selectedRepository.authType"></dd>
                      </div>
                      <div class="rounded-lg border border-border/60 p-3">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Synced</dt>
                        <dd class="mt-1 font-medium" x-text="selectedRepository.lastSyncedLabel"></dd>
                      </div>
                      <div class="rounded-lg border border-border/60 p-3">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Slug</dt>
                        <dd class="mt-1 font-mono break-all" x-text="selectedRepository.slug"></dd>
                      </div>
                      <div class="rounded-lg border border-border/60 p-3">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Default branch
                        </dt>
                        <dd class="mt-1 font-medium" x-text="selectedRepository.defaultBranch"></dd>
                      </div>
                      <div x-show="selectedRepository.serverHost" class="rounded-lg border border-border/60 p-3">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Host</dt>
                        <dd class="mt-1 font-mono break-all" x-text="selectedRepository.serverHost"></dd>
                      </div>
                    </dl>

                    <div x-show="selectedRepository.credentialsError" x-text="selectedRepository.credentialsError"
                      class="mt-4 rounded-lg border px-3 py-2 text-xs"
                      style="border-color:hsl(var(--failed)/0.30);color:hsl(var(--failed));background:hsl(var(--failed)/0.05)">
                    </div>

                    <div class="mt-4 space-y-2" x-show="selectedRepository.canManageRepository">
                      <button type="button" @click="handleRepositoryRefresh(selectedRepository)"
                        :disabled="syncing === selectedRepository.id"
                        class="w-full inline-flex h-9 items-center justify-center gap-2 rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent disabled:opacity-50">
                        <svg class="h-3.5 w-3.5" :class="syncing === selectedRepository.id ? 'animate-spin' : ''"
                          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span
                          x-text="syncing === selectedRepository.id ? repositoryRefreshLoadingLabel(selectedRepository) : repositoryRefreshLabel(selectedRepository)"></span>
                      </button>

                      <template
                        x-if="selectedRepository.provider === 'github' || selectedRepository.provider === 'gitlab'">
                        <div class="grid grid-cols-1 gap-2">
                          <button type="button" @click="reconnectOauth(selectedRepository)"
                            class="inline-flex h-9 items-center justify-center rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent">
                            Reconnect OAuth
                          </button>
                          <button type="button" @click="startCredentialEdit(selectedRepository, 'pat')"
                            class="inline-flex h-9 items-center justify-center rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent">
                            Change PAT
                          </button>
                        </div>
                      </template>

                      <template x-if="selectedRepository.provider === 'company-server'">
                        <button type="button" @click="startCredentialEdit(selectedRepository, 'ssh')"
                          class="w-full inline-flex h-9 items-center justify-center rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent">
                          Update SSH connection
                        </button>
                      </template>

                    </div>

                    <div x-show="selectedRepository.credentialMode === 'pat'"
                      class="mt-4 rounded-lg border border-border/60 p-3 space-y-3">
                      <label class="block text-xs font-medium">New personal access token</label>
                      <input type="password" x-model="selectedRepository.credentialToken"
                        class="h-9 w-full rounded-md border border-border bg-background px-3 text-xs outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20"
                        placeholder="Paste token">
                      <div class="flex gap-2">
                        <button type="button" @click="saveRepositoryCredentials(selectedRepository)"
                          :disabled="selectedRepository.credentialsSaving || !selectedRepository.credentialToken"
                          class="inline-flex h-8 flex-1 items-center justify-center rounded-md brand-gradient-bg px-3 text-xs font-medium text-[hsl(var(--on-brand))] disabled:opacity-50">
                          Save
                        </button>
                        <button type="button" @click="cancelCredentialEdit(selectedRepository)"
                          class="inline-flex h-8 items-center justify-center rounded-md border border-border px-3 text-xs">
                          Cancel
                        </button>
                      </div>
                    </div>

                    <div x-show="selectedRepository.credentialMode === 'ssh'"
                      class="mt-4 rounded-lg border border-border/60 p-3 space-y-3">
                      <label class="block text-xs font-medium">Host</label>
                      <input type="text" x-model="selectedRepository.credentialHost"
                        class="h-9 w-full rounded-md border border-border bg-background px-3 text-xs outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20"
                        placeholder="git.company.internal">
                      <label class="block text-xs font-medium">Repository path</label>
                      <input type="text" x-model="selectedRepository.credentialPath"
                        class="h-9 w-full rounded-md border border-border bg-background px-3 text-xs outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20"
                        placeholder="group/repository.git">
                      <div class="flex gap-2">
                        <button type="button" @click="saveRepositoryCredentials(selectedRepository)"
                          :disabled="selectedRepository.credentialsSaving || !selectedRepository.credentialHost || !selectedRepository.credentialPath"
                          class="inline-flex h-8 flex-1 items-center justify-center rounded-md brand-gradient-bg px-3 text-xs font-medium text-[hsl(var(--on-brand))] disabled:opacity-50">
                          Save
                        </button>
                        <button type="button" @click="cancelCredentialEdit(selectedRepository)"
                          class="inline-flex h-8 items-center justify-center rounded-md border border-border px-3 text-xs">
                          Cancel
                        </button>
                      </div>
                    </div>

                  </div>

                  {{-- Members and roles --}}
                  <div class="lg:col-span-7 p-5 overflow-y-auto scrollbar-thin" @click.stop>
                    <div class="flex items-center justify-between mb-3">
                      <h4 class="text-sm font-semibold inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 24 24"
                          fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                          stroke-linejoin="round">
                          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                          <circle cx="9" cy="7" r="4" />
                          <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                        People and Roles
                      </h4>
                      <span class="text-[11px] text-muted-foreground" x-text="selectedRepository.memberCount"></span>
                    </div>

                    <div x-show="selectedRepository.membersError" x-text="selectedRepository.membersError"
                      class="mb-3 rounded-lg border px-3 py-2 text-xs"
                      style="border-color:hsl(var(--failed)/0.30);color:hsl(var(--failed));background:hsl(var(--failed)/0.05)">
                    </div>

                    <template x-if="selectedRepository.canManageMembers">
                      <div class="mb-4 rounded-lg border border-border/60 p-3">
                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                          Add member
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
                          <input type="search" x-model="selectedRepository.userSearch"
                            @input.debounce.300ms="searchRepositoryUsers(selectedRepository)"
                            placeholder="Search LDAP users..."
                            class="h-9 rounded-md border border-border bg-background px-3 text-xs outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20">
                          <select x-model="selectedRepository.userRoleToAdd"
                            class="h-9 rounded-md border border-border bg-background px-3 text-xs outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20">
                            <template x-for="role in roleOptions" :key="`add-repository-user-role-${role.key}`">
                              <option :value="role.key" x-text="role.label"></option>
                            </template>
                          </select>
                        </div>
                        <div x-show="selectedRepository.userSearchLoading" class="mt-2 text-[11px] text-muted-foreground">
                          Searching...
                        </div>
                        <div x-show="selectedRepository.userSearchError" x-text="selectedRepository.userSearchError"
                          class="mt-2 text-[11px] text-failed"></div>
                        <div x-show="selectedRepository.userSuggestions.length > 0"
                          class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-border/60">
                          <template x-for="user in selectedRepository.userSuggestions" :key="user.username || user.email">
                            <button type="button"
                              @click="!user.already_member && addRepositoryUser(selectedRepository, user)"
                              :disabled="user.already_member || selectedRepository.userSaving"
                              class="flex w-full items-center gap-3 px-3 py-2 text-left text-xs transition-colors hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60">
                              <template x-if="user.avatar">
                                <img :src="user.avatar" :alt="user.name"
                                  class="h-7 w-7 rounded-full object-cover border border-border/70 shrink-0">
                              </template>
                              <template x-if="!user.avatar">
                                <div
                                  class="h-7 w-7 rounded-full brand-gradient-bg shadow-soft flex items-center justify-center text-[10px] font-semibold on-brand shrink-0"
                                  x-text="userInitials(user.name, user.username)"></div>
                              </template>
                              <span class="min-w-0 flex-1">
                                <span class="block text-xs font-semibold truncate" x-text="user.name"></span>
                                <span class="block text-[11px] text-muted-foreground truncate"
                                  x-text="userSubtitle(user)"></span>
                              </span>
                              <span class="text-[10px] font-semibold px-2 py-1 rounded-md border"
                                :class="user.already_member ? 'border-border text-muted-foreground' : 'border-primary/30 text-primary'"
                                x-text="user.already_member ? 'On repository' : 'Add'"></span>
                            </button>
                          </template>
                        </div>
                      </div>
                    </template>

                    <template x-if="selectedRepository.users.length === 0">
                      <p class="text-xs text-muted-foreground py-6 text-center">No members assigned yet.</p>
                    </template>

                    <div x-show="selectedRepository.users.length > 0">
                      <ul class="space-y-2">
                        <template x-for="user in selectedRepository.users" :key="`repository-user-${user.id}`">
                          <li
                            class="flex items-center gap-3 rounded-lg border border-border/60 p-3 hover:shadow-soft transition-base">
                            <template x-if="user.avatar">
                              <img :src="user.avatar" :alt="user.name"
                                class="h-9 w-9 rounded-full object-cover border border-border/70 shrink-0">
                            </template>
                            <template x-if="!user.avatar">
                              <div
                                class="h-9 w-9 rounded-lg brand-gradient-bg shadow-soft flex items-center justify-center text-[11px] font-semibold on-brand shrink-0"
                                x-text="user.initials"></div>
                            </template>
                            <div class="min-w-0 flex-1">
                              <div class="text-xs font-semibold truncate" x-text="user.name"></div>
                              <div class="text-[11px] text-muted-foreground truncate" x-text="userSubtitle(user)"></div>
                            </div>
                            <template x-if="selectedRepository.canManageMembers">
                              <select x-model="user.role"
                                @change="updateRepositoryUserRole(selectedRepository, user, $event.target.value)"
                                :disabled="selectedRepository.roleSavingId === roleSavingKey(user.id)"
                                class="h-8 w-36 rounded-md border border-border bg-background px-2 text-[11px] outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20">
                                <template x-for="role in roleOptions"
                                  :key="`repository-user-role-${user.id}-${role.key}`">
                                  <option :value="role.key" x-text="role.label"></option>
                                </template>
                              </select>
                            </template>
                            <template x-if="!selectedRepository.canManageMembers">
                              <span
                                class="text-[10px] font-medium px-2 py-0.5 rounded-md border border-border text-muted-foreground"
                                x-text="roleLabel(user.role)"></span>
                            </template>
                            <button type="button" x-show="selectedRepository.canManageMembers"
                              @click="removeRepositoryUser(selectedRepository, user)"
                              :disabled="selectedRepository.removingUserId === user.id"
                              class="inline-flex h-7 w-7 items-center justify-center rounded-md text-failed hover:bg-failed/10 transition-colors disabled:opacity-50"
                              title="Remove user">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                              </svg>
                            </button>
                          </li>
                        </template>
                      </ul>
                    </div>
                  </div>
                </div>
              </article>
            </div>
          </div>
        </template>
      </div>
    </template>

    <div x-show="uploadVersionModal" x-cloak x-transition:enter="transition ease-out duration-200"
      x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
      x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4"
      style="background:hsl(220 30% 5% / 0.55);backdrop-filter:blur(4px)" @click.self="closeUploadVersionModal()">
      <div class="w-full max-w-lg animate-slide-up overflow-hidden"
        style="background:hsl(var(--card));border-radius:calc(var(--radius) * 1.5);border:1px solid hsl(var(--border)/0.7);box-shadow:var(--shadow-lg)"
        role="dialog" aria-modal="true">
        <div class="brand-soft-bg px-6 py-5 border-b border-border/60">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <h2 class="font-semibold tracking-tight text-xl" style="color:hsl(var(--foreground))">Upload New Version
              </h2>
              <p class="mt-1 text-sm" style="color:hsl(var(--muted-foreground))">
                <span>Replace</span>
                <span class="font-medium" style="color:hsl(var(--foreground))"
                  x-text="uploadVersionRepository?.label"></span>
                <span>with a full project upload.</span>
              </p>
            </div>
            <button type="button" @click="closeUploadVersionModal()"
              class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md transition-colors hover:bg-accent"
              aria-label="Close">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="px-6 py-5 max-h-[60vh] overflow-y-auto scrollbar-thin">
          <div class="space-y-4">
            <label for="upload-version-archive"
              class="group relative flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-border/70 bg-secondary/30 px-6 py-9 text-center cursor-pointer transition-all hover:border-primary/60 hover:bg-secondary/50"
              :class="uploadVersionDropActive ? 'border-primary/60 bg-secondary/50' : ''"
              @dragenter.prevent="uploadVersionDropActive = true" @dragover.prevent="uploadVersionDropActive = true"
              @dragleave.self.prevent="uploadVersionDropActive = false" @drop.prevent="handleUploadVersionDrop($event)">
              <div
                class="h-14 w-14 rounded-2xl brand-soft-bg flex items-center justify-center text-primary transition-transform group-hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="17 8 12 3 7 8" />
                  <line x1="12" x2="12" y1="3" y2="15" />
                </svg>
              </div>
              <div class="space-y-1">
                <div class="text-base font-semibold">Drag &amp; Drop the full project</div>
                <p class="text-xs text-muted-foreground max-w-sm">Upload the complete folder, ZIP, or Git bundle again.
                  Missing files in this upload will be treated as removed.</p>
                <p class="text-xs text-muted-foreground">Click to browse for a ZIP or bundle file.</p>
              </div>
              <input id="upload-version-archive" type="file" accept=".zip,.bundle" class="hidden"
                @change="handleUploadVersionArchiveSelection($event)">
            </label>

            <div class="flex items-center justify-between gap-3 rounded-lg border border-dashed p-3 text-xs"
              style="border-color:hsl(var(--border));background:hsl(var(--secondary)/0.35);color:hsl(var(--muted-foreground))">
              <span>Need to browse a folder instead of an archive?</span>
              <button type="button" @click="$refs.uploadVersionFolderInput.click()"
                class="inline-flex h-8 shrink-0 items-center justify-center rounded-md border border-border bg-background px-3 font-medium transition-colors hover:bg-accent"
                style="color:hsl(var(--foreground))">
                Browse Folder
              </button>
              <input x-ref="uploadVersionFolderInput" type="file" webkitdirectory multiple class="hidden"
                @change="handleUploadVersionFolderSelection($event)">
            </div>

            <div x-show="uploadVersionZipLoading" x-cloak class="text-sm" style="color:hsl(var(--muted-foreground))"
              x-text="uploadVersionZipProgress"></div>

            <div x-show="uploadVersionFile" x-cloak class="space-y-2">
              <div class="flex items-center justify-between gap-3 text-xs" style="color:hsl(var(--muted-foreground))">
                <span class="truncate" x-text="uploadVersionFile?.name"></span>
                <span x-text="`${uploadVersionProgress}%`"></span>
              </div>
              <div class="h-2 rounded-full overflow-hidden" style="background:hsl(var(--border))">
                <div class="h-full transition-all"
                  :style="`width:${uploadVersionProgress}%;background:var(--gradient-brand)`"></div>
              </div>
            </div>

            <div x-show="uploadVersionError" x-cloak class="rounded-lg border px-3 py-2 text-sm"
              style="border-color:hsl(var(--failed)/0.30);color:hsl(var(--failed));background:hsl(var(--failed)/0.05)"
              x-text="uploadVersionError"></div>
          </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 px-6 py-4 border-t"
          style="border-color:hsl(var(--border)/0.6);background:hsl(var(--secondary)/0.5)">
          <button type="button" @click="closeUploadVersionModal()"
            class="inline-flex h-9 items-center justify-center rounded-md border border-border bg-background px-3 text-sm font-medium transition-colors hover:bg-accent">
            Cancel
          </button>
          <button type="button" @click="uploadRepositoryVersion()" :disabled="uploadVersionLoading || !uploadVersionFile"
            class="brand-gradient-bg shadow-soft inline-flex h-9 items-center justify-center rounded-md px-3 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-text="uploadVersionLoading ? 'Uploading...' : 'Upload New Version'"></span>
          </button>
        </div>
      </div>
    </div>

    @include('_partials.create-repository-modal')

  </div>{{-- /page --}}

@endsection

@push('scripts')
  <script>
    function repositoriesPage(config = {}) {
      const pendingConnectionKey = 'repositories.pending-connection';

      return {
        repositories: config.repositories ?? [],
        selectedId: null,
        viewMode: localStorage.getItem('repositories.viewMode') ?? 'cards',
        searchQuery: '',
        modal: false,
        step: 'provider',
        provider: null,
        loading: false,
        syncing: null,
        error: '',
        uploadVersionModal: false,
        uploadVersionRepository: null,
        uploadVersionFile: null,
        uploadVersionError: '',
        uploadVersionLoading: false,
        uploadVersionProgress: 0,
        uploadVersionDropActive: false,
        uploadVersionZipLoading: false,
        uploadVersionZipProgress: '',
        roleOptions: Array.isArray(config.roleOptions) ? config.roleOptions : [],
        csrfToken: config.csrfToken ?? '',
        createPackageBaseUrl: config.createPackageBaseUrl ?? '/create-package',
        oauthReconnectUrls: config.oauthReconnectUrls ?? {},
        oauthConnections: config.oauthConnections ?? {},
        oauthProvider: config.oauthProvider ?? null,

        authMethod: 'oauth',
        token: '',
        host: '',
        repoUrl: '',

        get filteredRepositories() {
          const q = this.searchQuery.trim().toLowerCase();
          if (!q) return this.repositories;
          return this.repositories.filter((repo) =>
            (repo.label || '').toLowerCase().includes(q) ||
            (repo.url || '').toLowerCase().includes(q) ||
            (repo.name || '').toLowerCase().includes(q) ||
            (repo.providerLabel || '').toLowerCase().includes(q) ||
            (repo.ownerName || '').toLowerCase().includes(q)
          );
        },

        setViewMode(mode) {
          this.viewMode = mode;
          localStorage.setItem('repositories.viewMode', mode);
        },

        providers: [
          {
            id: 'github',
            name: 'GitHub',
            description: 'Connect a public or private GitHub repository via OAuth.',
            authMethod: 'github',
            authLabel: 'OAuth (recommended) or Personal Access Token',
            icon: `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"></path><path d="M9 18c-4.51 2-5-2-7-2"></path></svg>`,
          },
          {
            id: 'gitlab',
            name: 'GitLab',
            description: 'Connect a GitLab.com or self-hosted GitLab repository.',
            authMethod: 'gitlab',
            authLabel: 'OAuth (recommended) or Personal Access Token',
            icon: `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"></path></svg>`,
          },
          {
            id: 'company-server',
            name: 'Company Server',
            description: 'Connect a self-hosted Git server over SSH.',
            authMethod: 'ssh',
            authLabel: 'SSH key + host',
            icon: `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect><rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect><line x1="6" x2="6.01" y1="6" y2="6"></line><line x1="6" x2="6.01" y1="18" y2="18"></line></svg>`,
          },
          {
            id: 'local-pc',
            name: 'Local PC',
            description: 'Connect a local repository through SSH access or upload.',
            authMethod: 'local',
            authLabel: 'SSH or upload',
            icon: `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" x2="2" y1="12" y2="12"></line><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path><line x1="6" x2="6.01" y1="16" y2="16"></line><line x1="10" x2="10.01" y1="16" y2="16"></line></svg>`,
          },
        ],

        get canSubmitAuth() {
          if (!this.provider) return false;

          if (this.provider.authMethod === 'github' || this.provider.authMethod === 'gitlab') {
            return this.authMethod === 'oauth' || this.token.trim() !== '';
          }

          return true;
        },

        get canSubmitDetails() {
          if (this.loading) return false;
          if (this.provider?.authMethod === 'ssh') return this.host.trim() !== '' && this.repoUrl.trim() !== '';
          return this.repoUrl.trim() !== '';
        },

        get selectedRepository() {
          if (!this.selectedId) return null;
          return this.repositories.find((repo) => repo.id === this.selectedId) ?? null;
        },

        init() {
          this.repositories = this.repositories.map((repo) => this.normalizeRepository(repo));

          if (sessionStorage.getItem('flash_toast_msg')) {
            const msg = sessionStorage.getItem('flash_toast_msg');
            const type = sessionStorage.getItem('flash_toast_type');
            sessionStorage.removeItem('flash_toast_msg');
            sessionStorage.removeItem('flash_toast_type');
            setTimeout(() => window.dispatchEvent(
              new CustomEvent('toast', { detail: { type, message: msg } })
            ), 50);
          }

          document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.uploadVersionModal) {
              this.closeUploadVersionModal();
              return;
            }

            if (e.key === 'Escape' && this.selectedId) {
              this.selectedId = null;
            }
          });
        },

        normalizeRepository(repo) {
          return {
            ...repo,
            users: Array.isArray(repo.users) ? repo.users : [],
            memberCount: Number(repo.memberCount ?? 0),
            ownerInitials: repo.ownerInitials || this.userInitials(repo.ownerName),
            ownerName: repo.ownerName || '',
            canManageMembers: Boolean(repo.canManageMembers),
            canManageRepository: Boolean(repo.canManageRepository),
            membersError: '',
            userSearch: '',
            userSuggestions: [],
            userSearchLoading: false,
            userSearchError: '',
            userSearchNonce: 0,
            type: repo.type || null,
            userSaving: false,
            userRoleToAdd: this.roleOptions[0]?.key ?? 'viewer',
            roleSavingId: null,
            removingUserId: null,
            credentialMode: null,
            credentialToken: '',
            credentialHost: repo.serverHost || '',
            credentialPath: repo.serverPath || repo.name || '',
            credentialProtocol: repo.serverProtocol || 'SSH',
            credentialsError: '',
            credentialsSaving: false,
          };
        },

        setSelected(id) {
          this.selectedId = id;
        },

        createPackageUrl(repo) {
          const url = new URL(this.createPackageBaseUrl, window.location.origin);
          url.searchParams.set('repository', repo.id);

          return `${url.pathname}${url.search}`;
        },

        isUploadedArchive(repo) {
          return repo?.provider === 'local-pc' && repo?.type === 'uploaded';
        },

        repositoryRefreshLabel(repo) {
          return this.isUploadedArchive(repo) ? 'Upload New Version' : 'Sync';
        },

        repositoryRefreshLoadingLabel(repo) {
          return this.isUploadedArchive(repo) ? 'Uploading...' : 'Syncing...';
        },

        repositoryRefreshTitle(repo) {
          return this.isUploadedArchive(repo)
            ? 'Upload a full project folder, ZIP, or Git bundle'
            : 'Sync branches and tags';
        },

        handleRepositoryRefresh(repo) {
          if (this.isUploadedArchive(repo)) {
            this.openUploadVersionModal(repo);
            return;
          }

          this.syncRepo(repo);
        },

        memberLabel(repo) {
          const count = Number(repo?.memberCount ?? 0);
          return `${count} member${count === 1 ? '' : 's'}`;
        },

        roleSavingKey(userId) {
          return `user-${userId}`;
        },

        roleLabel(role) {
          return this.roleOptions.find((option) => option.key === role)?.label ?? role;
        },

        userInitials(name, fallback = '') {
          const source = (name || fallback || '?').trim();
          const parts = source.split(/\s+/).filter(Boolean);
          if (parts.length === 0) return '?';
          return parts.map((part) => part[0]?.toUpperCase() || '').slice(0, 2).join('');
        },

        userSubtitle(user) {
          if (user.email && user.username) return `${user.username} - ${user.email}`;
          return user.email || user.username || 'LDAP user';
        },

        statusBadgeClass(status) {
          if (status === 'connected') return 'bg-success/10 text-success border-success/30';
          if (status === 'expired') return 'bg-queued/10 text-queued border-queued/30';
          if (status === 'needs-auth') return 'bg-failed/10 text-failed border-failed/30';
          return 'bg-inactive/10 text-inactive border-inactive/30';
        },

        providerIcon(provider) {
          if (provider === 'github') {
            return `<svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12" /></svg>`;
          }

          if (provider === 'gitlab') {
            return `<svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24"><path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 01-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 014.82 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.49h8.1l2.44-7.51A.42.42 0 0118.6 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.51L23 13.45a.84.84 0 01-.35.94z" /></svg>`;
          }

          if (provider === 'company-server') {
            return `<svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect><rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect><line x1="6" x2="6.01" y1="6" y2="6"></line><line x1="6" x2="6.01" y1="18" y2="18"></line></svg>`;
          }

          return `<svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" x2="2" y1="12" y2="12"></line><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path><line x1="6" x2="6.01" y1="16" y2="16"></line><line x1="10" x2="10.01" y1="16" y2="16"></line></svg>`;
        },

        listProviderIcon(provider) {
          if (provider === 'github') {
            return `<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12" /></svg>`;
          }

          if (provider === 'gitlab') {
            return `<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 01-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 014.82 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.49h8.1l2.44-7.51A.42.42 0 0118.6 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.51L23 13.45a.84.84 0 01-.35.94z" /></svg>`;
          }

          if (provider === 'company-server') {
            return `<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect><rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect><line x1="6" x2="6.01" y1="6" y2="6"></line><line x1="6" x2="6.01" y1="18" y2="18"></line></svg>`;
          }

          return `<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="22" x2="2" y1="12" y2="12"></line><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path><line x1="6" x2="6.01" y1="16" y2="16"></line><line x1="10" x2="10.01" y1="16" y2="16"></line></svg>`;
        },

        openModal() {
          this.reset();
          this.modal = true;
        },
        closeModal(next = false) {
          if (!next) setTimeout(() => this.reset(), 200);
          this.modal = next;
        },
        reset() {
          this.step = 'provider';
          this.provider = null;
          this.repoUrl = '';
          this.token = '';
          this.host = '';
          this.authMethod = 'oauth';
          this.error = '';
          this.loading = false;
        },
        pickProvider(provider) {
          this.provider = provider;
          this.authMethod = 'oauth';
          this.token = '';
          this.error = '';
          this.step = 'auth';
        },
        async handleVerify() {
          if (!this.canSubmitDetails) {
            return;
          }

          this.error = '';
          this.loading = true;
          this.step = 'verifying';

          try {
            const response = await fetch('{{ route('repositories.store') }}', {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
              },
              body: JSON.stringify(this.buildPayload()),
            });

            const payload = await this.parseJson(response);

            if (response.status === 409 && payload?.requires_oauth && payload?.redirect_url) {
              this.savePendingConnectionState();
              window.location.href = payload.redirect_url;
              return;
            }

            if (!response.ok) {
              throw new Error(this.extractErrorMessage(payload, 'Repository connection failed.'));
            }

            this.clearPendingConnectionState();
            if ((this.provider?.id === 'github' || this.provider?.id === 'gitlab') && this.authMethod === 'oauth') {
              this.oauthConnections[this.provider.id] = true;
            }

            this.step = 'done';
          } catch (error) {
            this.error = error.message || 'Repository connection failed.';
            this.step = 'details';
          } finally {
            this.loading = false;
          }
        },
        handleFinish() {
          this.closeModal(false);
          this.clearPendingConnectionState();
          sessionStorage.setItem('flash_toast_msg', 'Repository connected successfully.');
          sessionStorage.setItem('flash_toast_type', 'success');
          window.location.reload();
        },

        buildPayload() {
          const isCompanyServer = this.provider?.id === 'company-server';
          const repositoryValue = this.repoUrl.trim();

          return {
            access_token: this.authMethod === 'pat' ? this.token.trim() : null,
            auth_method: this.provider?.id === 'github' || this.provider?.id === 'gitlab'
              ? this.authMethod
              : null,
            name: repositoryValue,
            provider: this.provider?.id,
            server_host: isCompanyServer ? this.host.trim() : null,
            server_path: isCompanyServer ? repositoryValue : null,
            server_protocol: isCompanyServer ? 'SSH' : null,
            url: this.repoUrl.trim(),
          };
        },
        savePendingConnectionState() {
          sessionStorage.setItem(pendingConnectionKey, JSON.stringify({
            host: this.host,
            providerId: this.provider?.id ?? null,
            repoUrl: this.repoUrl,
          }));
        },
        loadPendingConnectionState() {
          const state = sessionStorage.getItem(pendingConnectionKey);

          if (!state) {
            return null;
          }

          try {
            return JSON.parse(state);
          } catch (error) {
            this.clearPendingConnectionState();
            return null;
          }
        },
        clearPendingConnectionState() {
          sessionStorage.removeItem(pendingConnectionKey);
        },
        resumeOAuthFlowIfNeeded() {
          const pendingState = this.loadPendingConnectionState();
          const providerId = this.oauthProvider || new URLSearchParams(window.location.search).get('oauth_provider');

          if (!pendingState || !providerId) {
            return;
          }

          const provider = this.providers.find((item) => item.id === pendingState.providerId);
          this.clearPendingConnectionState();

          if (!provider || provider.id !== providerId) {
            this.removeOAuthProviderQuery();
            return;
          }

          this.openModal();
          this.provider = provider;
          this.authMethod = 'oauth';
          this.host = pendingState.host || '';
          this.repoUrl = pendingState.repoUrl || '';
          this.step = 'details';
          this.oauthConnections[provider.id] = true;
          this.removeOAuthProviderQuery();
          this.emitToast('success', `${provider.name} account connected. Finish choosing the repository to add.`);
        },
        removeOAuthProviderQuery() {
          const url = new URL(window.location.href);
          url.searchParams.delete('oauth_provider');
          window.history.replaceState({}, document.title, `${url.pathname}${url.search}`);
        },
        emitToast(type, message) {
          window.dispatchEvent(new CustomEvent('toast', {
            detail: { message, type },
          }));
        },
        extractErrorMessage(payload, fallback) {
          if (!payload) {
            return fallback;
          }

          if (payload.message) {
            return payload.message;
          }

          if (payload.errors && typeof payload.errors === 'object') {
            const firstError = Object.values(payload.errors).flat()[0];
            if (firstError) {
              return firstError;
            }
          }

          return fallback;
        },
        async parseJson(response) {
          const contentType = response.headers.get('content-type') || '';

          if (!contentType.includes('application/json')) {
            return null;
          }

          return response.json();
        },
        safeParseJson(value) {
          try {
            return JSON.parse(value);
          } catch (error) {
            return null;
          }
        },

        applyMembersPayload(repo, payload) {
          repo.users = Array.isArray(payload.users) ? payload.users : [];
          repo.memberCount = Number(payload.memberCount ?? repo.users.length);
          repo.canManageMembers = Boolean(payload.canManageMembers);
          repo.userSearch = '';
          repo.userSuggestions = [];
          repo.userSearchError = '';
          repo.membersError = '';
        },

        async requestJson(url, options = {}) {
          const response = await fetch(url, {
            ...options,
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': this.csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
              ...(options.headers ?? {}),
            },
          });

          const payload = await this.parseJson(response) ?? {};

          if (!response.ok) {
            throw new Error(this.extractErrorMessage(payload, 'Request failed.'));
          }

          return payload;
        },

        async searchRepositoryUsers(repo) {
          const query = repo.userSearch.trim();
          repo.userSearchError = '';
          repo.userSuggestions = [];

          if (query.length < 2) {
            repo.userSearchLoading = false;
            return;
          }

          const nonce = repo.userSearchNonce + 1;
          repo.userSearchNonce = nonce;
          repo.userSearchLoading = true;

          try {
            const payload = await this.requestJson(`/repositories/${repo.id}/users/search?q=${encodeURIComponent(query)}`, {
              method: 'GET',
            });

            if (repo.userSearchNonce === nonce) {
              repo.userSuggestions = Array.isArray(payload.users) ? payload.users : [];
            }
          } catch (error) {
            if (repo.userSearchNonce === nonce) {
              repo.userSearchError = error.message || 'Could not search users.';
            }
          } finally {
            if (repo.userSearchNonce === nonce) {
              repo.userSearchLoading = false;
            }
          }
        },

        async addRepositoryUser(repo, user) {
          if (!user?.username || repo.userSaving) return;

          repo.userSaving = true;
          repo.membersError = '';

          try {
            const payload = await this.requestJson(`/repositories/${repo.id}/users`, {
              method: 'POST',
              body: JSON.stringify({
                role: repo.userRoleToAdd || 'viewer',
                username: user.username,
              }),
            });

            this.applyMembersPayload(repo, payload);
            this.emitToast('success', 'Member added to repository.');
          } catch (error) {
            repo.membersError = error.message || 'Could not add that member.';
          } finally {
            repo.userSaving = false;
          }
        },

        async updateRepositoryUserRole(repo, user, role) {
          if (!user?.id) return;

          const previousRole = user.role;
          repo.roleSavingId = this.roleSavingKey(user.id);
          repo.membersError = '';

          try {
            const payload = await this.requestJson(`/repositories/${repo.id}/users/${user.id}/role`, {
              method: 'PATCH',
              body: JSON.stringify({ role }),
            });

            this.applyMembersPayload(repo, payload);
            this.emitToast('success', 'Member role updated.');
          } catch (error) {
            user.role = previousRole;
            repo.membersError = error.message || 'Could not update that role.';
          } finally {
            repo.roleSavingId = null;
          }
        },

        async removeRepositoryUser(repo, user) {
          if (!user?.id || repo.removingUserId) return;

          repo.removingUserId = user.id;
          repo.membersError = '';

          try {
            const payload = await this.requestJson(`/repositories/${repo.id}/users/${user.id}`, {
              method: 'DELETE',
            });

            this.applyMembersPayload(repo, payload);
            this.emitToast('success', 'Member removed from repository.');
          } catch (error) {
            repo.membersError = error.message || 'Could not remove that member.';
          } finally {
            repo.removingUserId = null;
          }
        },

        startCredentialEdit(repo, mode) {
          repo.credentialMode = mode;
          repo.credentialsError = '';
          repo.credentialToken = '';
          repo.credentialHost = repo.serverHost || '';
          repo.credentialPath = repo.serverPath || repo.name || '';
          repo.credentialProtocol = repo.serverProtocol || 'SSH';
        },

        cancelCredentialEdit(repo) {
          repo.credentialMode = null;
          repo.credentialsError = '';
          repo.credentialToken = '';
        },

        reconnectOauth(repo) {
          const url = this.oauthReconnectUrls[repo.provider];
          if (url) {
            window.location.href = url;
          }
        },

        async saveRepositoryCredentials(repo) {
          repo.credentialsSaving = true;
          repo.credentialsError = '';

          const body = repo.credentialMode === 'pat'
            ? {
              access_token: repo.credentialToken,
              auth_method: 'pat',
            }
            : {
              server_host: repo.credentialHost,
              server_path: repo.credentialPath,
              server_protocol: repo.credentialProtocol || 'SSH',
            };

          try {
            const payload = await this.requestJson(`/repositories/${repo.id}/credentials`, {
              method: 'PATCH',
              body: JSON.stringify(body),
            });

            if (payload.repository) {
              this.replaceRepository(payload.repository);
            }

            this.emitToast('success', 'Repository connection updated.');
          } catch (error) {
            repo.credentialsError = error.message || 'Could not update repository connection.';
          } finally {
            repo.credentialsSaving = false;
          }
        },

        replaceRepository(repository) {
          const normalized = this.normalizeRepository(repository);
          const index = this.repositories.findIndex((repo) => repo.id === normalized.id);

          if (index >= 0) {
            this.repositories.splice(index, 1, normalized);
          } else {
            this.repositories.unshift(normalized);
          }

          this.selectedId = normalized.id;
        },

        openUploadVersionModal(repo) {
          this.uploadVersionRepository = repo;
          this.uploadVersionFile = null;
          this.uploadVersionError = '';
          this.uploadVersionLoading = false;
          this.uploadVersionProgress = 0;
          this.uploadVersionDropActive = false;
          this.uploadVersionZipLoading = false;
          this.uploadVersionZipProgress = '';
          this.uploadVersionModal = true;
        },

        closeUploadVersionModal() {
          if (this.uploadVersionLoading) return;

          this.uploadVersionModal = false;
          this.uploadVersionRepository = null;
          this.uploadVersionFile = null;
          this.uploadVersionError = '';
          this.uploadVersionProgress = 0;
          this.uploadVersionDropActive = false;
          this.uploadVersionZipLoading = false;
          this.uploadVersionZipProgress = '';
        },

        async handleUploadVersionDrop(event) {
          const items = Array.from(event.dataTransfer?.items || []);
          const files = Array.from(event.dataTransfer?.files || []);

          this.uploadVersionDropActive = false;

          const entries = items
            .map((item) => item.webkitGetAsEntry?.())
            .filter(Boolean);
          const directories = entries.filter((entry) => entry.isDirectory);

          if (directories.length) {
            const fileEntries = [];

            for (const directory of directories) {
              fileEntries.push(...await this.collectUploadVersionFiles(directory));
            }

            await this.zipUploadVersionFiles(fileEntries);
            return;
          }

          const archive = files.find((file) => /\.(zip|bundle)$/i.test(file.name));

          if (archive) {
            this.setUploadVersionArchive(archive);
            return;
          }

          await this.zipUploadVersionLocalFiles(files);
        },

        async handleUploadVersionFolderSelection(event) {
          const files = Array.from(event.target.files || []);
          if (!files.length) return;

          await this.zipUploadVersionLocalFiles(files);
          event.target.value = '';
        },

        handleUploadVersionArchiveSelection(event) {
          const file = event.target.files?.[0];
          if (!file) return;

          if (!/\.(zip|bundle)$/i.test(file.name)) {
            this.uploadVersionError = 'Upload a ZIP archive or Git bundle file.';
            event.target.value = '';
            return;
          }

          this.setUploadVersionArchive(file);
          event.target.value = '';
        },

        setUploadVersionArchive(file) {
          this.uploadVersionFile = file;
          this.uploadVersionError = '';
          this.uploadVersionProgress = 0;
        },

        async zipUploadVersionLocalFiles(files) {
          await this.zipUploadVersionFiles(files.map((file) => ({
            file,
            path: file.webkitRelativePath || file.name,
          })));
        },

        async zipUploadVersionFiles(fileEntries) {
          if (!fileEntries.length) return;

          if (!window.JSZip) {
            this.uploadVersionError = 'The ZIP helper could not load. Try uploading a ZIP archive instead.';
            return;
          }

          this.uploadVersionZipLoading = true;
          this.uploadVersionError = '';
          this.uploadVersionProgress = 0;

          try {
            const zip = new JSZip();
            fileEntries.forEach((entry) => {
              zip.file(entry.path, entry.file);
            });

            const rootName = (fileEntries[0].path || '').split('/')[0] || this.uploadVersionRepository?.name || 'repository';
            const blob = await zip.generateAsync({ type: 'blob' }, (metadata) => {
              this.uploadVersionZipProgress = `Zipping ${fileEntries.length} files... ${Math.round(metadata.percent)}%`;
            });

            this.uploadVersionFile = new File([blob], `${rootName}.zip`, { type: 'application/zip' });
          } catch (error) {
            this.uploadVersionError = 'Could not zip the selected folder.';
          } finally {
            this.uploadVersionZipLoading = false;
          }
        },

        async collectUploadVersionFiles(entry, pathPrefix = '') {
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
            children.map((child) => this.collectUploadVersionFiles(child, `${pathPrefix}${entry.name}/`)),
          );

          return nestedEntries.flat();
        },

        uploadRepositoryVersion() {
          if (!this.uploadVersionRepository || !this.uploadVersionFile || this.uploadVersionLoading) return;

          const repository = this.uploadVersionRepository;
          this.uploadVersionLoading = true;
          this.uploadVersionError = '';
          this.uploadVersionProgress = 0;
          this.syncing = repository.id;

          const formData = new FormData();
          formData.append('file', this.uploadVersionFile);

          const request = new XMLHttpRequest();
          request.open('POST', `/api/repositories/${repository.id}/upload-version`);
          request.setRequestHeader('Accept', 'application/json');
          request.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);

          request.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) return;
            this.uploadVersionProgress = Math.round((event.loaded / event.total) * 100);
          });

          request.onload = () => {
            const payload = this.safeParseJson(request.responseText);

            if (request.status >= 200 && request.status < 300) {
              sessionStorage.setItem('flash_toast_msg', payload?.warning || 'Repository version uploaded successfully.');
              sessionStorage.setItem('flash_toast_type', payload?.warning ? 'warning' : 'success');
              window.location.reload();
              return;
            }

            this.uploadVersionError = this.extractErrorMessage(payload, 'Repository version upload failed.');
            this.uploadVersionLoading = false;
            this.syncing = null;
          };

          request.onerror = () => {
            this.uploadVersionError = 'Repository version upload failed. Check your connection and try again.';
            this.uploadVersionLoading = false;
            this.syncing = null;
          };

          request.send(formData);
        },

        async syncRepo(repoOrId) {
          const id = typeof repoOrId === 'object' ? repoOrId.id : repoOrId;
          const repo = typeof repoOrId === 'object'
            ? repoOrId
            : this.repositories.find((item) => item.id === id);
          const endpoint = repo?.type === 'ssh-mirror'
            ? `/api/repositories/${id}/sync-ssh`
            : `/repositories/${id}/sync`;
          this.syncing = id;

          try {
            const resp = await fetch(endpoint, {
              method: 'POST',
              headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
            });
            const payload = await this.parseJson(resp);

            if (!resp.ok) {
              throw new Error(this.extractErrorMessage(payload, 'Repository sync failed.'));
            }

            sessionStorage.setItem('flash_toast_msg', 'Repository synced successfully.');
            sessionStorage.setItem('flash_toast_type', 'success');
            window.location.reload();
          } catch (error) {
            this.emitToast('error', error.message || 'Repository sync failed.');
          } finally {
            this.syncing = null;
          }
        },
        async removeRepo(repoOrId) {
          const id = typeof repoOrId === 'object' ? repoOrId.id : repoOrId;
          if (!confirm('Remove this repository?')) return;

          try {
            const resp = await fetch(`/repositories/${id}`, {
              method: 'DELETE',
              headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
            });
            const payload = await this.parseJson(resp);

            if (!resp.ok) {
              throw new Error(this.extractErrorMessage(payload, 'Repository removal failed.'));
            }

            sessionStorage.setItem('flash_toast_msg', 'Repository removed.');
            sessionStorage.setItem('flash_toast_type', 'success');
            window.location.reload();
          } catch (error) {
            this.emitToast('error', error.message || 'Repository removal failed.');
          }
        },
      };
    }
  </script>
@endpush