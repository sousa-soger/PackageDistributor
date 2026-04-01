@extends('layouts.app')

@section('title', 'New Package')

@section('content')
    <div class="max-w-6xl mx-auto space-y-8 pt-4" x-data="newPackageWizard({
                                                                repositories: @js($repositories),
                                                                generateUrl: '{{ route('deployments.generate-delta') }}',
                                                                csrfToken: '{{ csrf_token() }}'
                                                            })">

        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Create Distribution Package</h1>
            </div>

            <div class="text-sm text-slate-400 font-medium">
                <span x-text="`Step ${currentStep} of 5`"></span>
            </div>
        </div>

        <!-- Step Progress -->
        <div class="flex items-center">
            <div x-show="currentStep === 1">
                <x-ui.step-item number="1" label="Project Selection" :active="true" />
            </div>
            <button x-show="currentStep > 1" @click="currentStep = 1">
                <x-ui.step-item number="✓" label="Project Selection" :completed="true" />
            </button>

            <div x-show="currentStep < 2">
                <x-ui.step-item number="2" label="Version Selection" />
            </div>
            <div x-show="currentStep === 2">
                <x-ui.step-item number="2" label="Version Selection" :active="true" />
            </div>
            <div x-show="currentStep > 2">
                <x-ui.step-item number="✓" label="Version Selection" :completed="true" />
            </div>
            {{-- Make sure to remove this button when done --}}
            <button x-show="currentStep < 3" @click="currentStep = 3">
                <x-ui.step-item number="3" label="Packaging Options" />
            </button>
            <div x-show="currentStep === 3">
                <x-ui.step-item number="3" label="Packaging Options" :active="true" />
            </div>
            <button x-show="currentStep > 3" @click="currentStep = 3">
                <x-ui.step-item number="✓" label="Packaging Options" :completed="true" />
            </button>
            {{-- Make sure to remove this button when done --}}
            <button x-show="currentStep < 4" @click="currentStep = 4">
                <x-ui.step-item number="4" label="Packaging" />
            </button>
            <div x-show="currentStep === 4">
                <x-ui.step-item number="4" label="Packaging" :active="true" />
            </div>
            <div x-show="currentStep > 4">
                <x-ui.step-item number="✓" label="Packaging" :completed="true" />
            </div>
            {{-- Make sure to remove this button when done --}}
            <button x-show="currentStep < 5" @click="currentStep = 5">
                <x-ui.step-item number="5" label="Download" :last="true" />
            </button>
            <div x-show="currentStep === 5">
                <x-ui.step-item number="5" label="Download" :active="true" :last="true" />
            </div>
            <div x-show="currentStep > 5">
                <x-ui.step-item number="✓" label="Download" :completed="true" :last="true" />
            </div>

        </div>

        <div x-show="currentStep === 1" x-cloak>
            @include('components.step-card-new-package.1')
        </div>

        <!-- Step 2 -->
        <div x-show="currentStep === 2" x-cloak>
            @include('components.step-card-new-package.2')
        </div>

        <!-- Step 3 -->
        <div x-show="currentStep === 3" x-cloak>
            @include('components.step-card-new-package.3')
        </div>

        <!-- Step 4 -->
        <div x-show="currentStep === 4" x-cloak>
            @include('components.step-card-new-package.4')
        </div>

        <!-- Step 5 -->
        <div x-show="currentStep === 5" x-cloak>
            @include('components.step-card-new-package.5')
        </div>

        {{-- Testing purposes --}}
        <footer class="pt-6 text-sm text-slate-500">
            <p>Testing Information:</p>
            <div>
                <p>Current Step: <span x-text="currentStep" class="text-black"></span></p>
                <p>Selected Repository: <span x-text="selectedRepository" class="text-black"></span></p>
                <p>Repositories: <span x-text="repositories.length" class="text-black"></span></p>
                <p>Base / Head: <span x-text="selectedVersionBase" class="text-black"></span> → <span
                        x-text="selectedVersionHead" class="text-black"></span></p>
                <p>Selected Base Label: <span x-text="selectedBaseLabel" class="text-black"></span></p>
                <p>Selected Head Label: <span x-text="selectedHeadLabel" class="text-black"></span></p>
                <p>Filtered Base Versions: <span x-text="filteredVersionsBase.length" class="text-black"></span></p>
                <p>Filtered Head Versions: <span x-text="filteredVersionsHead.length" class="text-black"></span></p>
                <p>Repo Data: <span x-text="repoData ? 'Loaded' : 'Not Loaded'" class="text-black"></span></p>
                <p>Branches: <span x-text="repoBranches.length" class="text-black   "></span></p>
                <p>GitHub API Limit: <template x-if="rateLimit">
                        <span class="text-black"
                            x-text="`${rateLimit.resources.core.remaining} / ${rateLimit.resources.core.limit} (Resets ${new Date(rateLimit.resources.core.reset * 1000).toLocaleTimeString()})`"></span>
                    </template></p>

                <template
                    x-if="packagingResult && packagingResult.changed_files && packagingResult.changed_files.length > 0">
                    <div class="mt-4 border-t border-slate-200 pt-4 max-w-2xl">
                        <p class="font-bold text-slate-800">Files changed from <span x-text="selectedVersionBase"></span> to
                            <span x-text="selectedVersionHead"></span>:
                        </p>
                        <ul
                            class="list-disc pl-5 mt-2 max-h-64 overflow-y-auto w-full text-slate-600 bg-slate-50 p-2 rounded border border-slate-200">
                            <template x-for="file in packagingResult.changed_files" :key="file.filename">
                                <li class="text-xs font-mono truncate" :title="file.filename"
                                    x-html="`<span class='font-semibold text-slate-500 uppercase mr-1'>[${file.status}]</span> ${file.filename}`">
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>
            </div>
        </footer>
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
                abortController: null,
                packagingProgress: 0,
                packagingMessage: 'Ready to generate package.',
                packagingError: '',
                packagingResult: null,

                customNaming: false,
                packageName: '',

                get generatedName() {
                    const env = this.selectedEnvironment || '[Env]';
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
                    if (!this.selectedEnvironment || !this.selectedRepositoryLabel || !this.selectedVersionBase || !this.selectedVersionHead) {
                        this.packagingError = 'Please complete steps 1 to 3 before generating the package.';
                        return;
                    }

                    this.isPackaging = true;
                    this.abortController = new AbortController();
                    this.packagingError = '';
                    this.packagingResult = null;
                    this.packagingProgress = 10;
                    this.packagingMessage = 'Validating selected versions...';

                    let timeoutId = setTimeout(() => {
                        if (this.isPackaging) {
                            this.packagingMessage = 'It is taking longer than usual... Please wait.';
                        }
                    }, 60000); // 1 minute

                    try {
                        this.packagingProgress = 25;
                        this.packagingMessage = 'Reading Git differences and preparing package folders...';

                        const base_obj = this.allRepoVersions.find(x => x.unique_key === this.selectedVersionBase);
                        const head_obj = this.allRepoVersions.find(x => x.unique_key === this.selectedVersionHead);

                        // Use the actual 'ref' (like branch or tag name) instead of splitting the ID
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
                                package_name: this.packageName,
                            }),
                        });

                        this.packagingProgress = 75;
                        this.packagingMessage = 'Finalizing update and rollback packages...';

                        const data = await response.json();

                        if (!response.ok || data.status === 'error') {
                            throw new Error(data.message || 'Packaging failed.');
                        }

                        this.packagingProgress = 100;
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