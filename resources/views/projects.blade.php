@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    <div x-data="{
        gitlabOauth: true,
        activeTab: 'all',
        search: '',
        projects: [
            {
                id: 1,
                icon: 'monitor',
                name: 'Thrive Game',
                path: 'User/Thrive',
                description: 'Main development repo for Thrive.',
                lastActivity: '3h ago',
                visibility: 'public',
                role: null,
                category: 'personal',
            },
            {
                id: 2,
                icon: 'box',
                name: 'CybixCore Lib',
                path: 'System/CybixCore',
                description: 'Core library.',
                lastActivity: '1d ago',
                visibility: 'private',
                role: null,
                category: 'shared',
            },
            {
                id: 3,
                icon: 'gitlab',
                name: 'Test Suite',
                path: 'CybixCorp/Test',
                description: 'CI/CD testing.',
                lastActivity: '2d ago',
                visibility: 'private',
                role: null,
                category: 'all',
            },
            {
                id: 4,
                icon: 'person-box',
                name: 'UI Designs',
                path: 'ProjectTeam/UIDesigns',
                description: 'Shared assets. [ProjectTeam]',
                lastActivity: '1d ago',
                visibility: 'role',
                role: 'Viewer',
                category: 'shared',
            },
            {
                id: 5,
                icon: 'group',
                name: 'Marketing Assets',
                path: 'Marketers/Marketing',
                description: 'Campaign files.',
                lastActivity: '5h ago',
                visibility: 'role',
                role: 'Collaborator',
                category: 'all',
            },
        ],
        get filteredProjects() {
            return this.projects.filter(p => {
                const matchesSearch = this.search === '' ||
                    p.name.toLowerCase().includes(this.search.toLowerCase()) ||
                    p.path.toLowerCase().includes(this.search.toLowerCase());
                const matchesTab = this.activeTab === 'all' ||
                    p.category === this.activeTab ||
                    p.category === 'all';
                return matchesSearch && matchesTab;
            });
        }
    }">
        <div x-show="!gitlabOauth" x-cloak class="min-h-[calc(100vh-4rem)] flex items-center justify-center flex-col space-y-2">
            <p class="text-xl text-slate-600 font-medium tracking-wide pb-6">Sign in to GitLab</p>
            <p class="text-sm text-slate-400 font-medium tracking-wide pb-2">Sign in to GitLab to view repositories</p>
            <button class="group flex items-center gap-4 py-1 px-6 rounded-[24px] bg-white border border-slate-200 shadow-xl shadow-slate-200/50 hover:shadow-2xl hover:shadow-orange-500/10 transition-all duration-300 hover:scale-[1.02]">
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
            </button>
            <p class="text-xs text-slate-400 font-medium tracking-wide uppercase pb-6">Authenticate with OAuth</p>
        </div>

        <div class="max-w-6xl mx-auto px-6 py-6 space-y-6" x-show="gitlabOauth" x-cloak>

            {{-- Header --}}
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Projects Dashboard</h1>
            </div>

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
                </div>

                {{-- Project Rows --}}
                <div class="divide-y divide-slate-100">
                    <template x-for="project in filteredProjects" :key="project.id">
                        <div class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition-colors">

                            {{-- Icon --}}
                            <div class="shrink-0 w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                {{-- Monitor --}}
                                <template x-if="project.icon === 'monitor'">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                                </template>
                                {{-- Box --}}
                                <template x-if="project.icon === 'box'">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path d="M21 8.5 12 14 3 8.5"/><path d="M3 8.5V19a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.5L12 3 3 8.5Z"/><path d="M12 14v8"/></svg>
                                </template>
                                {{-- GitLab Fox --}}
                                <template x-if="project.icon === 'gitlab'">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 28 26" class="w-5 h-5"><path fill="#E24329" d="m24.507 9.5-.034-.09L21.082.562a.896.896 0 0 0-1.694.091l-2.29 7.01H7.825L5.535.653a.898.898 0 0 0-1.694-.09L.451 9.411.416 9.5a6.297 6.297 0 0 0 2.09 7.278l.012.01.03.022 5.16 3.867 2.56 1.935 1.554 1.176a1.051 1.051 0 0 0 1.268 0l1.555-1.176 2.56-1.935 5.197-3.89.014-.01A6.297 6.297 0 0 0 24.507 9.5Z"/><path fill="#FC6D26" d="m24.507 9.5-.034-.09a11.44 11.44 0 0 0-4.56 2.051l-7.447 5.632 4.742 3.584 5.197-3.89.014-.01A6.297 6.297 0 0 0 24.507 9.5Z"/><path fill="#FCA326" d="m7.707 20.677 2.56 1.935 1.555 1.176a1.051 1.051 0 0 0 1.268 0l1.555-1.176 2.56-1.935-4.743-3.584-4.755 3.584Z"/><path fill="#FC6D26" d="M5.01 11.461a11.43 11.43 0 0 0-4.56-2.05L.416 9.5a6.297 6.297 0 0 0 2.09 7.278l.012.01.03.022 5.16 3.867 4.745-3.584-7.444-5.632Z"/></svg>
                                </template>
                                {{-- Person + Box --}}
                                <template x-if="project.icon === 'person-box'">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><circle cx="9" cy="7" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6h2"/><rect x="13" y="13" width="8" height="7" rx="1"/><path d="M16 13v-2a2 2 0 0 1 4 0v2"/></svg>
                                </template>
                                {{-- Group --}}
                                <template x-if="project.icon === 'group'">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><circle cx="9" cy="7" r="3"/><circle cx="17" cy="8" r="2"/><path d="M3 20c0-3.3 2.7-6 6-6h2c3.3 0 6 2.7 6 6"/><path d="M19 14c1.7.6 3 2.2 3 4"/></svg>
                                </template>
                            </div>

                            {{-- Name + Description --}}
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-slate-800 leading-tight">
                                    <span x-text="project.name + ' (' + project.path + ')'"></span>
                                    <span class="font-normal text-slate-500" x-text="' /' + project.path"></span>
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5" x-text="project.description"></p>
                            </div>

                            {{-- Meta: last activity + visibility --}}
                            <div class="hidden md:flex items-center gap-4 shrink-0">
                                <span class="text-xs text-slate-400 whitespace-nowrap">
                                    Last Activity: <span class="text-slate-600 font-medium" x-text="project.lastActivity"></span>
                                </span>

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
                                {{-- Role badge --}}
                                <template x-if="project.visibility === 'role'">
                                    <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-600 font-medium">
                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path d="M17 20h5v-1a4 4 0 0 0-5.4-3.7"/><path d="M9 20H4v-1a4 4 0 0 1 5.4-3.7"/><circle cx="12" cy="8" r="4"/><path d="M22 20v-1a4 4 0 0 0-3-3.9"/><path d="M2 20v-1a4 4 0 0 1 3-3.9"/></svg>
                                        Role: <span x-text="project.role"></span>
                                    </span>
                                </template>
                            </div>

                            {{-- Action buttons --}}
                            <div class="flex items-center gap-2 shrink-0">
                                {{-- Public gets Manage Shares too --}}
                                <template x-if="project.visibility === 'public'">
                                    <button class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">Manage Shares</button>
                                </template>
                                {{-- Non-Collaborator shows View Details first --}}
                                <template x-if="project.visibility !== 'role' || project.role !== 'Collaborator'">
                                    <button class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">View Details</button>
                                </template>
                                <button class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition">Go to Project</button>
                                {{-- Collaborator gets View Details after--}}
                                <template x-if="project.visibility === 'role' && project.role === 'Collaborator'">
                                    <button class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">View Details</button>
                                </template>
                            </div>

                        </div>
                    </template>

                    {{-- Empty state --}}
                    <template x-if="filteredProjects.length === 0">
                        <div class="py-16 text-center text-sm text-slate-400">No projects found.</div>
                    </template>
                </div>

                {{-- Pagination --}}
                <div class="flex items-center justify-center gap-1 border-t border-slate-100 px-5 py-4">
                    <template x-for="page in [1, 2, 3]" :key="page">
                        <button :class="page === 1 ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-800'" class="w-8 h-8 rounded-lg text-sm transition" x-text="page"></button>
                    </template>
                    <span class="text-slate-400 text-sm px-1">...</span>
                    <button class="w-8 h-8 rounded-lg text-sm text-slate-500 hover:text-slate-800 transition">10</button>
                    <button class="ml-2 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">[Next]</button>
                </div>
            </div>
        </div>

    </div>

    
@endsection

@push('scripts')
<script>
</script>
@endpush