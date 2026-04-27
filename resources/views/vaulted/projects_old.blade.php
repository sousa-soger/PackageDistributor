@extends('layouts.app')

@section('title', 'Repositories')
@section('subtitle', 'GitHub, GitLab, company servers and local repositories.')

@section('topbar_actions')
    <a href="{{ route('create-package') }}">
        <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft hover:shadow-glow hover:brightness-[1.03] active:brightness-95 transition-base h-9 rounded-md px-3">
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
    </a>
@endsection

@section('content')
    <div x-data="{
        gitlabOauth: @js($gitlabConnected),
        activeTab: 'projects',   // 'projects' | 'personal' | 'shared' | 'all'
        search: '',
        loading: false,
        loadingExplore: false,
        error: '',
        projects: [],
        exploreProjects: [],
        toggle: false,

        init() {
            // Restore toggle from localStorage
            const saved = localStorage.getItem('gitlab_show_explore');
            this.toggle = saved === 'true';

            // Persist toggle changes to localStorage and lazy-load explore projects
            this.$watch('toggle', val => {
                localStorage.setItem('gitlab_show_explore', val);
                if (val && this.exploreProjects.length === 0 && !this.loadingExplore) {
                    this.loadExploreProjects();
                }
            });

            this.loadProjects().then(() => {
                // If toggle was restored as true, also load explore projects on init
                if (this.toggle && this.exploreProjects.length === 0) {
                    this.loadExploreProjects();
                }
            });
        },

        timeAgo(dateStr) {
            const now = new Date();
            const then = new Date(dateStr);
            const secs = Math.floor((now - then) / 1000);
            if (secs < 60) return 'just now';
            const mins = Math.floor(secs / 60);
            if (mins < 60) return mins + ' minute' + (mins > 1 ? 's' : '') + ' ago';
            const hrs = Math.floor(mins / 60);
            if (hrs < 24) return hrs + ' hour' + (hrs > 1 ? 's' : '') + ' ago';
            const days = Math.floor(hrs / 24);
            if (days < 30) return days + ' day' + (days > 1 ? 's' : '') + ' ago';
            const months = Math.floor(days / 30);
            if (months < 12) return months + ' month' + (months > 1 ? 's' : '') + ' ago';
            const years = Math.floor(months / 12);
            return years + ' year' + (years > 1 ? 's' : '') + ' ago';
        },

        async loadProjects() {
            if (!this.gitlabOauth) return;
            this.loading = true;
            this.error = '';
            try {
                const response = await fetch('{{ route('gitlab.projects') }}', {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to load GitLab projects.');
                this.projects = data;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async loadExploreProjects() {
            if (!this.gitlabOauth) return;
            this.loadingExplore = true;
            try {
                const response = await fetch('{{ route('gitlab.explore') }}', {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to load explore projects.');
                // Deduplicate against member projects
                const memberIds = new Set(this.projects.map(p => p.id));
                this.exploreProjects = data.filter(p => !memberIds.has(p.id));
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loadingExplore = false;
            }
        },

        get allVisibleProjects() {
            if (this.activeTab === 'all' && this.toggle) {
                return [...this.projects, ...this.exploreProjects];
            }
            return this.projects;
        },

        get filteredProjects() {
            return this.allVisibleProjects.filter(p => {
                const matchesSearch = this.search === '' ||
                    p.name.toLowerCase().includes(this.search.toLowerCase()) ||
                    p.path.toLowerCase().includes(this.search.toLowerCase());

                const matchesTab = this.activeTab === 'all' ||
                    this.activeTab === 'projects' ||
                    p.category === this.activeTab;

                return matchesSearch && matchesTab;
            });
        }
    }" x-init="init()">
        <div x-show="!gitlabOauth" x-cloak class="min-h-[calc(100vh-4rem)] flex items-center justify-center flex-col space-y-2">
            <p class="text-xl text-slate-600 font-medium tracking-wide pb-6">Sign in to GitLab</p>
            <p class="text-sm text-slate-400 font-medium tracking-wide pb-2">Sign in to GitLab to view repositories</p>
            <a
                href="{{ route('gitlab.oauth.redirect') }}"
                class="group flex items-center gap-4 py-1 px-6 rounded-[24px] bg-white border border-slate-200 shadow-xl shadow-slate-200/50 hover:shadow-2xl hover:shadow-orange-500/10 transition-all duration-300 hover:scale-[1.02]"
            >
                <div class="flex items-center justify-center w-12 h-12 rounded-2xl text-orange-600 transition-transform duration-300 group-hover:scale-110"> 
                    <span aria-hidden="true" data-testid="brand-header-default-logo">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 28 26" height="26" width="28" class="tanuki-logo" role="img" aria-hidden="true">
                            <path fill="#E24329" d="m24.507 9.5-.034-.09L21.082.562a.896.896 0 0 0-1.694.091l-2.29 7.01H7.825L5.535.653a.898.898 0 0 0-1.694-.09L.451 9.411.416 9.5a6.297 6.297 0 0 0 2.09 7.278l.012.01.03.022 5.16 3.867 2.56 1.935 1.554 1.176a1.051 1.051 0 0 0 1.268 0l1.555-1.176 2.56-1.935 5.197-3.89.014-.01A6.297 6.297 0 0 0 24.507 9.5Z" class="tanuki-shape tanuki"></path>
                            <path fill="#FC6D26" d="m24.507 9.5-.034-.09a11.44 11.44 0 0 0-4.56 2.051l-7.447 5.632 4.742 3.584 5.197-3.89.014-.01A6.297 6.297 0 0 0 24.507 9.5Z" class="tanuki-shape right-cheek"></path>
                            <path fill="#FCA326" d="m7.707 20.677 2.56 1.935 1.555 1.176a1.051 1.051 0 0 0 1.268 0l1.555-1.176 2.56-1.935-4.743-3.584-4.755 3.584Z" class="tanuki-shape chin"></path>
                            <path fill="#FC6D26" d="M5.01 11.461a11.43 11.43 0 0 0-4.56-2.05L.416 9.5a6.297 6.297 0 0 0 2.09 7.278l.012.01.03.022 5.16 3.867 4.745-3.584-7.444-5.632Z" class="tanuki-shape left-cheek"></path>
                        </svg>
                    </span>
                </div>
                <div class="text-left leading-tight">
                    <span class="block text-lg text-slate-900">Connect to GitLab</span>
                </div>
            </a>
            <p class="text-xs text-slate-400 font-medium tracking-wide uppercase pb-6">Authenticate with OAuth</p>
        </div>

        <div class="max-w-6xl mx-auto px-6 py-6 space-y-6" x-show="gitlabOauth" x-cloak>

            {{-- Header --}}
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Projects Dashboard</h1>
            </div>

            @if ($gitlabConnected)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 flex items-center justify-between">
                    <span>Connected to GitLab as <span class="font-semibold">{{ $gitlabUsername }}</span></span>
                    <form action="{{ route('gitlab.oauth.disconnect') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Disconnect</button>
                    </form>
                </div>
            @endif

            <div x-show="error" x-text="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600"></div>

            {{-- Search --}}
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                    <svg class="w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    x-model="search"
                    placeholder="Search projects..."
                    class="w-full max-w-md rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-700 placeholder-slate-400 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
            </div>

            {{-- Main Card --}}
            <x-gitlab-project-browser 
                :with-members="true" 
                projects-var="filteredProjects" 
                loading-var="loading" 
                loading-explore-var="loadingExplore" 
            />
        </div>

    </div>

    
@endsection

@push('scripts')
<script>
</script>
@endpush
