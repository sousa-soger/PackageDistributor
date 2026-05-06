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
          Supports GitHub, GitLab, company servers, and local paths.
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

    {{-- ── Repository grid ─────────────────────────────────────────────────── --}}
    @if($repositories->isNotEmpty())
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="repo-grid">
        <template x-for="repo in repositories" :key="repo.id">
          <article @click="setSelected(repo.id)"
            class="section-card p-5 group cursor-pointer text-left relative overflow-hidden transition-all duration-300 hover:shadow-soft"
            :class="selectedId === repo.id ? 'ring-[1px] ring-primary shadow-[0_0_0_4px_hsl(var(--primary)/0.25)] opacity-70' : ''">
            <div class="absolute -top-12 -right-12 h-32 w-32 rounded-full bg-primary/10 blur-2xl pointer-events-none"></div>

            <div class="relative">
              <div class="flex items-start justify-between gap-3 mb-3">
                <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center flex-shrink-0 text-primary"
                  x-html="providerIcon(repo.provider)"></div>

                <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border" :class="statusBadgeClass(repo.status)"
                  x-text="repo.statusLabel"></span>
              </div>

              <div class="text-sm font-semibold truncate" style="color:hsl(var(--foreground))" x-text="repo.label"></div>
              <div class="text-xs mt-0.5 truncate" style="color:hsl(var(--muted-foreground))">
                <span x-text="repo.providerLabel"></span>
                <span x-show="repo.serverHost" x-text="` - ${repo.serverHost}`"></span>
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
                  <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7 9 18l-5-5" />
                  </svg>
                  Package
                </a>

                <button type="button" x-show="repo.canManageRepository" @click.stop="syncRepo(repo)" :disabled="syncing === repo.id"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-base"
                  style="background:hsl(var(--secondary));color:hsl(var(--foreground))" title="Sync branches and tags">
                  <svg class="h-3.5 w-3.5" :class="syncing === repo.id ? 'animate-spin' : ''" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  <span x-text="syncing === repo.id ? 'Syncing...' : 'Sync'"></span>
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
                      class="rounded-lg brand-soft-bg shadow-soft flex items-center justify-center shrink-0 h-12 w-12 text-primary"
                      x-html="providerIcon(selectedRepository.provider)"></div>
                    <div class="flex items-center gap-1" @click.stop>
                      <a x-show="selectedRepository.canCreatePackage" :href="createPackageUrl(selectedRepository)"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm text-primary hover:bg-accent hover:text-accent-foreground transition-colors"
                        title="Create package">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M20 7 9 18l-5-5" />
                        </svg>
                      </a>
                      <button type="button" x-show="selectedRepository.canManageRepository" @click="syncRepo(selectedRepository)"
                        :disabled="syncing === selectedRepository.id"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm hover:bg-accent hover:text-accent-foreground transition-colors disabled:opacity-50"
                        title="Sync repository">
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
                  <div class="text-base font-semibold" x-text="selectedRepository.label"></div>
                  <div class="text-xs text-muted-foreground mt-1"
                    x-text="selectedRepository.url || selectedRepository.name"></div>
                  <div class="mt-3 flex flex-wrap items-center gap-3 text-[11px] text-muted-foreground">
                    <span class="inline-flex items-center gap-1">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="6" x2="6" y1="3" y2="15" />
                        <circle cx="18" cy="6" r="3" />
                        <circle cx="6" cy="18" r="3" />
                        <path d="M18 9a9 9 0 0 1-9 9" />
                      </svg>
                      <span x-text="selectedRepository.branchCount + ' branches'"></span>
                    </span>
                    <span class="inline-flex items-center gap-1">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 12V8H6a2 2 0 0 1 0-4h12v4" />
                        <path d="M4 6v12a2 2 0 0 0 2 2h14v-4" />
                        <path d="M18 12h4v4h-4z" />
                      </svg>
                      <span x-text="selectedRepository.tagCount + ' tags'"></span>
                    </span>
                    <span class="inline-flex items-center gap-1">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                      </svg>
                      <span x-text="memberLabel(selectedRepository)"></span>
                    </span>
                    <span class="ml-auto text-[11px] font-medium px-2 py-0.5 rounded-md border"
                      :class="statusBadgeClass(selectedRepository.status)" x-text="selectedRepository.statusLabel"></span>
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
                      <button type="button" @click="syncRepo(selectedRepository)"
                        :disabled="syncing === selectedRepository.id"
                        class="w-full inline-flex h-9 items-center justify-center gap-2 rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent disabled:opacity-50">
                        <svg class="h-3.5 w-3.5" :class="syncing === selectedRepository.id ? 'animate-spin' : ''"
                          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span x-text="syncing === selectedRepository.id ? 'Syncing...' : 'Sync now'"></span>
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

                      <template x-if="selectedRepository.provider === 'local-pc'">
                        <button type="button" @click="startCredentialEdit(selectedRepository, 'path')"
                          class="w-full inline-flex h-9 items-center justify-center rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent">
                          Change local path
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

                    <div x-show="selectedRepository.credentialMode === 'path'"
                      class="mt-4 rounded-lg border border-border/60 p-3 space-y-3">
                      <label class="block text-xs font-medium">Local folder path</label>
                      <input type="text" x-model="selectedRepository.credentialPath"
                        class="h-9 w-full rounded-md border border-border bg-background px-3 text-xs outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20"
                        placeholder="/Users/you/code/repository">
                      <div class="flex gap-2">
                        <button type="button" @click="saveRepositoryCredentials(selectedRepository)"
                          :disabled="selectedRepository.credentialsSaving || !selectedRepository.credentialPath"
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
        modal: false,
        step: 'provider',
        provider: null,
        loading: false,
        syncing: null,
        error: '',
        roleOptions: Array.isArray(config.roleOptions) ? config.roleOptions : [],
        csrfToken: config.csrfToken ?? '',
        createPackageBaseUrl: config.createPackageBaseUrl ?? '/create-package',
        oauthReconnectUrls: config.oauthReconnectUrls ?? {},
        oauthConnections: config.oauthConnections ?? {},
        oauthProvider: config.oauthProvider ?? null,

        authMethod: 'oauth',
        token: '',
        host: '',
        localPath: '',
        repoUrl: '',

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
            description: 'Index a local repository folder via the Cybix agent.',
            authMethod: 'path',
            authLabel: 'Local agent + folder path',
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
          if (this.provider?.authMethod === 'path') return this.localPath.trim() !== '';
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
            canManageMembers: Boolean(repo.canManageMembers),
            canManageRepository: Boolean(repo.canManageRepository),
            membersError: '',
            userSearch: '',
            userSuggestions: [],
            userSearchLoading: false,
            userSearchError: '',
            userSearchNonce: 0,
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
            return `<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12" /></svg>`;
          }

          if (provider === 'gitlab') {
            return `<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 01-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 014.82 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.49h8.1l2.44-7.51A.42.42 0 0118.6 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.51L23 13.45a.84.84 0 01-.35.94z" /></svg>`;
          }

          if (provider === 'company-server') {
            return `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect><rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect><line x1="6" x2="6.01" y1="6" y2="6"></line><line x1="6" x2="6.01" y1="18" y2="18"></line></svg>`;
          }

          return `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="22" x2="2" y1="12" y2="12"></line><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path><line x1="6" x2="6.01" y1="16" y2="16"></line><line x1="10" x2="10.01" y1="16" y2="16"></line></svg>`;
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
          this.localPath = '';
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
          const isLocal = this.provider?.id === 'local-pc';
          const isCompanyServer = this.provider?.id === 'company-server';
          const repositoryValue = isLocal ? this.localPath.trim() : this.repoUrl.trim();

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
            url: isLocal ? this.localPath.trim() : this.repoUrl.trim(),
          };
        },
        savePendingConnectionState() {
          sessionStorage.setItem(pendingConnectionKey, JSON.stringify({
            host: this.host,
            localPath: this.localPath,
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
          this.localPath = pendingState.localPath || '';
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

          if (repo.credentialMode === 'path') {
            body.server_path = repo.credentialPath;
          }

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

        async syncRepo(repoOrId) {
          const id = typeof repoOrId === 'object' ? repoOrId.id : repoOrId;
          this.syncing = id;

          try {
            const resp = await fetch(`/repositories/${id}/sync`, {
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
