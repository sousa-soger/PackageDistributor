@extends('layouts.app')

@section('title', 'Repositories')
@section('subtitle', 'GitHub, GitLab, company servers and local repositories.')

@section('topbar_actions')
  <button onclick="window.dispatchEvent(new CustomEvent('open-repo-modal'))"
    class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft hover:shadow-glow hover:brightness-[1.03] active:brightness-95 transition-base h-9 rounded-md px-3"
  >
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
    Connect Repository
  </button>
@endsection

@section('content')
<div class="animate-fade-in px-4 sm:px-6 lg:px-8 py-6 lg:py-8"
     x-data="repositoriesPage({
       oauthConnections: @js($oauthConnections),
       oauthProvider: @js(request('oauth_provider')),
       projects: @js($projects)
     })"
     x-init="init()">

  {{-- ── Empty state ─────────────────────────────────────────────────────── --}}
  @if($repositories->isEmpty())
  <div class="section-card p-12 text-center">
    <div class="mx-auto mb-5 h-16 w-16 rounded-2xl brand-soft-bg flex items-center justify-center">
      <svg class="h-8 w-8" style="color:hsl(var(--primary))"
           fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
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
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
      </svg>
      Connect Repository
    </button>
  </div>
  @endif

  {{-- ── Repository grid ─────────────────────────────────────────────────── --}}
  @if($repositories->isNotEmpty())
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="repo-grid">
    @foreach($repositories as $repo)
    <div class="section-card p-5 group"
         id="repo-card-{{ $repo->id }}">

      {{-- Card header --}}
      <div class="flex items-start justify-between gap-3 mb-3">

        {{-- Provider icon --}}
        <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center flex-shrink-0"
             style="color:hsl(var(--primary))">
          @if($repo->provider === 'github')
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12"/>
            </svg>
          @elseif($repo->provider === 'gitlab')
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 01-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 014.82 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.49h8.1l2.44-7.51A.42.42 0 0118.6 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.51L23 13.45a.84.84 0 01-.35.94z"/>
            </svg>
          @elseif($repo->provider === 'company-server')
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 12H3a2 2 0 00-2 2v4a2 2 0 002 2h18a2 2 0 002-2v-4a2 2 0 00-2-2h-2M12 2v14m0-14l-4 4m4-4l4 4"/>
            </svg>
          @else
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
          @endif
        </div>

        {{-- Status badge --}}
        @php
          $stCss = match($repo->status) {
            'connected'  => 'background:hsl(var(--success)/0.10);color:hsl(var(--success));border-color:hsl(var(--success)/0.30)',
            'expired'    => 'background:hsl(var(--queued)/0.10);color:hsl(var(--queued));border-color:hsl(var(--queued)/0.30)',
            'needs-auth' => 'background:hsl(var(--failed)/0.10);color:hsl(var(--failed));border-color:hsl(var(--failed)/0.30)',
            default      => 'background:hsl(var(--inactive)/0.10);color:hsl(var(--inactive));border-color:hsl(var(--inactive)/0.30)',
          };
          $stLabel = match($repo->status) {
            'connected'  => 'Connected',
            'expired'    => 'Expired',
            'needs-auth' => 'Needs auth',
            default      => ucfirst($repo->status),
          };
        @endphp
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border" style="{{ $stCss }}">
          {{ $stLabel }}
        </span>
      </div>

      {{-- Repo name --}}
      <div class="text-sm font-semibold truncate" style="color:hsl(var(--foreground))">
        {{ $repo->label }}
      </div>
      <div class="text-xs mt-0.5" style="color:hsl(var(--muted-foreground))">
        {{ ucfirst(str_replace('-', ' ', $repo->provider)) }}
        @if($repo->server_host) · {{ $repo->server_host }} @endif
      </div>

      {{-- Metadata pills --}}
      <div class="mt-3 flex flex-wrap gap-1.5">
        <span class="text-[10px] font-mono px-2 py-0.5 rounded"
              style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))">
          {{ $repo->branch_count }} branches
        </span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded"
              style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))">
          {{ $repo->tag_count }} tags
        </span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded"
              style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))">
          default · {{ $repo->default_branch }}
        </span>
      </div>

      {{-- Actions (shown on hover) --}}
      <div class="mt-4 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-base">
        {{-- Sync --}}
        <button
          @click="syncRepo({{ $repo->id }})"
          :disabled="syncing === {{ $repo->id }}"
          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-base"
          style="background:hsl(var(--secondary));color:hsl(var(--foreground))"
          title="Sync branches & tags">
          <svg class="h-3.5 w-3.5" :class="syncing === {{ $repo->id }} ? 'animate-spin' : ''"
               fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          <span x-text="syncing === {{ $repo->id }} ? 'Syncing…' : 'Sync'"></span>
        </button>

        {{-- Remove --}}
        <button
          @click="removeRepo({{ $repo->id }})"
          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-base"
          style="background:hsl(var(--failed)/0.08);color:hsl(var(--failed))"
          title="Remove repository">
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
          </svg>
          Remove
        </button>
      </div>
    </div>
    @endforeach

    {{-- "Add another" card --}}
    <button @click="window.dispatchEvent(new CustomEvent('open-repo-modal'))"
            class="section-card p-5 border-dashed flex flex-col items-center justify-center gap-3 min-h-[160px] transition-spring hover:shadow-soft cursor-pointer w-full text-center"
            style="border-style:dashed;border-color:hsl(var(--border))">
      <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center"
           style="color:hsl(var(--primary))">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
      </div>
      <div>
        <p class="text-sm font-semibold" style="color:hsl(var(--foreground))">Connect Repository</p>
        <p class="text-xs mt-0.5" style="color:hsl(var(--muted-foreground))">GitHub, GitLab, or custom server</p>
      </div>
    </button>
  </div>
  @endif

  @include('_partials.create-repository-modal')

</div>{{-- /page --}}

@endsection

@push('scripts')
<script>
function repositoriesPage(config = {}) {
  const pendingConnectionKey = 'repositories.pending-connection';

  return {
    modal: false,
    step: 'provider',
    provider: null,
    loading: false,
    syncing: null,
    error: '',
    oauthConnections: config.oauthConnections ?? {},
    oauthProvider: config.oauthProvider ?? null,
    projects: config.projects ?? [],

    authMethod: 'oauth',
    token: '',
    host: '',
    localPath: '',
    projectId: '',
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

    init() {
      if (sessionStorage.getItem('flash_toast_msg')) {
        const msg = sessionStorage.getItem('flash_toast_msg');
        const type = sessionStorage.getItem('flash_toast_type');
        sessionStorage.removeItem('flash_toast_msg');
        sessionStorage.removeItem('flash_toast_type');
        setTimeout(() => window.dispatchEvent(
          new CustomEvent('toast', { detail: { type, message: msg } })
        ), 50);
      }

      window.addEventListener('open-repo-modal', () => this.openModal());
      this.resumeOAuthFlowIfNeeded();

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.modal && this.step !== 'verifying') {
          this.closeModal(false);
        }
      });
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
      this.projectId = '';
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
        project_id: this.projectId || null,
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
        projectId: this.projectId,
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
      this.projectId = pendingState.projectId || '';
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

    async syncRepo(id) {
      this.syncing = id;

      try {
        const resp = await fetch(`/repositories/${id}/sync`, {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
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
    async removeRepo(id) {
      if (!confirm('Remove this repository?')) return;

      try {
        const resp = await fetch(`/repositories/${id}`, {
          method: 'DELETE',
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
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
