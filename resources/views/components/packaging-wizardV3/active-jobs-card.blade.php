<div id="active-jobs-section" x-show="unifiedQueue.length > 0" x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-data="{ expandedJobId: null }">
    <x-ui.card class="p-8 w-full">
        <div class="space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Current Packages</h2>
                <p class="text-sm text-slate-500 mt-1">
                    View packaging progress and download once complete.
                </p>
            </div>
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Env</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Project</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Version</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Status</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold"></th>
                            <th class="px-4 py-3 text-left text-sm font-semibold"></th>
                            <th class="px-4 py-3 text-left text-sm font-semibold"></th>
                        </tr>
                    </thead>
                    <template x-for="job in unifiedQueue" :key="job.jobId">
                        <tbody x-data="{
                                get expanded() { return expandedJobId === job.jobId; },
                                toggleExpanded() {
                                    expandedJobId = (expandedJobId === job.jobId) ? null : job.jobId;
                                }
                            }" class="divide-y divide-slate-100">
                            <tr @click="toggleExpanded()" class="cursor-pointer transition-all duration-300" :class="{
                                    'animate-row-success hover:bg-slate-50 transition-colors': job.status === 'completed',
                                    'bg-red-50/80 hover:bg-red-100/50': job.status === 'failed',
                                    'animate-row-indeterminate': job.jobId === currentJobId && packagingProgress === 0,
                                    'animate-row-running': job.jobId === currentJobId && job.status === 'running' && packagingProgress > 0,
                                    'hover:bg-slate-50': job.status === 'queued' || job.status === 'pending' || (!job.status && job.jobId !== currentJobId)

                                }" :style="(job.jobId === currentJobId && job.status === 'running' && packagingProgress > 0)
                                    ? `background-image: linear-gradient(90deg, transparent 20%, rgba(255,255,255,0.55) 50%, transparent 80%), linear-gradient(to right, rgba(187, 215, 252,0.55) ${packagingProgress}%, transparent ${packagingProgress}%);`
                                    : ''">
                                <td class="px-4 py-3 text-sm text-slate-800" x-text="job.row.environment"></td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-bold text-slate-800" x-text="job.row.project_name"></span>
                                </td>
                                <td class="px-4 py-3 text- text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="px-2 py-0.5 rounded border border-rose-100 bg-rose-50 text-rose-700 font-medium text-sm whitespace-nowrap"
                                            x-text="job.row.base_version"></span>
                                        <span class="text-slate-700 text-lg">→</span>
                                        <span
                                            class="px-2 py-0.5 rounded border border-emerald-100 bg-emerald-50 text-emerald-700 font-medium text-sm whitespace-nowrap"
                                            x-text="job.row.head_version"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-sm font-medium border"
                                        :class="{
                                                                        'bg-amber-50 border-amber-200 text-amber-700': job.status === 'pending' || job.status === 'queued',
                                                                        'bg-blue-50 border-blue-200 text-blue-700': job.status === 'running',
                                                                        'bg-emerald-50 border-emerald-200 text-emerald-700': job.status === 'completed',
                                                                        'bg-red-50 border-red-200 text-red-700': job.status === 'failed',
                                                                        'bg-slate-100 border-slate-300 text-slate-500': job.status === 'cancelled',
                                                                    }">
                                        <span class="inline-block h-1.5 w-1.5 rounded-full mr-1.5" :class="{
                                                                        'bg-amber-400': job.status === 'pending' || job.status === 'queued',
                                                                        'bg-blue-500 animate-pulse': job.status === 'running',
                                                                        'bg-emerald-500': job.status === 'completed',
                                                                        'bg-red-500': job.status === 'failed',
                                                                        'bg-slate-400': job.status === 'cancelled',
                                                                    }"></span>
                                        <span
                                            x-text="job.status ? job.status.charAt(0).toUpperCase() + job.status.slice(1) : 'Pending'"></span>
                                    </span>
                                </td>

                                <!-- Download .zip -->
                                <td class="px-4 py-3" @click.stop>
                                    <div class="flex items-center gap-2"
                                        :class="job.status !== 'completed' ? 'opacity-40 cursor-not-allowed' : ''">
                                        <svg aria-hidden="true" height="16" viewBox="0 0 16 16" version="1.1" width="16"
                                            class="shrink-0"
                                            :class="job.status === 'completed' ? 'text-slate-500' : 'text-slate-300'">
                                            <path fill="currentColor"
                                                d="M3.5 1.75v11.5c0 .09.048.173.126.217a.75.75 0 0 1-.752 1.298A1.748 1.748 0 0 1 2 13.25V1.75C2 .784 2.784 0 3.75 0h5.586c.464 0 .909.185 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v8.586A1.75 1.75 0 0 1 12.25 15h-.5a.75.75 0 0 1 0-1.5h.5a.25.25 0 0 0 .25-.25V4.664a.25.25 0 0 0-.073-.177L9.513 1.573a.25.25 0 0 0-.177-.073H7.25a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5h-3a.25.25 0 0 0-.25.25Zm3.75 8.75h.5c.966 0 1.75.784 1.75 1.75v3a.75.75 0 0 1-.75.75h-2.5a.75.75 0 0 1-.75-.75v-3c0-.966.784-1.75 1.75-1.75ZM6 5.25a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 6 5.25Zm.75 2.25h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 6.75A.75.75 0 0 1 8.75 6h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 6.75ZM8.75 3h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 9.75A.75.75 0 0 1 8.75 9h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 9.75Zm-1 2.5v2.25h1v-2.25a.25.25 0 0 0-.25-.25h-.5a.25.25 0 0 0-.25.25Z">
                                            </path>
                                        </svg>

                                        <template x-if="job.status === 'completed'">
                                            <a :href="'{{ url('download-archive') }}?folder=' + encodeURIComponent(job.row.name) + '&format=.zip'"
                                                class="flex items-center gap-1.5 group">
                                                <span class="text-blue-600 group-hover:underline">
                                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="currentColor"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z" />
                                                    </svg>
                                                </span>
                                                <span class="text-sm text-blue-600 font-medium group-hover:underline">
                                                    Package
                                                </span>
                                                <span class="text-sm text-blue-600 font-medium group-hover:underline">
                                                    (.zip)
                                                </span>
                                            </a>
                                        </template>
                                        <template x-if="job.status !== 'completed'">
                                            <span class="flex items-center gap-1.5 group">
                                                <span class="text-slate-400 group-hover:underline">
                                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="currentColor"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z" />
                                                    </svg>
                                                </span>
                                                <span class="text-sm text-slate-400 font-medium group-hover:underline">
                                                    Package
                                                </span>
                                                <span class="text-sm text-slate-400 font-medium group-hover:underline">
                                                    (.zip)
                                                </span>
                                            </span>
                                        </template>
                                    </div>
                                </td>
                                <!-- Download .tar.gz -->
                                <td class="px-4 py-3" @click.stop>
                                    <div class="flex items-center gap-2"
                                        :class="job.status !== 'completed' ? 'opacity-40 cursor-not-allowed' : ''">
                                        <svg aria-hidden="true" height="16" viewBox="0 0 16 16" version="1.1" width="16"
                                            class="shrink-0"
                                            :class="job.status === 'completed' ? 'text-slate-500' : 'text-slate-300'">
                                            <path fill="currentColor"
                                                d="M3.5 1.75v11.5c0 .09.048.173.126.217a.75.75 0 0 1-.752 1.298A1.748 1.748 0 0 1 2 13.25V1.75C2 .784 2.784 0 3.75 0h5.586c.464 0 .909.185 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v8.586A1.75 1.75 0 0 1 12.25 15h-.5a.75.75 0 0 1 0-1.5h.5a.25.25 0 0 0 .25-.25V4.664a.25.25 0 0 0-.073-.177L9.513 1.573a.25.25 0 0 0-.177-.073H7.25a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5h-3a.25.25 0 0 0-.25.25Zm3.75 8.75h.5c.966 0 1.75.784 1.75 1.75v3a.75.75 0 0 1-.75.75h-2.5a.75.75 0 0 1-.75-.75v-3c0-.966.784-1.75 1.75-1.75ZM6 5.25a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 6 5.25Zm.75 2.25h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 6.75A.75.75 0 0 1 8.75 6h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 6.75ZM8.75 3h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 9.75A.75.75 0 0 1 8.75 9h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 9.75Zm-1 2.5v2.25h1v-2.25a.25.25 0 0 0-.25-.25h-.5a.25.25 0 0 0-.25.25Z">
                                            </path>
                                        </svg>
                                        <template x-if="job.status === 'completed'">
                                            <a :href="'{{ url('download-archive') }}?folder=' + encodeURIComponent(job.row.name) + '&format=.tar.gz'"
                                                class="flex items-center gap-1.5 group">
                                                <span class="text-blue-600 group-hover:underline">
                                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="currentColor"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z" />
                                                    </svg>
                                                </span>
                                                <span class="text-sm text-blue-600 font-medium group-hover:underline">
                                                    Package
                                                </span>
                                                <span class="text-sm text-blue-600 font-bold group-hover:underline">
                                                    (.tar.gz)
                                                </span>
                                            </a>
                                        </template>
                                        <template x-if="job.status !== 'completed'">
                                            <span class="flex items-center gap-1.5 group">
                                                <span class="text-slate-400 group-hover:underline">
                                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="currentColor"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z" />
                                                    </svg>
                                                </span>
                                                <span class="text-sm text-slate-400 font-medium group-hover:underline">
                                                    Package
                                                </span>
                                                <span class="text-sm text-slate-400 font-bold group-hover:underline">
                                                    (.tar.gz)
                                                </span>
                                            </span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-2 py-3" @click.stop>
                                    {{-- Stop button: visible for queued / pending / running jobs --}}
                                    <button type="button"
                                        x-show="['queued', 'pending', 'running'].includes(job.status)"
                                        title="Stop job"
                                        @click.stop="
                                            fetch('/deployments/jobs/' + job.jobId + '/cancel', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json'
                                                }
                                            })
                                            .then(r => r.json())
                                            .then(data => {
                                                if (data.status === 'cancelled') {
                                                    let idx = unifiedQueue.findIndex(j => j.jobId === job.jobId);
                                                    if (idx !== -1) unifiedQueue[idx].status = 'cancelled';
                                                }
                                            })
                                            .catch(console.error)
                                        "
                                        class="shrink-0 text-red-400 hover:text-red-600 transition-colors focus:outline-none p-1 rounded hover:bg-red-50">
                                        <svg class="h-4 w-4 fill-current" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M8 8.707l3.646 3.647.708-.707L8.707 8l3.647-3.646-.707-.708L8 7.293 4.354 3.646l-.707.708L7.293 8l-3.646 3.646.707.708L8 8.707z" />
                                        </svg>
                                    </button>

                                    {{-- Retry button: visible only for failed or cancelled jobs --}}
                                    <button type="button"
                                        x-show="['failed', 'cancelled'].includes(job.status)"
                                        title="Retry job"
                                        @click.stop="
                                            fetch('/deployments/jobs/' + job.jobId + '/retry', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json'
                                                }
                                            })
                                            .then(r => r.json())
                                            .then(data => {
                                                if (data.status === 'queued') {
                                                    let idx = unifiedQueue.findIndex(j => j.jobId === job.jobId);
                                                    if (idx !== -1) {
                                                        unifiedQueue[idx].status = 'queued';
                                                        unifiedQueue[idx].errorMessage = null;
                                                        unifiedQueue[idx].progress = null;
                                                    }
                                                    // Re-attach polling so running → completed updates come through
                                                    currentJobId = job.jobId;
                                                    activeRow = job.row;
                                                    isRunning = true;
                                                    packagingProgress = 0;
                                                    fileDownloadProgress = 0;
                                                    headFileExtraction = 0;
                                                    baseFileExtraction = 0;
                                                    compareFilesProgress = 0;
                                                    packageGenProgress = 0;
                                                    compressionProgress = 0;
                                                    packagingError = '';
                                                    packagingMessage = 'Retrying job...';
                                                    startPolling();
                                                }
                                            })
                                            .catch(console.error)
                                        "
                                        class="shrink-0 text-slate-400 hover:text-blue-600 transition-colors focus:outline-none p-1 rounded hover:bg-blue-50">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8" />
                                            <path d="M21 3v5h-5" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr x-show="expanded" x-cloak x-transition.origin.top
                                class="bg-indigo-50/30 border-t border-indigo-100/50 shadow-inner">
                                <td colspan="7" class="px-6 py-5">
                                    <!-- Progress bars -->
                                    <!-- Resolve progress values: use live globals for the currently running job, stored job data for others -->
                                    <template x-if="true">
                                    <div x-data="{
                                            get _isActive() { return job.jobId === currentJobId; },
                                            get _overallPct()   { return this._isActive ? packagingProgress    : (job.progress?.packagingProgress    ?? 0); },
                                            get _downloadPct()  { return this._isActive ? fileDownloadProgress : (job.progress?.fileDownloadProgress  ?? 0); },
                                            get _basePct()      { return this._isActive ? baseFileExtraction   : (job.progress?.baseFileExtraction    ?? 0); },
                                            get _headPct()      { return this._isActive ? headFileExtraction   : (job.progress?.headFileExtraction    ?? 0); },
                                            get _comparePct()   { return this._isActive ? compareFilesProgress : (job.progress?.compareFilesProgress  ?? 0); },
                                            get _genPct()       { return this._isActive ? packageGenProgress   : (job.progress?.packageGenProgress    ?? 0); },
                                            get _compressPct()  { return this._isActive ? compressionProgress  : (job.progress?.compressionProgress   ?? 0); },
                                            get _error()        { return this._isActive ? packagingError       : (job.errorMessage                   ?? ''); },
                                            get _msg()          { return this._isActive ? packagingMessage     : (job.statusMessage                  ?? ''); },
                                            get _running()      { return this._isActive ? isRunning            : false; },
                                        }"
                                        class="rounded-xl border border-slate-200 bg-white shadow-sm p-5"
                                        x-show="_running || _overallPct > 0 || (job.jobId === currentJobId && packagingResult) || _error">

                                        <!-- Overall -->
                                        <div class="mb-2">
                                            <div class="mb-2 flex items-center justify-between">
                                                <span class="text-sm font-semibold"
                                                    :class="_overallPct === 100 ? 'text-green-600' : 'text-slate-700'">
                                                    Overall Progress
                                                    <span x-show="_overallPct === 100"
                                                        class="text-green-600 ml-1"> <i class="fa fa-check-circle"></i>
                                                        ✓ Complete</span>
                                                </span>
                                                <span class="text-sm font-bold text-blue-600"
                                                    x-text="_overallPct + '%'"></span>
                                            </div>
                                            <div
                                                class="h-2 w-full overflow-hidden rounded-full bg-slate-100 shadow-inner relative">
                                                <div class="h-full rounded-full transition-all duration-500 shadow-sm"
                                                    :class="{
                                                                                'bg-emerald-500': _overallPct === 100, 
                                                                                'bg-blue-500': (_overallPct > 0 && _overallPct < 100) || _overallPct === 0,
                                                                                'bg-red-500': _error !== '',
                                                                                'animate-indeterminate': _overallPct === 0
                                                                            }"
                                                    :style="_overallPct === 0 ? '' : `width: ${_overallPct}%`">
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="border-slate-100 mb-2">

                                        <!-- Stage bars -->
                                        <div class="flex flex-col bg-slate-50 border border-slate-100 rounded-lg p-4">

                                            <!-- Download — hidden until started, bar hidden on completion -->
                                            <div x-show="_downloadPct > 0"
                                                :class="_downloadPct < 100 ? 'mb-4' : 'mb-1'">
                                                <div class="flex items-center justify-between"
                                                    :class="_downloadPct < 100 ? 'mb-1' : ''">
                                                    <span class="text-xs font-semibold flex items-center gap-1.5"
                                                        :class="_downloadPct === 100 ? 'text-emerald-600' : 'text-slate-600'">
                                                        <span x-show="_downloadPct === 100"
                                                            class="text-emerald-500">✓</span>
                                                        <span
                                                            x-show="_downloadPct < 100 && _downloadPct > 0"
                                                            class="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                        Downloading Repositories
                                                    </span>
                                                    <span x-show="_downloadPct < 100"
                                                        class="text-xs font-medium text-slate-400"
                                                        x-text="_downloadPct + '%'"></span>
                                                </div>
                                                <div x-show="_downloadPct < 100"
                                                    class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                    <div class="h-full rounded-full transition-all duration-500 bg-blue-400"
                                                        :style="`width: ${_downloadPct}%`"></div>
                                                </div>
                                            </div>

                                            <!-- Extraction — side-by-side until both done, then stacked -->
                                            <div x-show="_basePct > 0 || _headPct > 0"
                                                :class="(_basePct === 100 && _headPct === 100) ? 'mb-1 flex flex-col gap-1' : 'mb-4 grid grid-cols-2 gap-4'">
                                                <!-- Base -->
                                                <div>
                                                    <div class="flex items-center justify-between"
                                                        :class="_basePct < 100 ? 'mb-1' : ''">
                                                        <span class="text-xs font-semibold flex items-center gap-1.5"
                                                            :class="_basePct === 100 ? 'text-emerald-600' : 'text-slate-600'">
                                                            <span x-show="_basePct === 100"
                                                                class="text-emerald-500">✓</span>
                                                            <span
                                                                x-show="_basePct < 100 && _basePct > 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                            <span x-show="_basePct === 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-slate-300"></span>
                                                            Base Extraction
                                                        </span>
                                                        <span x-show="_basePct < 100"
                                                            class="text-xs font-medium text-slate-400"
                                                            x-text="_basePct + '%'"></span>
                                                    </div>
                                                    <div x-show="_basePct < 100"
                                                        class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                        <div class="h-full rounded-full transition-all duration-500 bg-blue-400"
                                                            :style="`width: ${_basePct}%`"></div>
                                                    </div>
                                                </div>
                                                <!-- Head -->
                                                <div>
                                                    <div class="flex items-center justify-between"
                                                        :class="_headPct < 100 ? 'mb-1' : ''">
                                                        <span class="text-xs font-semibold flex items-center gap-1.5"
                                                            :class="_headPct === 100 ? 'text-emerald-600' : 'text-slate-600'">
                                                            <span x-show="_headPct === 100"
                                                                class="text-emerald-500">✓</span>
                                                            <span
                                                                x-show="_headPct < 100 && _headPct > 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                            <span x-show="_headPct === 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-slate-300"></span>
                                                            Head Extraction
                                                        </span>
                                                        <span x-show="_headPct < 100"
                                                            class="text-xs font-medium text-slate-400"
                                                            x-text="_headPct + '%'"></span>
                                                    </div>
                                                    <div x-show="_headPct < 100"
                                                        class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                        <div class="h-full rounded-full transition-all duration-500 bg-blue-400"
                                                            :style="`width: ${_headPct}%`"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Compare — hidden until started, bar hidden on completion -->
                                            <div x-show="_comparePct > 0"
                                                :class="_comparePct < 100 ? 'mb-4' : 'mb-1'">
                                                <div class="flex items-center justify-between"
                                                    :class="_comparePct < 100 ? 'mb-1' : ''">
                                                    <span class="text-xs font-semibold flex items-center gap-1.5"
                                                        :class="_comparePct === 100 ? 'text-emerald-600' : 'text-slate-600'">
                                                        <span x-show="_comparePct === 100"
                                                            class="text-emerald-500">✓</span>
                                                        <span
                                                            x-show="_comparePct < 100 && _comparePct > 0"
                                                            class="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                        Comparing Diffs
                                                    </span>
                                                    <span x-show="_comparePct < 100"
                                                        class="text-xs font-medium text-slate-400"
                                                        x-text="_comparePct + '%'"></span>
                                                </div>
                                                <div x-show="_comparePct < 100"
                                                    class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                    <div class="h-full rounded-full transition-all duration-500 bg-blue-400"
                                                        :style="`width: ${_comparePct}%`"></div>
                                                </div>
                                            </div>

                                            <!-- Gen + Compress — side-by-side until both done, then stacked -->
                                            <div x-show="_genPct > 0 || _compressPct > 0"
                                                :class="(_genPct === 100 && _compressPct === 100) ? 'flex flex-col gap-1' : 'grid grid-cols-2 gap-4'">
                                                <!-- Packaging -->
                                                <div>
                                                    <div class="flex items-center justify-between"
                                                        :class="_genPct < 100 ? 'mb-1' : ''">
                                                        <span class="text-xs font-semibold flex items-center gap-1.5"
                                                            :class="_genPct === 100 ? 'text-emerald-600' : 'text-slate-600'">
                                                            <span x-show="_genPct === 100"
                                                                class="text-emerald-500">✓</span>
                                                            <span
                                                                x-show="_genPct < 100 && _genPct > 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                            <span x-show="_genPct === 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-slate-300"></span>
                                                            Packaging Directory
                                                        </span>
                                                        <span x-show="_genPct < 100"
                                                            class="text-xs font-medium text-slate-400"
                                                            x-text="_genPct + '%'"></span>
                                                    </div>
                                                    <div x-show="_genPct < 100"
                                                        class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                        <div class="h-full rounded-full transition-all duration-500 bg-blue-400"
                                                            :style="`width: ${_genPct}%`"></div>
                                                    </div>
                                                </div>
                                                <!-- Compression -->
                                                <div>
                                                    <div class="flex items-center justify-between"
                                                        :class="_compressPct < 100 ? 'mb-1' : ''">
                                                        <span class="text-xs font-semibold flex items-center gap-1.5"
                                                            :class="_compressPct === 100 ? 'text-emerald-600' : 'text-slate-600'">
                                                            <span x-show="_compressPct === 100"
                                                                class="text-emerald-500">✓</span>
                                                            <span
                                                                x-show="_compressPct < 100 && _compressPct > 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                            <span x-show="_compressPct === 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-slate-300"></span>
                                                            Compressing Folder
                                                        </span>
                                                        <span x-show="_compressPct < 100"
                                                            class="text-xs font-medium text-slate-400"
                                                            x-text="_compressPct + '%'"></span>
                                                    </div>
                                                    <div x-show="_compressPct < 100"
                                                        class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                        <div class="h-full rounded-full transition-all duration-500 bg-blue-400"
                                                            :style="`width: ${_compressPct}%`"></div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div x-show="_error" x-cloak
                                            class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg shadow-sm">
                                            <span class="text-sm font-semibold text-red-600">Error:</span>
                                            <span class="text-sm text-red-700" x-text="_error"></span>
                                        </div>
                                    </div>
                                    </template>

                                    <!-- Status message -->
                                    <p class="mt-3 text-sm font-medium flex items-center gap-2 h-6"
                                        :class="(job.jobId === currentJobId ? packagingError : job.errorMessage) ? 'text-red-500' : 'text-slate-500'">
                                        <span x-show="job.jobId === currentJobId && isRunning" class="rotate-anim inline-block opacity-70">⟳</span>
                                        <span x-text="job.jobId === currentJobId ? (packagingMessage || '') : (job.statusMessage || '')"></span>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </template>
                </table>
            </div>
        </div>
    </x-ui.card>
</div>