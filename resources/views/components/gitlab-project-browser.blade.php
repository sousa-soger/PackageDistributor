@props([
    'projectsVar' => 'filteredProjects',
    'loadingVar' => 'loading',
    'loadingExploreVar' => 'loadingExplore',
    'selectable' => false,
    'withMembers' => false,
    'scrollable' => false,
])

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">

    {{-- Tabs --}}
    <div class="flex border-b border-slate-200 px-2 pt-1 overflow-x-auto">
        <button 
            @click="activeTab = 'projects'"
            :class="activeTab === 'projects' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'"
            class="px-4 py-2.5 text-sm border-b-2 transition-colors -mb-px whitespace-nowrap"
        >My Projects</button>
        <button
            @click="activeTab = 'personal'"
            :class="activeTab === 'personal' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'"
            class="px-4 py-2.5 text-sm border-b-2 transition-colors -mb-px whitespace-nowrap"
        >Personal</button>
        <button
            @click="activeTab = 'shared'"
            :class="activeTab === 'shared' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'"
            class="px-4 py-2.5 text-sm border-b-2 transition-colors -mb-px whitespace-nowrap"
        >Shared with Me</button>
        <button
            @click="activeTab = 'all'"
            :class="activeTab === 'all' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'"
            class="px-4 py-2.5 text-sm border-b-2 transition-colors -mb-px whitespace-nowrap"
        >All Projects</button>
        <div x-show="activeTab === 'all'" class="ml-auto px-4 py-2.5 flex items-center gap-2 cursor-pointer -mb-px border-b-2 border-transparent hover:bg-slate-50 transition-colors" @click="toggle = !toggle">
            <input type="checkbox" x-model="toggle" class="cursor-pointer pointer-events-none accent-orange-500">
            <span class="text-sm text-slate-500 whitespace-nowrap">Include SAINS Public Internal Projects</span>
        </div>
    </div>

    {{-- Project Rows --}}
    <div class="divide-y divide-slate-100 {{ $scrollable ? 'max-h-80 overflow-y-auto' : '' }}">
        <template x-for="project in {{ $projectsVar }}" :key="project.id">
            <div @if($withMembers)
                    x-data="{
                        expanded: false,
                        searchQuery: '',
                        suggestions: [],
                        selectedUser: null,
                        accessLevel: 30,
                        inviteLoading: false,
                        inviteSuccess: '',
                        inviteError: '',
                        members: [],
                        membersLoading: false,
                        debounceTimer: null,
                        roleLabels: { 10: 'Guest', 20: 'Reporter', 30: 'Developer', 40: 'Maintainer', 50: 'Owner' },

                        toggle() {
                            this.expanded = !this.expanded;
                            if (this.expanded && this.members.length === 0) {
                                this.loadMembers(project.id);
                            }
                        },

                        onSearchInput() {
                            clearTimeout(this.debounceTimer);
                            this.selectedUser = null;
                            if (this.searchQuery.length < 2) { this.suggestions = []; return; }
                            this.debounceTimer = setTimeout(() => this.fetchSuggestions(), 300);
                        },

                        async fetchSuggestions() {
                            const res = await fetch(`{{ route('gitlab.users.search') }}?q=${encodeURIComponent(this.searchQuery)}`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            this.suggestions = res.ok ? await res.json() : [];
                        },

                        selectUser(u) {
                            this.selectedUser = u;
                            this.searchQuery = u.username;
                            this.suggestions = [];
                        },

                        clearUser() {
                            this.selectedUser = null;
                            this.searchQuery = '';
                            this.suggestions = [];
                        },

                        async sendInvite(projectId) {
                            if (!this.selectedUser) return;
                            this.inviteLoading = true;
                            this.inviteSuccess = '';
                            this.inviteError = '';
                            try {
                                const res = await fetch(`/gitlab/projects/${projectId}/members`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    },
                                    body: JSON.stringify({ user_id: this.selectedUser.id, access_level: parseInt(this.accessLevel) }),
                                });
                                const data = await res.json();
                                if (!res.ok) throw new Error(data.message || 'Failed to invite.');
                                this.inviteSuccess = `${this.selectedUser.name} added successfully!`;
                                this.clearUser();
                                this.accessLevel = 30;
                                await this.loadMembers(projectId);
                            } catch (e) {
                                this.inviteError = e.message;
                            } finally {
                                this.inviteLoading = false;
                            }
                        },

                        async loadMembers(projectId) {
                            this.membersLoading = true;
                            try {
                                const res = await fetch(`/gitlab/projects/${projectId}/members`, {
                                    headers: { 'Accept': 'application/json' }
                                });
                                this.members = res.ok ? await res.json() : [];
                            } finally {
                                this.membersLoading = false;
                            }
                        },

                        async updateRole(projectId, memberId, newLevel) {
                            await fetch(`/gitlab/projects/${projectId}/members/${memberId}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({ access_level: parseInt(newLevel) }),
                            });
                        },

                        async removeMember(projectId, memberId) {
                            if(!confirm('Are you sure you want to remove this member?')) return;
                            await fetch(`/gitlab/projects/${projectId}/members/${memberId}`, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                            });
                            await this.loadMembers(projectId);
                        },

                        formatDate(d) {
                            if (!d) return 'No expiry';
                            return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
                        },
                    }" class="overflow-hidden"
                 @endif
            >

                {{-- Main row --}}
                <div class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition-colors {{ $selectable || $withMembers ? 'cursor-pointer' : '' }}"
                    @if($selectable)
                        @click="selectGitlabProject(project)"
                        :class="selectedRepository == project.id ? 'bg-blue-50 ring-1 ring-inset ring-blue-200' : ''"
                    @endif
                >

                    {{-- Icon --}}
                    <div class="shrink-0 w-10 h-10 flex items-center justify-center rounded-xl overflow-hidden bg-slate-100 text-slate-500">
                        <template x-if="project.avatar_url">
                            <img :src="project.avatar_url" :alt="project.name" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!project.avatar_url">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 28 26" class="w-5 h-5"><path fill="#E24329" d="m24.507 9.5-.034-.09L21.082.562a.896.896 0 0 0-1.694.091l-2.29 7.01H7.825L5.535.653a.898.898 0 0 0-1.694-.09L.451 9.411.416 9.5a6.297 6.297 0 0 0 2.09 7.278l.012.01.03.022 5.16 3.867 2.56 1.935 1.554 1.176a1.051 1.051 0 0 0 1.268 0l1.555-1.176 2.56-1.935 5.197-3.89.014-.01A6.297 6.297 0 0 0 24.507 9.5Z"/><path fill="#FC6D26" d="m24.507 9.5-.034-.09a11.44 11.44 0 0 0-4.56 2.051l-7.447 5.632 4.742 3.584 5.197-3.89.014-.01A6.297 6.297 0 0 0 24.507 9.5Z"/><path fill="#FCA326" d="m7.707 20.677 2.56 1.935 1.555 1.176a1.051 1.051 0 0 0 1.268 0l1.555-1.176 2.56-1.935-4.743-3.584-4.755 3.584Z"/><path fill="#FC6D26" d="M5.01 11.461a11.43 11.43 0 0 0-4.56-2.05L.416 9.5a6.297 6.297 0 0 0 2.09 7.278l.012.01.03.022 5.16 3.867 4.745-3.584-7.444-5.632Z"/></svg>
                        </template>
                    </div>

                    {{-- Name + Description --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-bold text-slate-800 leading-tight" x-text="project.name"></p>
                            <span class="text-xs text-slate-400 font-mono truncate" x-text="project.path"></span>
                            <template x-if="project.source === 'explore'">
                                <span class="inline-flex items-center rounded-full bg-orange-50 border border-orange-200 px-2 py-0.5 text-xs font-medium text-orange-600">SAINS Internal</span>
                            </template>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5" x-text="project.description"></p>
                    </div>

                    {{-- Meta --}}
                    <div class="hidden md:flex items-center gap-4 shrink-0">
                        <span class="text-xs text-slate-600 whitespace-nowrap">Last Updated:
                            <span class="font-medium" x-text="timeAgo(project.lastActivity)"></span>
                        </span>
                        <template x-if="project.visibility === 'internal'">
                            <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path d="M16 11V7a4 4 0 0 0-8 0v4"/><rect x="3" y="11" width="18" height="11" rx="2"/></svg>Internal
                            </span>
                        </template>
                        <template x-if="project.visibility === 'public'">
                            <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>Public
                            </span>
                        </template>
                        <template x-if="project.visibility === 'private'">
                            <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Private
                            </span>
                        </template>
                    </div>

                    @if($withMembers)
                        {{-- Actions --}}
                        <div class="flex items-center gap-2 shrink-0">
                            {{-- Invite button: only for Maintainer (40) or Owner (50) --}}
                            <template x-if="project.access_level >= 40">
                                <button @click="toggle(); loadMembers(project.id)"
                                    :class="expanded ? 'bg-slate-200 text-slate-700 border border-slate-300' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'"
                                    class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition">
                                    <svg fill="none" class="w-4 h-4" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM3 20a6 6 0 0 1 12 0v1H3v-1z"/></svg>
                                    Invite
                                </button>
                            </template>
                            <a :href="project.web_url" target="_blank" rel="noopener noreferrer"
                                class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition">
                                Go to Project
                            </a>
                        </div>
                    @endif

                    @if($selectable)
                        {{-- Selected checkmark --}}
                        <div x-show="selectedRepository == project.id" class="shrink-0 ml-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                        </div>
                    @endif
                </div>

                @if($withMembers)
                    {{-- Expandable panel --}}
                    <div x-show="expanded" x-collapse x-cloak class="border-t border-slate-100 bg-slate-50 px-6 py-5 space-y-5">

                        {{-- Invite form (only for Maintainer/Owner) --}}
                        <template x-if="project.access_level >= 40">
                            <div>
                                <p class="text-sm font-semibold text-slate-700 mb-3">Invite a member to <span class="text-blue-600" x-text="project.name"></span></p>

                                <div class="flex flex-col md:flex-row gap-3 items-start">

                                    {{-- Username autocomplete --}}
                                    <div class="relative flex-1 w-full">
                                        <div class="flex items-center rounded-lg border border-slate-300 bg-white overflow-hidden focus-within:ring-2 focus-within:ring-blue-200 focus-within:border-blue-500">
                                            <template x-if="selectedUser">
                                                <div class="flex items-center gap-1.5 pl-3 shrink-0">
                                                    <img :src="selectedUser.avatar_url" class="w-5 h-5 rounded-full">
                                                    <span class="text-sm font-medium text-slate-700" x-text="selectedUser.username"></span>
                                                    <button @click="clearUser()" type="button" class="ml-1 text-slate-400 hover:text-slate-600">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <input type="text" x-model="searchQuery" @input="onSearchInput()"
                                                :class="selectedUser ? 'w-0 opacity-0 pointer-events-none' : 'flex-1'"
                                                placeholder="Search by username..."
                                                class="px-3 py-2 text-sm outline-none bg-transparent flex-1 w-full">
                                        </div>

                                        {{-- Dropdown suggestions --}}
                                        <template x-if="suggestions.length > 0">
                                            <div class="absolute z-20 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                                                <template x-for="u in suggestions" :key="u.id">
                                                    <button @click="selectUser(u)" type="button"
                                                        class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition text-left">
                                                        <img :src="u.avatar_url" class="w-7 h-7 rounded-full shrink-0">
                                                        <div class="min-w-0">
                                                            <p class="text-sm font-medium text-slate-800 truncate" x-text="u.name"></p>
                                                            <p class="text-xs text-slate-400 truncate" x-text="'@' + u.username"></p>
                                                        </div>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Access level --}}
                                    <select x-model="accessLevel" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none w-full md:w-auto">
                                        <option value="10">Guest</option>
                                        <option value="20">Reporter</option>
                                        <option value="30" selected>Developer</option>
                                        <option value="40">Maintainer</option>
                                        <option value="50">Owner</option>
                                    </select>

                                    {{-- Submit --}}
                                    <button @click="sendInvite(project.id)" :disabled="!selectedUser || inviteLoading"
                                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition whitespace-nowrap w-full md:w-auto">
                                        <span x-show="!inviteLoading">Invite</span>
                                        <span x-show="inviteLoading" class="animate-pulse">Sending...</span>
                                    </button>
                                </div>

                                <p x-show="inviteSuccess" x-text="inviteSuccess" class="mt-2 text-sm text-emerald-600 font-medium"></p>
                                <p x-show="inviteError" x-text="inviteError" class="mt-2 text-sm text-red-500"></p>
                            </div>
                        </template>

                        {{-- Members list --}}
                        <div>
                            <p class="text-sm font-semibold text-slate-700 mb-3">Members</p>

                            <div x-show="membersLoading" class="py-4 text-center text-sm text-slate-400 animate-pulse">Loading members...</div>

                            <template x-if="!membersLoading && members.length > 0">
                                <div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="border-b border-slate-100 bg-slate-50">
                                            <tr>
                                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Account</th>
                                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Role</th>
                                                <th class="text-left px-2 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Expiration</th>
                                                <th class="px-2 py-2.5"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <template x-for="m in members" :key="m.id">
                                                <tr class="hover:bg-slate-50 transition-colors">
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2.5">
                                                            <img :src="m.avatar_url" class="w-7 h-7 rounded-full shrink-0">
                                                            <div class="min-w-0">
                                                                <p class="font-medium text-slate-800 truncate" x-text="m.name"></p>
                                                                <p class="text-xs text-slate-400 truncate" x-text="'@' + m.username"></p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <select :value="m.access_level"
                                                            :disabled="project.access_level < 40"
                                                            @change="updateRole(project.id, m.id, $event.target.value); m.access_level = parseInt($event.target.value)"
                                                            class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none disabled:opacity-60 disabled:cursor-not-allowed bg-white">
                                                            <option value="10">Guest</option>
                                                            <option value="20">Reporter</option>
                                                            <option value="30">Developer</option>
                                                            <option value="40">Maintainer</option>
                                                            <option value="50">Owner</option>
                                                        </select>
                                                    </td>
                                                    <td class="px-2 py-3 text-xs text-slate-500" x-text="formatDate(m.expires_at)">
                                                    </td>
                                                    <td title="Remove User" class="pr-2">
                                                        <svg @click="removeMember(project.id, m.id)" class="w-5 h-5 text-red-600 hover:text-red-700 cursor-pointer mx-auto" viewBox="0 0 256 231" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M109.571,181.114l-31.497,12.953l9.702,9.442l-24.694,25.374L48.95,215.131c-5.203-5.065-9.34-11.12-12.166-17.808
                                                            l-2.756-6.523l-10.991-10.697l-5.204,5.483L2,172.428V85l64.896,48.899l-5.94,6.258c22.595,8.254,47.941,5.379,68.217-8.075
                                                            l2.299-1.526c4.55-3.018,10.588-2.455,14.501,1.352l5.899,5.741L109.571,181.114z M250.972,126.087l-29.367-29.367
                                                            c-1.939-1.938-4.568-3.027-7.309-3.027H186.32l13.714-37.959h42.154c4.349,0,7.875-3.526,7.875-7.875
                                                            c0-4.349-3.526-7.875-7.875-7.875h-66.979c-3.559,0-7.021,0.717-10.286,2.13l-24.256,10.495L121.688,33.53
                                                            c-3.065-3.083-8.053-3.098-11.136-0.029c-3.084,3.066-3.097,8.052-0.03,11.136l22.764,22.887c1.76,1.768,4.742,3.122,8.71,1.674
                                                            l18.39-7.957l-14.044,39.051c-1.44,3.687-1.175,8.788,1.41,11.698l22.341,23.512l-7.393,33.572
                                                            c-1.227,5.575,2.298,11.09,7.872,12.317c0.749,0.165,1.496,0.244,2.233,0.244c4.747,0,9.022-3.29,10.084-8.116l8.525-38.718
                                                            c0.712-3.233-0.168-6.61-2.365-9.085l-10.076-11.349h31.042l26.34,26.34c4.038,4.035,10.582,4.037,14.618,0
                                                            C255.009,136.669,255.009,130.124,250.972,126.087z M117.491,99.918l6.949-8.001l10,17.407L117.491,99.918z M96.5,128.313
                                                            l2.543-16.324L128,118.592L96.5,128.313z M166.262,18.481c0,9.038,7.327,16.365,16.365,16.365c9.038,0,16.365-7.327,16.365-16.365
                                                            s-7.327-16.365-16.365-16.365C173.589,2.116,166.262,9.443,166.262,18.481z"/>
                                                        </svg>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>

                            <template x-if="!membersLoading && members.length === 0">
                                <p class="text-sm text-slate-400">No members found.</p>
                            </template>
                        </div>
                    </div>
                @endif
            </div>
        </template>

        <template x-if="{{ $loadingVar }}">
            <div class="rounded-xl border-t border-transparent py-16 text-center relative z-10 flex flex-col justify-center">
                <span class="font-medium text-blue-900 animate-pulse text-sm">Loading Your GitLab Projects...</span>
            </div>
        </template>
        <template x-if="{{ $loadingExploreVar }}">
            <div x-show="activeTab === 'all'" class="rounded-xl border-t border-transparent py-16 text-center relative z-10 flex flex-col justify-center">
                <span class="font-medium text-blue-900 animate-pulse text-sm">Loading SAINS Internal GitLab Projects...</span>
            </div>
        </template>

        {{-- Empty state --}}
        <template x-if="!{{ $loadingVar }} && !{{ $loadingExploreVar }} && {{ $projectsVar }}.length === 0">
            <div class="py-16 text-center text-sm text-slate-400">No projects found.</div>
        </template>
    </div>
</div>
