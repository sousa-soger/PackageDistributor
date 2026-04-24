@extends('layouts.app')

@section('title', 'New Package')

@section('content')
    <div class="max-w-7xl mx-auto space-y-8 pt-4 pb-12" x-data="newPackageWizard({
                        repositories: @js($repositories),
                queueUrl: '{{ route('deployments.queue-job') }}',
                jobProgressBaseUrl: '{{ url('/deployments/jobs') }}',
                downloadUrl: '{{ route('download.archive') }}',
                csrfToken: '{{ csrf_token() }}',
                completedPackages: @js($packages),
                dbQueuedPackages: @js($queuedPackages),
                vcsProvider: @js($vcsProvider),
                gitlabConnected: @js($gitlabConnected)
            })">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">New Package</h1>
        </div>
        {{-- ================================================================ --}}
        {{-- CARD 1 — Repository selection + multi-row version picker --}}
        {{-- ================================================================ --}}
        @include('components.packaging-wizardV3.selection-card')
        {{--GLOBAL FLOATING DROPDOWN (lives once, outside x-for)--}}
        @include('components.packaging-wizardV3.version-dropdown')
        {{-- ================================================================ --}}


        {{-- ================================================================ --}}
        {{-- new CARD 2 — Queue + Progress + Result --}}
        {{-- ================================================================ --}}
        @include('components.packaging-wizardV3.active-jobs-card')
        {{-- ================================================================ --}}


        {{-- ================================================================ --}}
        {{-- CARD 3 — View list of previously generated packages by the user --}}
        {{-- ================================================================ --}}
        @include('components.packaging-wizardV3.history-card')
        {{-- ================================================================ --}}

    </div>
    
@endsection

@push('scripts')
    <script>
        function newPackageWizard({ repositories, queueUrl, jobProgressBaseUrl, downloadUrl, csrfToken, completedPackages, dbQueuedPackages, vcsProvider, gitlabConnected }) {
            return {
                // ── Provider ─────────────────────────────────────────────────
                vcsProvider,
                gitlabConnected,

                // ── Repository & version state ───────────────────────────────
                repositories,
                selectedRepository: '',
                repoData: null,
                repoBranches: [],
                repoTags: [],
                repoReleases: [],
                isLoadingVersions: false,
                rateLimit: null,

                // ── GitLab project browser state ──────────────────────────────
                gitlabProjects: [],        
                gitlabExploreProjects: [], 
                gitlabSearch: '',
                activeTab: 'projects',   // 'projects' | 'personal' | 'shared' | 'all'
                toggle: false,
                gitlabLoading: false,
                gitlabLoadingExplore: false,

                get allVisibleProjects() {
                    if (this.activeTab === 'all' && this.toggle) {
                        return [...this.gitlabProjects, ...this.gitlabExploreProjects];
                    }
                    return this.gitlabProjects;
                },

                get filteredGitlabProjects() {
                    return this.allVisibleProjects.filter(p => {
                        const q = this.gitlabSearch.toLowerCase();
                        const matchesSearch = q === '' ||
                            p.name.toLowerCase().includes(q) ||
                            (p.path && p.path.toLowerCase().includes(q));

                        const matchesTab = this.activeTab === 'all' ||
                            this.activeTab === 'projects' ||
                            p.category === this.activeTab;

                        return matchesSearch && matchesTab;
                    });
                },

                // ── Multi-row packaging table ────────────────────────────────
                packageRows: [
                    { id: Date.now(), base: '', head: '', environment: 'PROD', format: '.zip', customName: false, name: '' }
                ],

                // ── Global floating dropdown state ───────────────────────────
                floatDd: {
                    open: false,
                    rowIndex: null,
                    field: null,   // 'base' | 'head'
                    typeFilter: '',
                    searchQuery: '',
                    style: '',
                },

                // ── Queue / job state ────────────────────────────────────────
                unifiedQueue: dbQueuedPackages.map(dbJob => ({
                    jobId: dbJob.id,
                    status: dbJob.status,
                    created_at: dbJob.created_at,
                    // Carry DB-persisted progress, message, and error so non-active rows show their own state
                    progress: dbJob.progress ?? null,
                    statusMessage: dbJob.message ?? '',
                    errorMessage: dbJob.error_message ?? '',
                    row: {
                        environment: dbJob.environment,
                        project_name: dbJob.project_name,
                        base_version: dbJob.base_version,
                        head_version: dbJob.head_version,
                        name: dbJob.package_name,
                    }
                })),

                currentJobId: null,         // DB ID of the currently-running job
                isQueuing: false,           // true while POSTing to create jobs
                isRunning: false,           // true while polling and not yet terminal
                pollIntervalId: null,       // setInterval handle
                activeRow: null,            // snapshot of the row currently being processed

                // Multi-job queue
                jobQueue: [],               // array of { row, jobId } entries to process
                jobQueueIndex: 0,           // index of the currently running job
                jobResults: [],             // accumulated results [{row, result, error}]

                // ── Duplicate detection ──────────────────────────────────────
                completedPackages,          // all completed jobs for this user
                duplicateModal: { open: false, match: null },

                // ── Progress fields (same names as V2/V1 so the UI is identical) ──
                packagingProgress: 0,
                fileDownloadProgress: 0,
                headFileExtraction: 0,
                baseFileExtraction: 0,
                compareFilesProgress: 0,
                packageGenProgress: 0,
                compressionProgress: 0,
                packagingMessage: '',
                packagingResult: null,
                packagingError: '',

                // ── Computed ─────────────────────────────────────────────────

                get canStartQueue() {
                    // Repo selected AND at least one complete row (ignoring the trailing blank row)
                    return !!(this.selectedRepository && this.packageRows.some(r => this.isRowComplete(r)));
                },

                get canAddRow() {
                    // Show the "Add Job" button only when the last row is fully complete
                    if (!this.selectedRepository || this.packageRows.length === 0) return false;
                    const lastRow = this.packageRows[this.packageRows.length - 1];
                    return this.isRowComplete(lastRow);
                },


                get completeRows() {
                    return this.packageRows.filter(r => this.isRowComplete(r));
                },

                get totalJobs() {
                    return this.jobQueue.length;
                },

                get jobQueueLabel() {
                    if (this.totalJobs <= 1) return '';
                    return `(${this.jobQueueIndex + 1} of ${this.totalJobs})`;
                },

                get activeRowLabel() {
                    if (!this.activeRow) return '';
                    const bObj = this.allRepoVersions.find(v => v.unique_key === this.activeRow.base);
                    const hObj = this.allRepoVersions.find(v => v.unique_key === this.activeRow.head);
                    const b = bObj ? bObj.name : this.activeRow.base;
                    const h = hObj ? hObj.name : this.activeRow.head;
                    return `${b}  →  ${h}  (${this.activeRow.environment})`;
                },

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

                get floatDdCurrentValue() {
                    if (this.floatDd.rowIndex === null) return '';
                    const row = this.packageRows[this.floatDd.rowIndex];
                    return row ? (row[this.floatDd.field] || '') : '';
                },

                get floatDdVersions() {
                    const tf = this.floatDd.typeFilter;
                    const sq = this.floatDd.searchQuery.toLowerCase();
                    return this.allRepoVersions.filter(v => {
                        const matchType = !tf || v.type === tf;
                        const matchSearch = !sq || v.name.toLowerCase().includes(sq);
                        return matchType && matchSearch;
                    });
                },

                // ── Lifecycle ────────────────────────────────────────────────

                init() {
                    if (this.vcsProvider === 'github') {
                        this.fetchRateLimit();
                    } else if (this.vcsProvider === 'gitlab' && this.gitlabConnected) {
                        const saved = localStorage.getItem('gitlab_show_explore');
                        this.toggle = saved === 'true';

                        this.$watch('toggle', val => {
                            localStorage.setItem('gitlab_show_explore', val);
                            if (val && this.gitlabExploreProjects.length === 0 && !this.gitlabLoadingExplore) {
                                this.loadGitlabExploreProjects();
                            }
                        });

                        this.loadGitlabProjects().then(() => {
                            if (this.toggle && this.gitlabExploreProjects.length === 0) {
                                this.loadGitlabExploreProjects();
                            }
                        });
                    }
                    this.$watch('selectedRepository', async () => {
                        if (this.selectedRepository) {
                            this.isLoadingVersions = true;
                        }
                        this.floatDd.open = false;
                        this.packageRows = [
                            { id: Date.now(), base: '', head: '', environment: 'PROD', customName: false, name: '' }
                        ];
                        await this.fetchRepoData();
                        await this.fetchRepoVersions();
                        if (this.vcsProvider === 'github') await this.fetchRateLimit();
                    });
                    setInterval(() => this.updateRowNames(), 60000);

                    // ── Auto-resume polling on refresh ──────────────────────────────────
                    const activeJob = this.unifiedQueue.find(q => q.status === 'running' || q.status === 'pending' || q.status === 'queued');
                    if (activeJob) {
                        this.isRunning = true;
                        this.currentJobId = activeJob.jobId;
                        this.activeRow = activeJob.row;
                        this.packagingMessage = 'Resuming job tracking...';
                        this.startPolling();
                    }
                },

                // ── Row helpers ───────────────────────────────────────────────

                isRowComplete(row) {
                    return !!(row.base && row.head && row.environment && row.name);
                },

                isRowReadyForName(row) {
                    return !!(row.base && row.head && row.environment);
                },

                handleRowInteract(index) {
                    this.updateRowNames();
                    const row = this.packageRows[index];
                    if (row) this.checkDuplicate(row);
                },

                checkDuplicate(row) {
                    if (!row.base || !row.head || !this.selectedRepository) return;

                    const baseObj = this.allRepoVersions.find(v => v.unique_key === row.base);
                    const headObj = this.allRepoVersions.find(v => v.unique_key === row.head);
                    const baseRef = baseObj ? baseObj.ref : row.base.split(':').slice(1).join(':');
                    const headRef = headObj ? headObj.ref : row.head.split(':').slice(1).join(':');

                    const match = this.completedPackages.find(p =>
                        p.repo === this.selectedRepository &&
                        p.base_version === baseRef &&
                        p.head_version === headRef &&
                        p.environment === row.environment
                    );

                    if (match) {
                        this.duplicateModal = { open: true, match };
                    }
                },

                addRow() {
                    const prev = this.packageRows[this.packageRows.length - 1];
                    this.packageRows.push({
                        id: Date.now(),
                        base: '', head: '',
                        environment: prev ? (prev.environment || 'PROD') : 'PROD',
                        customName: false, name: ''
                    });
                },

                removeRow(index) {
                    // Close floating dropdown if it was open on this row
                    if (this.floatDd.open && this.floatDd.rowIndex === index) {
                        this.floatDd.open = false;
                    }
                    this.packageRows.splice(index, 1);
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

                resetFormFields() {
                    // Close any open floating dropdown
                    this.floatDd.open = false;
                    // Reset rows back to a single blank row
                    this.packageRows = [
                        { id: Date.now(), base: '', head: '', environment: 'PROD', customName: false, name: '' }
                    ];
                },

                // ── Floating dropdown helpers ─────────────────────────────────

                openFloatDd(btn, rowIndex, field) {
                    if (this.floatDd.open && this.floatDd.rowIndex === rowIndex && this.floatDd.field === field) {
                        this.floatDd.open = false;
                        return;
                    }
                    const r = btn.getBoundingClientRect();
                    this.floatDd.style = `position:fixed;z-index:99999;top:${r.bottom + 6}px;left:${r.left}px;width:${Math.max(r.width, 240)}px`;
                    this.floatDd.rowIndex = rowIndex;
                    this.floatDd.field = field;
                    this.floatDd.typeFilter = '';
                    this.floatDd.searchQuery = '';
                    this.floatDd.open = true;
                    // Auto-focus search bar
                    this.$nextTick(() => {
                        if (this.$refs.searchBar) this.$refs.searchBar.focus();
                    });
                },

                selectFloatVersion(versionKey) {
                    if (this.floatDd.rowIndex === null) return;
                    const row = this.packageRows[this.floatDd.rowIndex];
                    if (!row) return;

                    // If selecting Head and it matches existing Base, clear Base and show toast
                    if (this.floatDd.field === 'head' && row.base === versionKey) {
                        row.base = '';
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'warning', message: 'Versions Identical. Current version was cleared.' } }));
                    }
                    // If selecting Base and it matches existing Head, clear Head and show toast
                    if (this.floatDd.field === 'base' && row.head === versionKey) {
                        row.head = '';
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'warning', message: 'Versions Identical. Target version was cleared.' } }));
                    }

                    row[this.floatDd.field] = versionKey;
                    this.handleRowInteract(this.floatDd.rowIndex);
                    this.floatDd.open = false;
                },

                isFloatDdOpen(rowIndex, field) {
                    return this.floatDd.open && this.floatDd.rowIndex === rowIndex && this.floatDd.field === field;
                },

                // ── Queue-based packaging ─────────────────────────────────────

                async startPackaging() {
                    const rows = this.completeRows;
                    // Prevent double-submission of already active jobs
                    const activeNames = this.unifiedQueue
                        .filter(q => ['running', 'pending', 'queued'].includes(q.status))
                        .map(q => q.row.name);

                    const finalRows = rows.filter(r => !activeNames.includes(r.name));
                    if (finalRows.length === 0) {
                        this.packagingError = 'Selected packages are already in the queue or running.';
                        return;
                    }

                    // Reset all state for a fresh multi-job run
                    this.isQueuing = true;
                    this.isRunning = false;
                    this.packagingResult = null;
                    this.packagingError = '';
                    this.currentJobId = null;
                    this.jobQueue = [];
                    this.jobQueueIndex = 0;
                    this.jobResults = [];
                    this.packagingMessage = `Submitting ${finalRows.length} job(s) to queue...`;

                    // ── Submit ALL complete rows to the backend at once ──────
                    try {
                        for (const row of finalRows) {
                            const baseObj = this.allRepoVersions.find(v => v.unique_key === row.base);
                            const headObj = this.allRepoVersions.find(v => v.unique_key === row.head);
                            const baseRef = baseObj ? baseObj.ref : row.base.split(':').slice(1).join(':');
                            const headRef = headObj ? headObj.ref : row.head.split(':').slice(1).join(':');

                            const res = await fetch(queueUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    environment: row.environment,
                                    project_name: this.selectedRepositoryLabel,
                                    base_version: baseRef,
                                    head_version: headRef,
                                    repo: this.selectedRepository,
                                    package_name: row.name,
                                }),
                            });

                            const data = await res.json();
                            if (!res.ok || !data.job_id) {
                                throw new Error(data.message || `Failed to queue job for row: ${row.name}`);
                            }

                            const jobEntry = {
                                row: {
                                    ...row,
                                    project_name: this.selectedRepositoryLabel,
                                    base_version: baseRef,
                                    head_version: headRef
                                },
                                jobId: data.job_id,
                                status: data.status,
                                created_at: new Date().toISOString(),
                                progress: null,      // populated once job completes/fails
                                statusMessage: '',
                                errorMessage: '',
                            };
                            this.jobQueue.push(jobEntry);
                            // Unshift pushes it to the TOP of the unified display queue
                            this.unifiedQueue.unshift(jobEntry);
                        }
                    } catch (err) {
                        this.packagingError = err.message || 'Unknown error submitting jobs.';
                        this.packagingMessage = 'Failed to queue jobs.';
                        this.isQueuing = false;
                        return;
                    }

                    this.isQueuing = false;
                    this.isRunning = true;
                    this.packagingMessage = `All ${this.jobQueue.length} job(s) queued — starting...`;

                    // ── Clear the form so it's ready for the next batch ──────
                    this.resetFormFields();
                    
                    this.$nextTick(() => {
                        document.getElementById('active-jobs-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });

                    // ── Process jobs sequentially ────────────────────────────
                    await this.processNextJob();
                },

                async processNextJob() {
                    if (this.jobQueueIndex >= this.jobQueue.length) {
                        // All done
                        this.isRunning = false;
                        this.packagingMessage = `All ${this.jobQueue.length} package(s) completed.`;
                        return;
                    }

                    const entry = this.jobQueue[this.jobQueueIndex];
                    this.activeRow = entry.row;
                    this.currentJobId = entry.jobId;

                    // Reset per-job progress bars
                    this.packagingProgress = 0;
                    this.fileDownloadProgress = 0;
                    this.headFileExtraction = 0;
                    this.baseFileExtraction = 0;
                    this.compareFilesProgress = 0;
                    this.packageGenProgress = 0;
                    this.compressionProgress = 0;
                    this.packagingResult = null;
                    this.packagingError = '';

                    const label = this.jobQueueLabel ? ` ${this.jobQueueLabel}` : '';
                    this.packagingMessage = `Job #${this.currentJobId}${label} queued — waiting for worker...`;

                    this.startPolling();
                },

                startPolling() {
                    this.stopPolling(); // clear any previous interval

                    this.pollIntervalId = setInterval(async () => {
                        if (!this.currentJobId) { this.stopPolling(); return; }

                        try {
                            const res = await fetch(
                                `${jobProgressBaseUrl}/${this.currentJobId}/progress?t=${Date.now()}`,
                                {
                                    cache: 'no-store',
                                    headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' },
                                }
                            );

                            if (!res.ok) return;

                            const payload = await res.json();
                            const prog = payload.progress || {};

                            // Let's propagate the new status to the unified list to instantly show "running" tag
                            const uq = this.unifiedQueue.find(q => q.jobId === this.currentJobId);
                            if (uq) uq.status = payload.status;

                            // Advance stage fields (only forward, never retreat)
                            const adv = (key, val) => {
                                if (val !== undefined && Number(val) > this[key]) this[key] = Number(val);
                            };
                            adv('packagingProgress', prog.packagingProgress);
                            adv('fileDownloadProgress', prog.fileDownloadProgress);
                            adv('headFileExtraction', prog.headFileExtraction);
                            adv('baseFileExtraction', prog.baseFileExtraction);
                            adv('compareFilesProgress', prog.compareFilesProgress);
                            adv('packageGenProgress', prog.packageGenProgress);
                            adv('compressionProgress', prog.compressionProgress);
                            if (prog.packagingMessage) this.packagingMessage = prog.packagingMessage;

                            // Terminal states
                            if (payload.status === 'completed') {
                                this.stopPolling();
                                // Force all bars to 100
                                this.packagingProgress = 100;
                                this.fileDownloadProgress = this.headFileExtraction = this.baseFileExtraction =
                                    this.compareFilesProgress = this.packageGenProgress = this.compressionProgress = 100;
                                this.packagingMessage = 'Package created successfully.';
                                this.packagingResult = payload.result;
                                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Package created successfully.' } }));
                                

                                // Snapshot the final progress into the unifiedQueue entry so the
                                // detail panel shows correct data after this job is no longer active
                                if (uq) {
                                    uq.progress = {
                                        packagingProgress: 100,
                                        fileDownloadProgress: 100,
                                        headFileExtraction: 100,
                                        baseFileExtraction: 100,
                                        compareFilesProgress: 100,
                                        packageGenProgress: 100,
                                        compressionProgress: 100,
                                    };
                                    uq.statusMessage = 'Package created successfully.';
                                    uq.errorMessage = '';
                                }

                                // Record result and advance to next job
                                this.jobResults.push({ row: this.activeRow, result: payload.result, error: null });
                                this.jobQueueIndex++;

                                if (this.jobQueueIndex < this.jobQueue.length) {
                                    // Slight delay so the user can see the completion state
                                    setTimeout(() => this.processNextJob(), 1200);
                                } else {
                                    this.isRunning = false;
                                    this.packagingMessage = `All ${this.jobQueue.length} package(s) completed successfully.`;
                                }
                            }

                            if (payload.status === 'failed') {
                                this.stopPolling();
                                this.packagingError = payload.error || 'Job failed.';
                                this.packagingMessage = 'Packaging failed.';
                                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Job failed: ' + this.packagingError } }));

                                // Snapshot the failure into the unifiedQueue entry
                                if (uq) {
                                    uq.progress = {
                                        packagingProgress: this.packagingProgress,
                                        fileDownloadProgress: this.fileDownloadProgress,
                                        headFileExtraction: this.headFileExtraction,
                                        baseFileExtraction: this.baseFileExtraction,
                                        compareFilesProgress: this.compareFilesProgress,
                                        packageGenProgress: this.packageGenProgress,
                                        compressionProgress: this.compressionProgress,
                                    };
                                    uq.statusMessage = 'Packaging failed.';
                                    uq.errorMessage = this.packagingError;
                                }

                                // Record failure and stop (don't auto-advance on error)
                                this.jobResults.push({ row: this.activeRow, result: null, error: this.packagingError });
                                this.isRunning = false;
                            }

                            if (payload.status === 'cancelled') {
                                this.stopPolling();
                                this.packagingMessage = 'Job was cancelled.';
                                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'warning', message: 'Job was cancelled.' } }));

                                // Ensure the row shows the cancelled status immediately
                                if (uq) {
                                    uq.status = 'cancelled';
                                    uq.statusMessage = 'Job was cancelled.';
                                }

                                // Record cancellation and advance to the next job in the queue
                                this.jobResults.push({ row: this.activeRow, result: null, error: 'cancelled' });
                                this.jobQueueIndex++;

                                if (this.jobQueueIndex < this.jobQueue.length) {
                                    setTimeout(() => this.processNextJob(), 500);
                                } else {
                                    this.isRunning = false;
                                    this.packagingMessage = 'All jobs processed.';
                                }
                            }

                        } catch (e) {
                            // Network hiccup — keep polling
                        }
                    }, 1500); // poll every 1.5 s
                },

                stopPolling() {
                    if (this.pollIntervalId) {
                        clearInterval(this.pollIntervalId);
                        this.pollIntervalId = null;
                    }
                },

                resetJob() {
                    this.stopPolling();
                    this.currentJobId = null;
                    this.isQueuing = false;
                    this.isRunning = false;
                    this.packagingResult = null;
                    this.packagingError = '';
                    this.packagingProgress = 0;
                    this.fileDownloadProgress = 0;
                    this.headFileExtraction = 0;
                    this.baseFileExtraction = 0;
                    this.compareFilesProgress = 0;
                    this.packageGenProgress = 0;
                    this.compressionProgress = 0;
                    this.packagingMessage = '';
                    this.activeRow = null;
                    this.jobQueue = [];
                    this.jobQueueIndex = 0;
                    this.jobResults = [];
                },

                downloadPackage(fmt = '.zip') {
                    if (!this.packagingResult) return;
                    const folder = encodeURIComponent(this.packagingResult.folder_name);

                    if (fmt === 'both') {
                        window.location.href = `${downloadUrl}?folder=${folder}&format=.zip`;
                        setTimeout(() => {
                            const iframe = document.createElement('iframe');
                            iframe.style.display = 'none';
                            iframe.src = `${downloadUrl}?folder=${folder}&format=.tar.gz`;
                            document.body.appendChild(iframe);
                            setTimeout(() => iframe.remove(), 10000);
                        }, 1000);
                    } else {
                        window.location.href = `${downloadUrl}?folder=${folder}&format=${encodeURIComponent(fmt)}`;
                    }
                },

                // ── GitLab project browser methods ────────────────────────────

                async loadGitlabProjects() {
                    if (!this.gitlabConnected) return;
                    this.gitlabLoading = true;
                    try {
                        const res = await fetch('{{ route('gitlab.projects') }}', { headers: { Accept: 'application/json' } });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Failed to load GitLab projects.');
                        this.gitlabProjects = data;
                    } catch (e) {
                         this.gitlabProjects = [];
                    } finally {
                        this.gitlabLoading = false;
                    }
                },

                async loadGitlabExploreProjects() {
                    if (!this.gitlabConnected) return;
                    this.gitlabLoadingExplore = true;
                    try {
                        const res = await fetch('{{ route('gitlab.explore') }}', { headers: { Accept: 'application/json' } });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Failed to load explore projects.');
                        const memberIds = new Set(this.gitlabProjects.map(p => p.id));
                        this.gitlabExploreProjects = data.filter(p => !memberIds.has(p.id));
                    } catch (e) {
                        this.gitlabExploreProjects = [];
                    } finally {
                        this.gitlabLoadingExplore = false;
                    }
                },

                selectGitlabProject(project) {
                    this.selectedRepository = project.id;
                    this.selectedRepositoryLabel_override = project.name;
                    this.isLoadingVersions = true;
                    this.floatDd.open = false;
                    this.packageRows = [
                        { id: Date.now(), base: '', head: '', environment: 'PROD', customName: false, name: '' }
                    ];
                    this.fetchRepoVersions();
                },

                // ── GitHub API helpers ────────────────────────────────────────

                async fetchRateLimit() {
                    if (this.vcsProvider !== 'github') return;
                    try {
                        this.rateLimit = await (await fetch('/github/rate-limit')).json();
                    } catch (e) { }
                },

                async fetchRepoData() {
                    if (!this.selectedRepository) { this.repoData = null; return; }
                    if (this.vcsProvider !== 'github') { this.repoData = null; return; }
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
                        let data = {};
                        if (this.vcsProvider === 'gitlab') {
                            const res = await fetch(`/gitlab/projects/${encodeURIComponent(this.selectedRepository)}/versions`);
                            data = res.ok ? await res.json() : {};
                        } else {
                        }
                        this.repoBranches = data.branches || [];
                        this.repoTags     = data.tags     || [];
                        this.repoReleases = data.releases || [];
                    } catch {
                        this.repoBranches = this.repoTags = this.repoReleases = [];
                    } finally {
                        this.isLoadingVersions = false;
                    }
                },

                // ── Date formatting ───────────────────────────────────────────
                timeAgo(dateString) {
                    if (!dateString) return '-';
                    const date = new Date(dateString);
                    const now = new Date();
                    const seconds = Math.floor((now - date) / 1000);
                    let interval = seconds / 31536000;
                    if (interval > 1) return Math.floor(interval) + " years ago";
                    interval = seconds / 2592000;
                    if (interval > 1) return Math.floor(interval) + " months ago";
                    interval = seconds / 86400;
                    if (interval > 1) return Math.floor(interval) + " days ago";
                    interval = seconds / 3600;
                    if (interval > 1) return Math.floor(interval) + " px hours ago";
                    interval = seconds / 60;
                    if (interval > 1) return Math.floor(interval) + " minutes ago";
                    return "just now";
                },
            };
        }
    </script>
@endpush