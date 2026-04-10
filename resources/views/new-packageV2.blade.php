@extends('layouts.app')

@section('title', 'New Package V2')

@section('content')
    <div class="max-w-7xl mx-auto space-y-8 pt-4 pb-12" x-data="newPackageWizard({
                        repositories: @js($repositories),
                        generateUrl: '{{ route('deployments.generate-delta') }}',
                        csrfToken: '{{ csrf_token() }}'
                    })">

        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Create Distribution Package V2</h1>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">
            <!-- Left Column: Step 1 and Step 3 -->
            <div class="xl:col-span-5 flex flex-col gap-8">
                @include('components.step-card-new-package.1')
                @include('components.step-card-new-package.3')
            </div>

            <!-- Right Column: Step 2 -->
            <div class="xl:col-span-7">
                @include('components.step-card-new-package.2')
            </div>
        </div>

        <!-- Combined Step 4 & 5 Card -->
        <div class="mt-8" x-cloak>
            <x-ui.card class="w-full relative overflow-hidden transition-all duration-700 ease-in-out">
                <div class="flex flex-col lg:flex-row min-h-[400px] transition-all duration-700 ease-in-out">

                    <!-- Left Side: Step 4 Content -->
                    <div class="p-8 transition-all duration-700 ease-in-out flex flex-col justify-between"
                        :class="packagingResult ? 'w-full lg:w-[45%] lg:border-r border-slate-200' : 'w-full lg:w-full'">

                        <div>
                            <div class="mb-6">
                                <h2 class="text-xl font-semibold text-slate-800">Distribution Package Lifecycle</h2>
                                <p class="mt-2 text-sm text-slate-500">
                                    The system will generate an update package and a rollback package based on the selected
                                    Git versions.
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 mb-6"
                                :class="packagingResult ? 'opacity-0 h-0 hidden' : 'opacity-100'">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Target
                                        Environment</div>
                                    <span x-text="selectedEnvironment || '-'"
                                        class="mt-1 block text-sm font-medium text-slate-800"></span>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Project Name
                                    </div>
                                    <span x-text="selectedRepositoryLabel || '-'"
                                        class="mt-1 block text-sm font-medium text-slate-800"></span>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Base Version
                                    </div>
                                    <span x-text="selectedBaseLabel || '-'"
                                        class="mt-1 block text-sm font-medium text-slate-800"></span>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Head Version
                                    </div>
                                    <span x-text="selectedHeadLabel || '-'"
                                        class="mt-1 block text-sm font-medium text-slate-800"></span>
                                </div>
                            </div>

                            <!-- Progress Section -->
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                                x-show="isPackaging || packagingProgress > 0 || packagingResult">
                                <div class="mb-4">
                                    <div class="mb-1.5 flex items-center justify-between">
                                        <span class="text-sm font-semibold"
                                            :class="packagingProgress === 100 ? 'text-green-600' : 'text-slate-700'">
                                            Packaging Progress <span x-show="packagingProgress === 100"
                                                class="text-green-600">✓</span>
                                        </span>
                                        <span class="text-sm font-medium text-slate-600"
                                            x-text="packagingProgress + '%'"></span>
                                    </div>
                                    <div class="h-3 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full bg-blue-500 transition-all duration-500"
                                            :style="`width: ${packagingProgress}%`"></div>
                                    </div>
                                </div>
                                <hr class="border-slate-200 mb-4">
                                <div class="flex flex-col">
                                    <div :class="fileDownloadProgress === 100 ? 'mb-1' : 'mb-4'">
                                        @include('components.step-card-new-package.partials.progress-bar', ['field' => 'fileDownloadProgress', 'label' => 'Downloading base and head repository', 'weight' => '10%'])
                                    </div>
                                    <div
                                        :class="baseFileExtraction === 100 && headFileExtraction === 100 ? 'mb-1 flex flex-col gap-1' : 'mb-4 grid grid-cols-2 gap-4'">
                                        @include('components.step-card-new-package.partials.progress-bar', ['field' => 'baseFileExtraction', 'label' => 'Base File Extraction', 'weight' => '20%'])
                                        @include('components.step-card-new-package.partials.progress-bar', ['field' => 'headFileExtraction', 'label' => 'Head File Extraction', 'weight' => '20%'])
                                    </div>
                                    <div :class="compareFilesProgress === 100 ? 'mb-1' : 'mb-4'">
                                        @include('components.step-card-new-package.partials.progress-bar', ['field' => 'compareFilesProgress', 'label' => 'Comparing Files', 'weight' => '10%'])
                                    </div>
                                    <div
                                        :class="packageGenProgress === 100 && compressionProgress === 100 ? 'flex flex-col gap-1' : 'grid grid-cols-2 gap-4'">
                                        @include('components.step-card-new-package.partials.progress-bar', ['field' => 'packageGenProgress', 'label' => 'Generating Update and Rollback Packages', 'weight' => '20%'])
                                        @include('components.step-card-new-package.partials.progress-bar', ['field' => 'compressionProgress', 'label' => 'Compressing Update and Rollback Packages', 'weight' => '20%'])
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 text-sm text-slate-500" x-show="isPackaging || packagingResult"
                                x-text="packagingMessage"></p>

                            <div x-show="packagingError"
                                class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                <div class="font-semibold">Packaging failed</div>
                                <div class="mt-1" x-text="packagingError"></div>
                            </div>

                            <div x-show="packagingResult" x-cloak
                                class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 transition-all">
                                <div class="text-sm font-semibold text-emerald-800">Package created successfully</div>
                                <div class="mt-3 grid gap-3 grid-cols-1">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Folder
                                        </div>
                                        <div class="mt-1 break-all text-sm text-slate-700"
                                            x-text="packagingResult?.folder_name || '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Package
                                            Root</div>
                                        <div class="mt-1 break-all text-sm text-slate-700"
                                            x-text="packagingResult?.package_root || '-'"></div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Action Buttons Left -->
                        <div class="mt-6 flex items-center justify-end" x-show="!packagingResult">
                            <div class="flex items-center gap-3">
                                <button type="button"
                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                                    x-show="!isPackaging && !packagingResult" @click="runPackaging()"
                                    :disabled="!selectedEnvironment || !selectedVersionBase || !selectedVersionHead || !selectedFormat">
                                    Start Packaging
                                </button>
                                <button type="button" @mouseover="hovered = true" @mouseleave="hovered = false"
                                    class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800"
                                    x-show="isPackaging && !packagingResult" @click="confirmation = true">
                                    <span x-show="!hovered">Packaging...</span>
                                    <span x-show="hovered">Stop Packaging</span>
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Right Side: Step 5 Content (Dynamicly Shown) -->
                    <div x-show="packagingResult" x-transition:enter="transition-all ease-out duration-700 delay-300"
                        x-transition:enter-start="opacity-0 translate-x-12"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        class="p-8 w-full lg:w-[55%] flex flex-col bg-white">

                        <div>
                            <h3 class="text-2xl font-bold text-slate-900">
                                Package: <span x-text="packagingResult?.folder_name"></span>
                            </h3>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500 mt-2">
                                <span>Size: <span x-text="packagingResult?.file_size"></span></span>
                                <span class="text-slate-300">|</span>
                                <span>SHA256 Checksum: <span x-text="packagingResult?.sha256"></span></span>
                            </div>
                        </div>

                        <div class="pt-6 grid grid-cols-1 gap-10 xl:grid-cols-[1fr_auto_1fr] flex-1">
                            <!-- Left: Download -->
                            <div class="space-y-8 flex flex-col justify-start">
                                <div class="space-y-2">
                                    <h3 class="text-xl font-semibold text-slate-900">Download Package Locally</h3>
                                    <p class="text-sm text-slate-500">Download the file directly to your computer.</p>
                                </div>
                                <div class="flex flex-col items-center justify-center gap-3 pt-2">
                                    <div
                                        class="flex h-16 w-16 items-center justify-center rounded-2xl border border-slate-200 bg-amber-50 text-3xl shadow-sm">
                                        📦</div>
                                    <span class="text-xs font-medium tracking-wide text-slate-400 uppercase"
                                        x-text="selectedFormat ? selectedFormat.toUpperCase() + ' Package' : 'Package'"></span>
                                </div>
                                <div class="pt-2">
                                    <button type="button" @click="
                                                        const base = '{{ route('download.archive') }}';
                                                        const folder = encodeURIComponent(packagingResult?.folder_name);
                                                        if (selectedFormat === 'both') {
                                                            window.location.href = base + '?folder=' + folder + '&format=.zip';
                                                            setTimeout(() => {
                                                                const iframe = document.createElement('iframe');
                                                                iframe.style.display = 'none';
                                                                iframe.src = base + '?folder=' + folder + '&format=.tar.gz';
                                                                document.body.appendChild(iframe);
                                                                setTimeout(() => iframe.remove(), 10000);
                                                            }, 1000);
                                                        } else {
                                                            window.location.href = base + '?folder=' + folder + '&format=' + encodeURIComponent(selectedFormat);
                                                        }
                                                    "
                                        class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-blue-600 px-6 py-4 text-base font-medium text-white shadow-sm transition hover:bg-blue-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v1a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1" />
                                        </svg>
                                        <span>Download Package</span>
                                        <span
                                            class="rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                            x-text="selectedFormat || 'Select format'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Divider with OR -->
                            <div class="relative hidden h-full items-center xl:flex">
                                <div class="h-full w-px bg-slate-200"></div>
                                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-200 bg-white text-sm font-medium text-slate-500 shadow-sm">
                                        OR</div>
                                </div>
                            </div>

                            <!-- Right: Deploy -->
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <h3 class="text-lg font-bold text-slate-900">Deploy to Own Hosting Server</h3>
                                </div>
                                <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1fr_auto] xl:items-start">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-slate-800">Server Type</label>
                                        <select
                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                                            <option selected disabled>Select a server profile...</option>
                                            <option>Production (Apache)</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-slate-800">Server Check</label>
                                        <div class="flex items-center gap-2 pt-1">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white shadow-sm">
                                                <svg class="h-5 w-5 animate-spin text-blue-600 hidden" viewBox="0 0 24 24"
                                                    fill="none">
                                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5"
                                                        class="opacity-20" />
                                                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <div class="h-2 w-2 rounded-full bg-slate-300"></div>
                                            </div>
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 shadow-sm">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2.2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m5 13 4 4L19 7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-1 text-sm">
                                    <p class="text-slate-700">Server Status: <span
                                            class="font-medium text-emerald-600">Online</span></p>
                                    <p class="text-slate-700">Authentication: <span
                                            class="font-medium text-emerald-600">Verified</span></p>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-slate-800">Deployment Path <span
                                            class="font-normal text-slate-400">(optional)</span></label>
                                    <input type="text" value="/var/www/html/cybix/current"
                                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                                </div>
                                <div>
                                    <button type="button"
                                        class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-blue-600 px-6 py-4 text-base font-medium text-white shadow-sm transition hover:bg-blue-700">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 17h16M7 17V7h10v10M9 7V5h6v2" />
                                        </svg>
                                        <span>Deploy Now</span>
                                        <span
                                            class="rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">Ready</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex items-center justify-end">
                            <button type="button"
                                class="rounded-xl bg-emerald-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                @click="window.location.href = '/'">
                                Finish
                            </button>
                        </div>
                    </div>

                </div>
            </x-ui.card>
        </div>

    </div>

    {{-- Stop confirmation modal --}}
    <div x-show="confirmation" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm w-[400px]">
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-slate-800">Attention</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Are you sure you want to stop packaging?
                </p>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    @click="confirmation = false">Cancel</button>
                <button type="button"
                    class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                    @click="stopPackaging(); confirmation = false">Stop Packaging</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function newPackageWizard({ repositories, generateUrl, csrfToken }) {
            return {
                repositories,
                currentStep: 1,

                selectedRepository: '',
                selectedEnvironment: '',
                selectedFormat: '',

                versionSearchBase: '',
                versionSearchHead: '',
                versionTypeFilterBase: '',
                versionTypeFilterHead: '',
                selectedVersionBase: '',
                selectedVersionHead: '',

                repoData: null,
                repoBranches: [],
                repoTags: [],
                repoReleases: [],
                isLoadingVersions: false,
                rateLimit: null,

                isPackaging: false,
                confirmation: false,
                hovered: false,
                abortController: null,

                // Overall derived progress (comes from backend weighted computation)
                packagingProgress: 0,

                // Stage-level progress fields
                fileDownloadProgress: 0,
                headFileExtraction: 0,
                baseFileExtraction: 0,
                compareFilesProgress: 0,
                packageGenProgress: 0,
                compressionProgress: 0,

                packagingMessage: 'Ready to generate package.',
                packagingError: '',
                packagingResult: null,

                customNaming: false,
                packageName: '',

                get generatedName() {
                    const env = this.selectedEnvironment || '';
                    const proj = this.selectedRepositoryLabel || '[Project]';
                    const base = this.selectedBaseLabel || '[Base]';
                    const head = this.selectedHeadLabel || '[Head]';

                    const now = new Date();
                    const yyyy = now.getFullYear();
                    const mm = String(now.getMonth() + 1).padStart(2, '0');
                    const dd = String(now.getDate()).padStart(2, '0');
                    const hh = String(now.getHours()).padStart(2, '0');
                    const min = String(now.getMinutes()).padStart(2, '0');
                    const timeStr = `${yyyy}${mm}${dd}-${hh}${min}`;

                    return `${env}-${proj}-${base}-to-${head}-${timeStr}`;
                },

                updateName() {
                    if (!this.customNaming) {
                        this.packageName = this.generatedName;
                    }
                },

                get selectedRepositoryLabel() {
                    const repo = this.repositories.find(r => r.id === this.selectedRepository);
                    return repo ? repo.label : this.selectedRepository;
                },

                async fetchRateLimit() {
                    try {
                        const response = await fetch('/github/rate-limit');
                        this.rateLimit = await response.json();
                        console.log(response);
                    } catch (e) {
                        console.error('Rate limit fetch failed', e);
                    }
                },

                get allRepoVersions() {
                    const branches = this.repoBranches.map(branch => ({
                        unique_key: `branch:${branch.name}`,
                        type: 'branch',
                        name: branch.name,
                        ref: branch.name,
                        subtitle: branch.commit?.sha
                            ? `Latest SHA: ${branch.commit.sha.substring(0, 7)}`
                            : 'Branch',
                        date: '',
                        asset_count: null,
                        is_prerelease: false,
                        is_draft: false
                    }));

                    const tags = this.repoTags.map(tag => ({
                        unique_key: `tag:${tag.name}`,
                        type: 'tag',
                        name: tag.name,
                        ref: tag.name,
                        subtitle: tag.commit?.sha
                            ? `Tagged commit: ${tag.commit.sha.substring(0, 7)}`
                            : 'Tag',
                        date: '',
                        asset_count: null,
                        is_prerelease: false,
                        is_draft: false
                    }));

                    const releases = this.repoReleases.map(release => ({
                        unique_key: `release:${release.id}`,
                        type: 'release',
                        name: release.name || release.tag_name,
                        ref: release.tag_name,
                        subtitle: release.tag_name
                            ? `Tag: ${release.tag_name}`
                            : 'Release',
                        date: release.published_at
                            ? new Date(release.published_at).toLocaleDateString()
                            : '',
                        asset_count: Array.isArray(release.assets) ? release.assets.length : 0,
                        is_prerelease: !!release.prerelease,
                        is_draft: !!release.draft
                    }));

                    return [...releases, ...tags, ...branches];
                },

                filterVersions(search, typeFilter) {
                    const keyword = (search || '').trim().toLowerCase();

                    return this.allRepoVersions.filter(version => {
                        const matchesType =
                            typeFilter === '' ||
                            version.type === typeFilter;

                        const matchesSearch =
                            keyword === '' ||
                            version.name.toLowerCase().includes(keyword) ||
                            version.subtitle.toLowerCase().includes(keyword) ||
                            (version.ref && version.ref.toLowerCase().includes(keyword));

                        return matchesType && matchesSearch;
                    });
                },

                get filteredVersionsBase() {
                    return this.filterVersions(this.versionSearchBase, this.versionTypeFilterBase);
                },

                get filteredVersionsHead() {
                    return this.filterVersions(this.versionSearchHead, this.versionTypeFilterHead);
                },

                get selectedBaseLabel() {
                    const v = this.allRepoVersions.find(x => x.unique_key === this.selectedVersionBase);
                    return v ? v.name : '';
                },

                get selectedHeadLabel() {
                    const v = this.allRepoVersions.find(x => x.unique_key === this.selectedVersionHead);
                    return v ? v.name : '';
                },

                get comparisonReady() {
                    return !!(this.selectedVersionBase && this.selectedVersionHead);
                },

                init() {
                    this.fetchRateLimit();

                    this.$watch('selectedRepository', async () => {
                        await this.fetchRepoData();
                        await this.fetchRepoVersions();
                        await this.fetchRateLimit();
                        this.updateName();
                    });

                    this.$watch('selectedEnvironment', () => this.updateName());
                    this.$watch('selectedVersionBase', () => { setTimeout(() => this.updateName(), 50); });
                    this.$watch('selectedVersionHead', () => { setTimeout(() => this.updateName(), 50); });
                    this.$watch('customNaming', (val) => {
                        if (!val) this.updateName();
                    });
                    setInterval(() => this.updateName(), 60000);
                },

                async fetchRepoData() {
                    if (!this.selectedRepository) {
                        this.repoData = null;
                        return;
                    }

                    try {
                        const response = await fetch(`/github/repo-info?repo=${encodeURIComponent(this.selectedRepository)}`);
                        const data = await response.json();

                        if (!response.ok) {
                            this.repoData = null;
                            return;
                        }

                        this.repoData = data;
                    } catch (error) {
                        console.error('Failed to fetch repo info:', error);
                        this.repoData = null;
                    }
                },

                async fetchRepoVersions() {
                    if (!this.selectedRepository) {
                        this.repoBranches = [];
                        this.repoTags = [];
                        this.repoReleases = [];
                        this.selectedVersionBase = '';
                        this.selectedVersionHead = '';
                        return;
                    }

                    this.isLoadingVersions = true;
                    this.selectedVersionBase = '';
                    this.selectedVersionHead = '';

                    try {
                        const response = await fetch(`/github/repo-versions?repo=${encodeURIComponent(this.selectedRepository)}`);
                        const data = await response.json();

                        if (!response.ok) {
                            this.repoBranches = [];
                            this.repoTags = [];
                            this.repoReleases = [];
                            return;
                        }

                        this.repoBranches = data.branches || [];
                        this.repoTags = data.tags || [];
                        this.repoReleases = data.releases || [];
                    } catch (error) {
                        console.error('Failed to fetch repo versions:', error);
                        this.repoBranches = [];
                        this.repoTags = [];
                        this.repoReleases = [];
                    } finally {
                        this.isLoadingVersions = false;
                    }
                },

                async runPackaging() {
                    // Update validation to also consider format selection since we're now in one page
                    if (!this.selectedEnvironment || !this.selectedRepositoryLabel || !this.selectedVersionBase || !this.selectedVersionHead || !this.selectedFormat) {
                        this.packagingError = 'Please complete all selections before generating the package.';
                        return;
                    }

                    this.isPackaging = true;
                    this.abortController = new AbortController();
                    this.packagingError = '';
                    this.packagingResult = null;

                    // Reset all stage progress
                    this.packagingProgress = 0;
                    this.fileDownloadProgress = 0;
                    this.headFileExtraction = 0;
                    this.baseFileExtraction = 0;
                    this.compareFilesProgress = 0;
                    this.packageGenProgress = 0;
                    this.compressionProgress = 0;

                    // Snapshot the package name NOW so the 60-second updateName()
                    // interval cannot change it mid-run and break the polling URL.
                    const frozenPackageName = this.packageName;

                    this.packagingMessage = 'Validating selected versions...';

                    let timeoutId = setTimeout(() => {
                        if (this.isPackaging) {
                            this.packagingMessage = 'It is taking longer than usual... Please wait.';
                        }
                    }, 60000);

                    const pollProgress = setInterval(async () => {
                        try {
                            const res = await fetch(`/deployments/progress/${encodeURIComponent(frozenPackageName)}?t=${Date.now()}`, {
                                cache: 'no-store',
                                headers: {
                                    'Accept': 'application/json',
                                    'Cache-Control': 'no-cache',
                                    'Pragma': 'no-cache',
                                },
                            });
                            if (res.ok) {
                                const prog = await res.json();
                                console.log('progress payload', prog);
                                if (this.isPackaging && !this.packagingResult) {
                                    // Overall packaging progress – trust backend weighted value
                                    if (prog.packagingProgress !== undefined && Number(prog.packagingProgress) > this.packagingProgress)
                                        this.packagingProgress = Number(prog.packagingProgress);

                                    // Stage fields – only advance, never retreat
                                    if (prog.fileDownloadProgress !== undefined && Number(prog.fileDownloadProgress) > this.fileDownloadProgress)
                                        this.fileDownloadProgress = Number(prog.fileDownloadProgress);
                                    if (prog.headFileExtraction !== undefined && Number(prog.headFileExtraction) > this.headFileExtraction)
                                        this.headFileExtraction = Number(prog.headFileExtraction);
                                    if (prog.baseFileExtraction !== undefined && Number(prog.baseFileExtraction) > this.baseFileExtraction)
                                        this.baseFileExtraction = Number(prog.baseFileExtraction);
                                    if (prog.compareFilesProgress !== undefined && Number(prog.compareFilesProgress) > this.compareFilesProgress)
                                        this.compareFilesProgress = Number(prog.compareFilesProgress);
                                    if (prog.packageGenProgress !== undefined && Number(prog.packageGenProgress) > this.packageGenProgress)
                                        this.packageGenProgress = Number(prog.packageGenProgress);
                                    if (prog.compressionProgress !== undefined && Number(prog.compressionProgress) > this.compressionProgress)
                                        this.compressionProgress = Number(prog.compressionProgress);

                                    if (prog.packagingMessage) this.packagingMessage = prog.packagingMessage;
                                }
                            }
                        } catch (e) {
                            // Suppress errors during polling
                        }
                    }, 1000);

                    try {
                        this.packagingMessage = 'Reading Git differences and preparing package folders...';

                        const base_obj = this.allRepoVersions.find(x => x.unique_key === this.selectedVersionBase);
                        const head_obj = this.allRepoVersions.find(x => x.unique_key === this.selectedVersionHead);

                        const base_ref = base_obj ? base_obj.ref : this.selectedVersionBase.split(':').slice(1).join(':');
                        const head_ref = head_obj ? head_obj.ref : this.selectedVersionHead.split(':').slice(1).join(':');

                        const response = await fetch(generateUrl, {
                            method: 'POST',
                            signal: this.abortController.signal,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                environment: this.selectedEnvironment,
                                project_name: this.selectedRepositoryLabel,
                                base_version: base_ref,
                                head_version: head_ref,
                                repo: this.selectedRepository,
                                package_name: frozenPackageName,
                                format: this.selectedFormat // Passed format
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok || data.status === 'error') {
                            throw new Error(data.message || 'Packaging failed.');
                        }

                        // Mark all stages complete
                        this.packagingProgress = 100;
                        this.fileDownloadProgress = 100;
                        this.headFileExtraction = 100;
                        this.baseFileExtraction = 100;
                        this.compareFilesProgress = 100;
                        this.packageGenProgress = 100;
                        this.compressionProgress = 100;
                        this.packagingMessage = 'Package created successfully.';
                        this.packagingResult = data;
                    } catch (error) {
                        this.packagingProgress = 0;
                        if (error.name === 'AbortError') {
                            this.packagingMessage = 'Packaging stopped manually.';
                            this.packagingError = 'Operation was aborted by the user.';
                        } else {
                            this.packagingMessage = 'Packaging stopped.';
                            this.packagingError = error.message || 'Unexpected error during packaging.';
                        }
                    } finally {
                        clearInterval(pollProgress);
                        clearTimeout(timeoutId);
                        this.isPackaging = false;
                    }
                },

                stopPackaging() {
                    if (this.abortController) {
                        this.abortController.abort();
                    }
                },
            };
        }
    </script>
@endpush