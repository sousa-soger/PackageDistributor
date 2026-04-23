@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    <div x-data="{
        gitlabOauth: @js($gitlabConnected),
        activeTab: 'all',
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
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">

                {{-- Tabs --}}
                <div class="flex border-b border-slate-200 px-2 pt-1">
                    <button
                        @click="activeTab = 'personal'"
                        :class="activeTab === 'personal' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-2.5 text-sm border-b-2 transition-colors -mb-px"
                    >My Personal Projects</button>
                    <button
                        @click="activeTab = 'shared'"
                        :class="activeTab === 'shared' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-2.5 text-sm border-b-2 transition-colors -mb-px"
                    >Shared with Me</button>
                    <button
                        @click="activeTab = 'all'"
                        :class="activeTab === 'all' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-2.5 text-sm border-b-2 transition-colors -mb-px"
                    >All Projects</button>
                    <div x-show="activeTab === 'all'" class="ml-auto px-4 py-2.5 flex items-center gap-2 cursor-pointer -mb-px border-b-2 border-transparent" @click="toggle = !toggle">
                        <input type="checkbox" x-model="toggle" class="cursor-pointer pointer-events-none accent-orange-500">
                        <span class="text-sm text-slate-500 whitespace-nowrap">Include SAINS Public Internal Projects</span>
                    </div>
                </div>

                {{-- Project Rows --}}
                <div class="divide-y divide-slate-100">
                    <template x-for="project in filteredProjects" :key="project.id">
                        <div class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition-colors">

                            {{-- Icon --}}
                            <div class="shrink-0 w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                <template x-if="project.icon === 'gitlab'">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 28 26" class="w-5 h-5"><path fill="#E24329" d="m24.507 9.5-.034-.09L21.082.562a.896.896 0 0 0-1.694.091l-2.29 7.01H7.825L5.535.653a.898.898 0 0 0-1.694-.09L.451 9.411.416 9.5a6.297 6.297 0 0 0 2.09 7.278l.012.01.03.022 5.16 3.867 2.56 1.935 1.554 1.176a1.051 1.051 0 0 0 1.268 0l1.555-1.176 2.56-1.935 5.197-3.89.014-.01A6.297 6.297 0 0 0 24.507 9.5Z"/><path fill="#FC6D26" d="m24.507 9.5-.034-.09a11.44 11.44 0 0 0-4.56 2.051l-7.447 5.632 4.742 3.584 5.197-3.89.014-.01A6.297 6.297 0 0 0 24.507 9.5Z"/><path fill="#FCA326" d="m7.707 20.677 2.56 1.935 1.555 1.176a1.051 1.051 0 0 0 1.268 0l1.555-1.176 2.56-1.935-4.743-3.584-4.755 3.584Z"/><path fill="#FC6D26" d="M5.01 11.461a11.43 11.43 0 0 0-4.56-2.05L.416 9.5a6.297 6.297 0 0 0 2.09 7.278l.012.01.03.022 5.16 3.867 4.745-3.584-7.444-5.632Z"/></svg>
                                </template>
                            </div>

                            {{-- Name + Description --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-bold text-slate-800 leading-tight" x-text="project.name"></p>
                                    <span class="text-xs text-slate-400 font-mono truncate" x-text="project.path"></span>
                                    {{-- Explore badge --}}
                                    <template x-if="project.source === 'explore'">
                                        <span class="inline-flex items-center rounded-full bg-orange-50 border border-orange-200 px-2 py-0.5 text-xs font-medium text-orange-600">SAINS Internal</span>
                                    </template>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5" x-text="project.description"></p>
                            </div>

                            {{-- Meta: last activity + visibility --}}
                            <div class="hidden md:flex items-center gap-4 shrink-0">
                                <span class="text-xs text-slate-400 whitespace-nowrap">
                                    <span class="text-slate-600 font-medium" x-text="timeAgo(project.lastActivity)"></span>
                                </span>

                                {{-- Internal --}}
                                <template x-if="project.visibility === 'internal'">
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path d="M16 11V7a4 4 0 0 0-8 0v4"/><rect x="3" y="11" width="18" height="11" rx="2"/></svg>
                                        Internal
                                    </span>
                                </template>
                                {{-- Public --}}
                                <template x-if="project.visibility === 'public'">
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>
                                        Public
                                    </span>
                                </template>
                                {{-- Private --}}
                                <template x-if="project.visibility === 'private'">
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        Private
                                    </span>
                                </template>
                            </div>

                            {{-- Action buttons --}}
                            <div class="flex items-center gap-2 shrink-0">
                                <a :href="project.web_url" target="_blank" rel="noopener noreferrer" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition">Go to Project</a>
                            </div>

                        </div>

                    </template>
                   
                    <template x-if="loading">
                        <div class="rounded-xl border py-16 text-center relative z-10 flex flex-col justify-center">
                            <span class="font-medium text-blue-900 animate-pulse">Loading Your GitLab Projects...</span>
                        </div>
                    </template>
                    <template x-if="loadingExplore">
                        <div class="rounded-xl border py-16 text-center relative z-10 flex flex-col justify-center">
                            <span class="font-medium text-blue-900 animate-pulse">Loading SAINS Internal GitLab Projects...</span>
                        </div>
                    </template>

                    {{-- Empty state --}}
                    <template x-if="!loading && !loadingExplore && filteredProjects.length === 0">
                        <div class="py-16 text-center text-sm text-slate-400">No projects found.</div>
                    </template>
                </div>

            </div>
        </div>

    </div>

    
@endsection

@push('scripts')
<script>
</script>
@endpush
