@extends('layouts.app')

@section('title', 'New Package')

@section('content')
    <div class="max-w-6xl mx-auto space-y-8 pt-4" x-data="{
        currentStep: 1,
        selectedRepository: '',
        selectedVersion: '',
        repositories: @js($repositories),

        versionSearch: '',
        versionTypeFilter: '',

        repoData: null,
        repoBranches: [],
        repoTags: [],
        repoReleases: [],
        isLoadingVersions: false,

        get testurl() {
            return `/github/repo-info?repo=${encodeURIComponent(this.selectedRepository || '')}`;
        },

        get selectedRepoData() {
            return this.repositories.find(repo => repo.id === this.selectedRepository) || null;
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

        get filteredVersions() {
            const keyword = this.versionSearch.trim().toLowerCase();

            return this.allRepoVersions.filter(version => {
                const matchesType =
                    this.versionTypeFilter === '' ||
                    version.type === this.versionTypeFilter;

                const matchesSearch =
                    keyword === '' ||
                    version.name.toLowerCase().includes(keyword) ||
                    version.subtitle.toLowerCase().includes(keyword) ||
                    (version.ref && version.ref.toLowerCase().includes(keyword));

                return matchesType && matchesSearch;
            });
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
                this.selectedVersion = '';
                return;
            }

            this.isLoadingVersions = true;
            this.selectedVersion = '';

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
        }
    }"
    x-init="
        $watch('selectedRepository', async () => {
            await fetchRepoData();
            await fetchRepoVersions();
        });
    "
    >
            
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

            <div x-show="currentStep < 3">
                <x-ui.step-item number="3" label="Packaging Options" />
            </div>
            <div x-show="currentStep === 3">
                <x-ui.step-item number="3" label="Packaging Options" :active="true" />
            </div>
            <div x-show="currentStep > 3">
                <x-ui.step-item number="✓" label="Packaging Options" :completed="true" />
            </div>

            <div x-show="currentStep < 4">
                <x-ui.step-item number="4" label="Packaging" />
            </div>
            <div x-show="currentStep === 4">
                <x-ui.step-item number="4" label="Packaging" :active="true" />
            </div>
            <div x-show="currentStep > 4">
                <x-ui.step-item number="✓" label="Packaging" :completed="true" />
            </div>

            <div x-show="currentStep < 5">
                <x-ui.step-item number="5" label="Download" :last="true" />
            </div>
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
                    <p>Selected Version: <span x-text="selectedVersion" class="text-black"></span></p>
                    <p>Repo Data: <span x-text="repoData ? 'Loaded' : 'Not Loaded'" class="text-black"></span></p>
                    <p>Branches: <span x-text="repoBranches.length" class="text-black   "></span></p>
                    <p>selectedRepoData: <span x-text="selectedRepoData" class="text-black"></span></p>
                    <p>testurl: <span x-text="testurl" class="text-black"></span></p>
                </div>
            </footer>
    </div>

@endsection

@push('scripts')
    <script>
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        const progressLabel = document.getElementById('progressLabel');
        const logBox = document.getElementById('logBox');
        const nextBtn = document.getElementById('nextBtn');

        function setProgress(percent, label) {
            progressBar.style.width = percent + '%';
            progressPercent.textContent = percent + '%';
            if (label) progressLabel.textContent = label;
        }

        function addLog(text) {
            const line = document.createElement('div');
            line.textContent = text;
            logBox.appendChild(line);
        }

        async function startPackaging() {
            try {
                // Step 1
                addLog('Initializing process... (Done)');
                setProgress(10);

                await delay(800);

                // Step 2
                addLog('Pulling source code... (Done)');
                setProgress(30);

                await delay(1000);

                // Step 3
                addLog('Optimizing assets (CSS/JS)... (65% complete)');
                setProgress(65);

                await delay(1200);

                // Step 4
                addLog('Compressing files... (Done)');
                setProgress(90);

                await delay(800);

                // Final request (simulate backend packaging)
                const response = await axios.get('/dummy-package');

                addLog('Finalizing package... (Done)');
                setProgress(100, 'Completed');

                nextBtn.disabled = false;
                nextBtn.classList.remove('opacity-50');
                nextBtn.textContent = 'Continue';

            } catch (error) {
                addLog('Error occurred during packaging.');
                console.error(error);
            }
        }

        function delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        // auto start when page loads
        document.addEventListener('DOMContentLoaded', startPackaging);
    </script>
@endpush