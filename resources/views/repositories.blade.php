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

    {{-- Empty State --}}
    @if($repositories->isEmpty())
      <x-repository.empty-state />
    @endif

    {{-- Show Repo --}}
    @if($repositories->isNotEmpty())
      <x-repository.toolbar />
      <x-repository.card-view />
      <x-repository.list-view />
    @endif
    {{-- Repository detail bottom-sheet --}}
    <x-repository.bottom-sheet />

    {{-- Upload version modal --}}
    <x-repository.upload-version-modal />

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
          const members = Number(repo?.memberCount ?? 0);
          const ownerOffset = repo?.ownerName ? 1 : 0;
          const count = members + ownerOffset;
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