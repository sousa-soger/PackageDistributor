@extends('layouts.app')

@section('title', 'New Package V3 Lifecycle')

@section('content')
    <div class="max-w-7xl mx-auto space-y-8 pt-4 pb-12" x-data="newPackageWizard({
                                repositories: @js($repositories),
                                generateUrl: '{{ route('deployments.generate-delta') }}',
                                csrfToken: '{{ csrf_token() }}'
                            })">
        <x-ui.card class="p-8 w-full">
            <div class="space-y-6">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Package Selection</h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Select repositories then select [base → head] to create update and rollback package
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Repository</label>
                    <select x-model="selectedRepository"
                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500">
                        <option value="" disabled>Select a repository...</option>
                        @foreach ($repositories as $repository)
                            <option value="{{ $repository['id'] }}">
                                {{ $repository['label'] }} ({{ $repository['owner'] }}/{{ $repository['repo'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div x-show="repoData" class="rounded-xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-700">
                    <div class="flex flex-col gap-1">
                        <span class="font-semibold text-slate-900">Description:</span>
                        <span class="text-slate-500" x-text="repoData?.description || '-'"></span>
                    </div>
                </div>

                <div x-show="isLoadingVersions"
                    class="rounded-xl border border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-500">
                    Loading repository versions...
                </div>

                <!-- Multi-row Base/Head Selection -->
                <div class="mt-8 overflow-visible" x-show="selectedRepository && !isLoadingVersions" x-cloak>
                    <div class="min-w-[900px]">

                        <!-- Header Row -->
                        <div class="flex text-sm font-semibold text-slate-800 pb-4">
                            <div class="w-[20%] text-center">BASE</div>
                            <div class="w-[20%] text-center">HEAD</div>
                            <div class="w-[12%] text-center">Environment</div>
                            <div class="w-[10%] text-center">Format</div>
                            <div class="w-[38%] text-left pl-6">Package Folder Name</div>
                        </div>

                        <!-- Relative container for continuous vertical lines -->
                        <div class="relative pt-2 pb-2">
                            <div class="absolute top-[-1.5rem] bottom-0 left-[20%] w-px bg-slate-200"></div>
                            <div class="absolute top-[-1.5rem] bottom-0 left-[40%] w-px bg-slate-200"></div>
                            <div class="absolute top-[-1.5rem] bottom-0 left-[52%] w-px bg-slate-200"></div>
                            <div class="absolute top-[-1.5rem] bottom-0 left-[62%] w-px bg-slate-200"></div>

                            <!-- Rows -->
                            <div class="space-y-4">
                                <template x-for="(row, index) in packageRows" :key="row.id">
                                    <div class="flex items-center relative z-10">

                                        <!-- BASE trigger -->
                                        <div class="w-[20%] px-4 flex justify-center">
                                            <div class="w-full max-w-[220px]">
                                                <button type="button" @click="openFloatDd($el, index, 'base')"
                                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer flex items-center justify-between"
                                                    :class="isFloatDdOpen(index, 'base') ? 'border-blue-500 ring-1 ring-blue-500' : ''">
                                                    <span
                                                        x-text="row.base ? (allRepoVersions.find(v => v.unique_key === row.base)?.name || 'Select version') : 'Select version'"
                                                        class="truncate pr-2 text-left"></span>
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-4 w-4 shrink-0 text-slate-500 transition-transform duration-150"
                                                        :class="isFloatDdOpen(index, 'base') ? 'rotate-180' : ''"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- HEAD trigger -->
                                        <div class="w-[20%] px-4 flex justify-center">
                                            <div class="w-full max-w-[220px]">
                                                <button type="button" @click="openFloatDd($el, index, 'head')"
                                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer flex items-center justify-between"
                                                    :class="isFloatDdOpen(index, 'head') ? 'border-blue-500 ring-1 ring-blue-500' : ''">
                                                    <span
                                                        x-text="row.head ? (allRepoVersions.find(v => v.unique_key === row.head)?.name || 'Select version') : 'Select version'"
                                                        class="truncate pr-2 text-left"></span>
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-4 w-4 shrink-0 text-slate-500 transition-transform duration-150"
                                                        :class="isFloatDdOpen(index, 'head') ? 'rotate-180' : ''"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Environment -->
                                        <div class="w-[12%] px-4 flex justify-center">
                                            <select x-model="row.environment" @change="handleRowInteract(index)"
                                                class="w-full max-w-[100px] rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                                <option value="PROD">PROD</option>
                                                <option value="QA">QA</option>
                                                <option value="DEV">DEV</option>
                                            </select>
                                        </div>

                                        <!-- Format -->
                                        <div class="w-[10%] px-4 flex justify-center">
                                            <select x-model="row.format" @change="handleRowInteract(index)"
                                                class="w-full max-w-[90px] rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                                <option value=".zip">.zip</option>
                                                <option value=".tar.gz">.tar.gz</option>
                                                <option value="both">both</option>
                                            </select>
                                        </div>

                                        <!-- Package Folder Name -->
                                        <div class="w-[38%] pl-6 pr-2 flex items-center gap-3">
                                            <input type="text" x-model="row.name"
                                                @input="row.customName = true; handleRowInteract(index)"
                                                :readonly="!row.customName || !isRowReadyForName(row)"
                                                :disabled="!isRowReadyForName(row)"
                                                :class="(!row.customName || !isRowReadyForName(row)) ? 'bg-slate-50 text-slate-400 border-slate-100 cursor-not-allowed' : 'bg-white text-slate-700 border-slate-200'"
                                                class="w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder:text-slate-300 transition-colors"
                                                placeholder="">
                                            <button type="button"
                                                @click="row.customName = !row.customName; if(row.customName) { $nextTick(() => { $el.previousElementSibling.focus() }) }; handleRowInteract(index)"
                                                x-show="isRowReadyForName(row)" x-cloak
                                                class="shrink-0 transition focus:outline-none"
                                                :class="row.customName ? 'text-amber-500 hover:text-amber-600' : 'text-blue-600 hover:text-blue-800'"
                                                :title="row.customName ? 'Revert to automatic name' : 'Edit name manually'">
                                                <!-- Pencil Icon (when auto) -->
                                                <svg x-show="!row.customName" xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                                <!-- Revert Icon (when custom) -->
                                                <svg x-show="row.customName" xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 relative z-10" x-show="selectedRepository" x-cloak>
                    <div class="mb-6 border-t border-slate-200 pt-6">
                        <h2 class="text-xl font-semibold text-slate-800">Distribution Package Lifecycle</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            The system will generate an update package and a rollback package based on the selected Git
                            versions.
                            <br>(Multi-queue implementation currently pending).
                        </p>
                    </div>

                    <div class="mt-6 flex items-center">
                        <div class="flex items-center gap-3">
                            <button type="button"
                                class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition disabled:bg-slate-300 disabled:cursor-not-allowed shadow-sm"
                                disabled>
                                Process Queue
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </x-ui.card>

        {{-- === GLOBAL FLOATING DROPDOWN (lives once, outside x-for) === --}}
        {{-- Backdrop: catches outside clicks to close --}}
        <div x-show="floatDd.open" @click="floatDd.open = false" style="position:fixed;inset:0;z-index:99998" x-cloak></div>

        {{-- Panel: anchored via JS-computed fixed coords --}}
        <div x-show="floatDd.open" :style="floatDd.style" x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden" style="z-index:99999" x-cloak>
            <div class="p-3 border-b border-slate-100 bg-slate-50">
                <select x-model="floatDd.typeFilter"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                    <option value="">All Types</option>
                    <option value="branch">Branches</option>
                    <option value="tag">Tags</option>
                    <option value="release">Releases</option>
                </select>
            </div>
            <div class="max-h-60 overflow-y-auto py-1">
                <template x-for="v in floatDdVersions" :key="v.unique_key">
                    <button type="button" @click="selectFloatVersion(v.unique_key)"
                        class="w-full px-4 py-2.5 text-left text-sm hover:bg-blue-50 flex items-center justify-between transition"
                        :class="floatDdCurrentValue === v.unique_key ? 'bg-blue-50/70 font-medium text-blue-700' : 'text-slate-700'">
                        <span class="truncate" x-text="v.name"></span>
                        <span class="ml-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wider"
                            x-text="v.type"></span>
                    </button>
                </template>
                <div x-show="floatDdVersions.length === 0" class="px-4 py-4 text-center text-sm text-slate-500">
                    No versions found
                </div>
            </div>
        </div>
        {{-- ============================================================ --}}

    </div>
@endsection

@push('scripts')
    <script>
        function newPackageWizard({ repositories, generateUrl, csrfToken }) {
            return {
                repositories,
                selectedRepository: '',
                repoData: null,
                repoBranches: [],
                repoTags: [],
                repoReleases: [],
                isLoadingVersions: false,
                rateLimit: null,

                packageRows: [
                    { id: Date.now(), base: '', head: '', environment: 'PROD', format: '.zip', customName: false, name: '' }
                ],

                // ── Global floating dropdown state ──────────────────────
                floatDd: {
                    open: false,
                    rowIndex: null,
                    field: null,   // 'base' | 'head'
                    typeFilter: '',
                    style: '',
                },

                openFloatDd(btn, rowIndex, field) {
                    // Toggle: close if same button clicked again
                    if (this.floatDd.open && this.floatDd.rowIndex === rowIndex && this.floatDd.field === field) {
                        this.floatDd.open = false;
                        return;
                    }
                    const r = btn.getBoundingClientRect();
                    this.floatDd.style = `position:fixed;z-index:99999;top:${r.bottom + 6}px;left:${r.left}px;width:${Math.max(r.width, 240)}px`;
                    this.floatDd.rowIndex = rowIndex;
                    this.floatDd.field = field;
                    this.floatDd.typeFilter = '';
                    this.floatDd.open = true;
                },

                selectFloatVersion(versionKey) {
                    if (this.floatDd.rowIndex === null) return;
                    const row = this.packageRows[this.floatDd.rowIndex];
                    if (!row) return;
                    row[this.floatDd.field] = versionKey;
                    this.handleRowInteract(this.floatDd.rowIndex);
                    this.floatDd.open = false;
                },

                isFloatDdOpen(rowIndex, field) {
                    return this.floatDd.open && this.floatDd.rowIndex === rowIndex && this.floatDd.field === field;
                },

                get floatDdCurrentValue() {
                    if (this.floatDd.rowIndex === null) return '';
                    const row = this.packageRows[this.floatDd.rowIndex];
                    return row ? (row[this.floatDd.field] || '') : '';
                },

                get floatDdVersions() {
                    const tf = this.floatDd.typeFilter;
                    return this.allRepoVersions.filter(v => !tf || v.type === tf);
                },
                // ───────────────────────────────────────────────────────

                get selectedRepositoryLabel() {
                    const repo = this.repositories.find(r => r.id === this.selectedRepository);
                    return repo ? repo.label : this.selectedRepository;
                },

                get allRepoVersions() {
                    const branches = this.repoBranches.map(b => ({
                        unique_key: `branch:${b.name}`, type: 'branch', name: b.name, ref: b.name
                    }));
                    const tags = this.repoTags.map(t => ({
                        unique_key: `tag:${t.name}`, type: 'tag', name: t.name, ref: t.name
                    }));
                    const releases = this.repoReleases.map(r => ({
                        unique_key: `release:${r.id}`, type: 'release', name: r.name || r.tag_name, ref: r.tag_name
                    }));
                    return [...releases, ...tags, ...branches];
                },

                init() {
                    this.fetchRateLimit();
                    this.$watch('selectedRepository', async () => {
                        this.floatDd.open = false;
                        this.packageRows = [
                            { id: Date.now(), base: '', head: '', environment: 'PROD', format: '.zip', customName: false, name: '' }
                        ];
                        await this.fetchRepoData();
                        await this.fetchRepoVersions();
                        await this.fetchRateLimit();
                    });
                    setInterval(() => this.updateRowNames(), 60000);
                },

                isRowComplete(row) {
                    return !!(row.base && row.head && row.environment && row.format && row.name);
                },

                isRowReadyForName(row) {
                    return !!(row.base && row.head && row.environment && row.format);
                },

                handleRowInteract(index) {
                    this.updateRowNames();
                    const isLastRow = index === this.packageRows.length - 1;
                    if (isLastRow && this.isRowComplete(this.packageRows[index])) {
                        const prev = this.packageRows[index];
                        this.packageRows.push({
                            id: Date.now(),
                            base: '', head: '',
                            environment: prev.environment || '',
                            format: prev.format || '',
                            customName: false, name: ''
                        });
                    }
                },

                updateRowNames() {
                    const proj = this.selectedRepositoryLabel || '[Project]';
                    const now = new Date();
                    const pad = n => String(n).padStart(2, '0');
                    const ts = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}`;

                    this.packageRows.forEach(row => {
                        if (!row.customName && (row.base || row.head)) {
                            const env = row.environment || '';
                            const bObj = this.allRepoVersions.find(v => v.unique_key === row.base);
                            const hObj = this.allRepoVersions.find(v => v.unique_key === row.head);
                            const bLbl = bObj ? bObj.name : '[Base]';
                            const hLbl = hObj ? hObj.name : '[Head]';
                            row.name = `${env}-${proj}-${bLbl}-to-${hLbl}-${ts}`;
                        } else if (!row.customName) {
                            row.name = '';
                        }
                    });
                },

                async fetchRateLimit() {
                    try {
                        this.rateLimit = await (await fetch('/github/rate-limit')).json();
                    } catch (e) { }
                },

                async fetchRepoData() {
                    if (!this.selectedRepository) { this.repoData = null; return; }
                    try {
                        const res = await fetch(`/github/repo-info?repo=${encodeURIComponent(this.selectedRepository)}`);
                        this.repoData = res.ok ? await res.json() : null;
                    } catch { this.repoData = null; }
                },

                async fetchRepoVersions() {
                    if (!this.selectedRepository) {
                        this.repoBranches = this.repoTags = this.repoReleases = [];
                        return;
                    }
                    this.isLoadingVersions = true;
                    try {
                        const res = await fetch(`/github/repo-versions?repo=${encodeURIComponent(this.selectedRepository)}`);
                        const data = res.ok ? await res.json() : {};
                        this.repoBranches = data.branches || [];
                        this.repoTags = data.tags || [];
                        this.repoReleases = data.releases || [];
                    } catch {
                        this.repoBranches = this.repoTags = this.repoReleases = [];
                    } finally {
                        this.isLoadingVersions = false;
                    }
                }
            };
        }
    </script>
@endpush