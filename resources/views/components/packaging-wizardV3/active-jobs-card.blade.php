<div x-show="unifiedQueue.length > 0" x-cloak x-transition:enter="transition ease-out duration-300">
    <x-ui.card class="p-8 w-full">
        <div class="space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Active Jobs</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Jobs that are queued and not in completion
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
                        <tbody class="divide-y divide-slate-100">
                            <tr class="transition-colors hover:bg-slate-50">
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
                                                                    }">
                                        <span class="inline-block h-1.5 w-1.5 rounded-full mr-1.5" :class="{
                                                                        'bg-amber-400': job.status === 'pending' || job.status === 'queued',
                                                                        'bg-blue-500 animate-pulse': job.status === 'running',
                                                                        'bg-emerald-500': job.status === 'completed',
                                                                        'bg-red-500': job.status === 'failed',
                                                                    }"></span>
                                        <span
                                            x-text="job.status ? job.status.charAt(0).toUpperCase() + job.status.slice(1) : 'Pending'"></span>
                                    </span>
                                </td>

                                <!-- Download .zip -->
                                <td class="px-4 py-3" @click.stop>
                                    <div class="flex items-center gap-2"
                                        :class="job.status !== 'completed' ? 'opacity-40 cursor-not-allowed' : ''">
                                        <svg aria-hidden="true" height="16" viewBox="0 0 16 16" version="1.1"
                                            width="16" class="shrink-0"
                                            :class="job.status === 'completed' ? 'text-slate-500' : 'text-slate-300'">
                                            <path fill="currentColor"
                                                d="M3.5 1.75v11.5c0 .09.048.173.126.217a.75.75 0 0 1-.752 1.298A1.748 1.748 0 0 1 2 13.25V1.75C2 .784 2.784 0 3.75 0h5.586c.464 0 .909.185 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v8.586A1.75 1.75 0 0 1 12.25 15h-.5a.75.75 0 0 1 0-1.5h.5a.25.25 0 0 0 .25-.25V4.664a.25.25 0 0 0-.073-.177L9.513 1.573a.25.25 0 0 0-.177-.073H7.25a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5h-3a.25.25 0 0 0-.25.25Zm3.75 8.75h.5c.966 0 1.75.784 1.75 1.75v3a.75.75 0 0 1-.75.75h-2.5a.75.75 0 0 1-.75-.75v-3c0-.966.784-1.75 1.75-1.75ZM6 5.25a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 6 5.25Zm.75 2.25h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 6.75A.75.75 0 0 1 8.75 6h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 6.75ZM8.75 3h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 9.75A.75.75 0 0 1 8.75 9h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 9.75Zm-1 2.5v2.25h1v-2.25a.25.25 0 0 0-.25-.25h-.5a.25.25 0 0 0-.25.25Z">
                                            </path>
                                        </svg>
                                        <template x-if="job.status === 'completed'">
                                            <a :href="'{{ url('download-archive') }}?folder=' + encodeURIComponent(job.row.name) + '&format=.zip'"
                                                class="group">
                                                <span
                                                    class="text-sm text-blue-600 font-medium group-hover:underline">Update
                                                    Package .zip</span>
                                            </a>
                                        </template>
                                        <template x-if="job.status !== 'completed'">
                                            <span class="text-sm text-slate-400 font-medium select-none">Update
                                                Package .zip</span>
                                        </template>
                                    </div>
                                </td>
                                <!-- Download .tar.gz -->
                                <td class="px-4 py-3" @click.stop>
                                    <div class="flex items-center gap-2"
                                        :class="job.status !== 'completed' ? 'opacity-40 cursor-not-allowed' : ''">
                                        <svg aria-hidden="true" height="16" viewBox="0 0 16 16" version="1.1"
                                            width="16" class="shrink-0"
                                            :class="job.status === 'completed' ? 'text-slate-500' : 'text-slate-300'">
                                            <path fill="currentColor"
                                                d="M3.5 1.75v11.5c0 .09.048.173.126.217a.75.75 0 0 1-.752 1.298A1.748 1.748 0 0 1 2 13.25V1.75C2 .784 2.784 0 3.75 0h5.586c.464 0 .909.185 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v8.586A1.75 1.75 0 0 1 12.25 15h-.5a.75.75 0 0 1 0-1.5h.5a.25.25 0 0 0 .25-.25V4.664a.25.25 0 0 0-.073-.177L9.513 1.573a.25.25 0 0 0-.177-.073H7.25a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5h-3a.25.25 0 0 0-.25.25Zm3.75 8.75h.5c.966 0 1.75.784 1.75 1.75v3a.75.75 0 0 1-.75.75h-2.5a.75.75 0 0 1-.75-.75v-3c0-.966.784-1.75 1.75-1.75ZM6 5.25a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 6 5.25Zm.75 2.25h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 6.75A.75.75 0 0 1 8.75 6h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 6.75ZM8.75 3h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 9.75A.75.75 0 0 1 8.75 9h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 9.75Zm-1 2.5v2.25h1v-2.25a.25.25 0 0 0-.25-.25h-.5a.25.25 0 0 0-.25.25Z">
                                            </path>
                                        </svg>
                                        <template x-if="job.status === 'completed'">
                                            <a :href="'{{ url('download-archive') }}?folder=' + encodeURIComponent(job.row.name) + '&format=.tar.gz'"
                                                class="group">
                                                <span
                                                    class="text-sm text-blue-600 font-medium group-hover:underline">Update
                                                    Package .tar.gz</span>
                                            </a>
                                        </template>
                                        <template x-if="job.status !== 'completed'">
                                            <span class="text-sm text-slate-400 font-medium select-none">Update
                                                Package .tar.gz</span>
                                        </template>
                                    </div>
                                </td>
                                <td>
                                    <button type="button" @click="" title="Remove this job"
                                        class="shrink-0 text-red-500 hover:text-red-600 transition-colors focus:outline-none ">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr x-show="job.jobId === currentJobId" x-cloak x-transition.origin.top
                                class="bg-indigo-50/30 border-t border-indigo-100/50 shadow-inner">
                                <td colspan="7" class="px-6 py-5">
                                    <!-- Progress bars -->
                                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5"
                                        x-show="isRunning || packagingProgress > 0 || packagingResult || packagingError">

                                        <!-- Overall -->
                                        <div class="mb-2">
                                            <div class="mb-2 flex items-center justify-between">
                                                <span class="text-sm font-semibold"
                                                    :class="packagingProgress === 100 ? 'text-green-600' : 'text-slate-700'">
                                                    Overall Progress
                                                    <span x-show="packagingProgress === 100"
                                                        class="text-green-600 ml-1"> <i
                                                            class="fa fa-check-circle"></i> ✓ Complete</span>
                                                </span>
                                                <span class="text-sm font-bold text-blue-600"
                                                    x-text="packagingProgress + '%'"></span>
                                            </div>
                                            <div
                                                class="h-2 w-full overflow-hidden rounded-full bg-slate-100 shadow-inner">
                                                <div class="h-full rounded-full transition-all duration-500 shadow-sm"
                                                    :class="{
                                                                                'bg-emerald-500': packagingProgress === 100, 
                                                                                'bg-blue-500': packagingProgress > 0 && packagingProgress < 100,
                                                                                'bg-red-500': packagingError !== ''
                                                                            }"
                                                    :style="`width: ${packagingProgress}%`">
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="border-slate-100 mb-2">

                                        <!-- Stage bars -->
                                        <div
                                            class="flex flex-col bg-slate-50 border border-slate-100 rounded-lg p-4">

                                            <!-- Download — hidden until started, bar hidden on completion -->
                                            <div x-show="fileDownloadProgress > 0"
                                                :class="fileDownloadProgress < 100 ? 'mb-4' : 'mb-1'">
                                                <div class="flex items-center justify-between"
                                                    :class="fileDownloadProgress < 100 ? 'mb-1' : ''">
                                                    <span class="text-xs font-semibold flex items-center gap-1.5"
                                                        :class="fileDownloadProgress === 100 ? 'text-emerald-600' : 'text-slate-600'">
                                                        <span x-show="fileDownloadProgress === 100"
                                                            class="text-emerald-500">✓</span>
                                                        <span
                                                            x-show="fileDownloadProgress < 100 && fileDownloadProgress > 0"
                                                            class="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                        Downloading Repositories
                                                    </span>
                                                    <span x-show="fileDownloadProgress < 100"
                                                        class="text-xs font-medium text-slate-400"
                                                        x-text="fileDownloadProgress + '%'"></span>
                                                </div>
                                                <div x-show="fileDownloadProgress < 100"
                                                    class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                    <div class="h-full rounded-full transition-all duration-500 bg-blue-400"
                                                        :style="`width: ${fileDownloadProgress}%`"></div>
                                                </div>
                                            </div>

                                            <!-- Extraction — side-by-side until both done, then stacked -->
                                            <div x-show="baseFileExtraction > 0 || headFileExtraction > 0"
                                                :class="(baseFileExtraction === 100 && headFileExtraction === 100) ? 'mb-1 flex flex-col gap-1' : 'mb-4 grid grid-cols-2 gap-4'">
                                                <!-- Base -->
                                                <div>
                                                    <div class="flex items-center justify-between"
                                                        :class="baseFileExtraction < 100 ? 'mb-1' : ''">
                                                        <span
                                                            class="text-xs font-semibold flex items-center gap-1.5"
                                                            :class="baseFileExtraction === 100 ? 'text-emerald-600' : 'text-slate-600'">
                                                            <span x-show="baseFileExtraction === 100"
                                                                class="text-emerald-500">✓</span>
                                                            <span
                                                                x-show="baseFileExtraction < 100 && baseFileExtraction > 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                            <span x-show="baseFileExtraction === 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-slate-300"></span>
                                                            Base Extraction
                                                        </span>
                                                        <span x-show="baseFileExtraction < 100"
                                                            class="text-xs font-medium text-slate-400"
                                                            x-text="baseFileExtraction + '%'"></span>
                                                    </div>
                                                    <div x-show="baseFileExtraction < 100"
                                                        class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                        <div class="h-full rounded-full transition-all duration-500 bg-blue-400"
                                                            :style="`width: ${baseFileExtraction}%`"></div>
                                                    </div>
                                                </div>
                                                <!-- Head -->
                                                <div>
                                                    <div class="flex items-center justify-between"
                                                        :class="headFileExtraction < 100 ? 'mb-1' : ''">
                                                        <span
                                                            class="text-xs font-semibold flex items-center gap-1.5"
                                                            :class="headFileExtraction === 100 ? 'text-emerald-600' : 'text-slate-600'">
                                                            <span x-show="headFileExtraction === 100"
                                                                class="text-emerald-500">✓</span>
                                                            <span
                                                                x-show="headFileExtraction < 100 && headFileExtraction > 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                            <span x-show="headFileExtraction === 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-slate-300"></span>
                                                            Head Extraction
                                                        </span>
                                                        <span x-show="headFileExtraction < 100"
                                                            class="text-xs font-medium text-slate-400"
                                                            x-text="headFileExtraction + '%'"></span>
                                                    </div>
                                                    <div x-show="headFileExtraction < 100"
                                                        class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                        <div class="h-full rounded-full transition-all duration-500 bg-blue-400"
                                                            :style="`width: ${headFileExtraction}%`"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Compare — hidden until started, bar hidden on completion -->
                                            <div x-show="compareFilesProgress > 0"
                                                :class="compareFilesProgress < 100 ? 'mb-4' : 'mb-1'">
                                                <div class="flex items-center justify-between"
                                                    :class="compareFilesProgress < 100 ? 'mb-1' : ''">
                                                    <span class="text-xs font-semibold flex items-center gap-1.5"
                                                        :class="compareFilesProgress === 100 ? 'text-emerald-600' : 'text-slate-600'">
                                                        <span x-show="compareFilesProgress === 100"
                                                            class="text-emerald-500">✓</span>
                                                        <span
                                                            x-show="compareFilesProgress < 100 && compareFilesProgress > 0"
                                                            class="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                        Comparing Diffs
                                                    </span>
                                                    <span x-show="compareFilesProgress < 100"
                                                        class="text-xs font-medium text-slate-400"
                                                        x-text="compareFilesProgress + '%'"></span>
                                                </div>
                                                <div x-show="compareFilesProgress < 100"
                                                    class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                    <div class="h-full rounded-full transition-all duration-500 bg-blue-400"
                                                        :style="`width: ${compareFilesProgress}%`"></div>
                                                </div>
                                            </div>

                                            <!-- Gen + Compress — side-by-side until both done, then stacked -->
                                            <div x-show="packageGenProgress > 0 || compressionProgress > 0"
                                                :class="(packageGenProgress === 100 && compressionProgress === 100) ? 'flex flex-col gap-1' : 'grid grid-cols-2 gap-4'">
                                                <!-- Packaging -->
                                                <div>
                                                    <div class="flex items-center justify-between"
                                                        :class="packageGenProgress < 100 ? 'mb-1' : ''">
                                                        <span
                                                            class="text-xs font-semibold flex items-center gap-1.5"
                                                            :class="packageGenProgress === 100 ? 'text-emerald-600' : 'text-slate-600'">
                                                            <span x-show="packageGenProgress === 100"
                                                                class="text-emerald-500">✓</span>
                                                            <span
                                                                x-show="packageGenProgress < 100 && packageGenProgress > 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                            <span x-show="packageGenProgress === 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-slate-300"></span>
                                                            Packaging Directory
                                                        </span>
                                                        <span x-show="packageGenProgress < 100"
                                                            class="text-xs font-medium text-slate-400"
                                                            x-text="packageGenProgress + '%'"></span>
                                                    </div>
                                                    <div x-show="packageGenProgress < 100"
                                                        class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                        <div class="h-full rounded-full transition-all duration-500 bg-blue-400"
                                                            :style="`width: ${packageGenProgress}%`"></div>
                                                    </div>
                                                </div>
                                                <!-- Compression -->
                                                <div>
                                                    <div class="flex items-center justify-between"
                                                        :class="compressionProgress < 100 ? 'mb-1' : ''">
                                                        <span
                                                            class="text-xs font-semibold flex items-center gap-1.5"
                                                            :class="compressionProgress === 100 ? 'text-emerald-600' : 'text-slate-600'">
                                                            <span x-show="compressionProgress === 100"
                                                                class="text-emerald-500">✓</span>
                                                            <span
                                                                x-show="compressionProgress < 100 && compressionProgress > 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                            <span x-show="compressionProgress === 0"
                                                                class="inline-block h-2 w-2 rounded-full bg-slate-300"></span>
                                                            Compressing Folder
                                                        </span>
                                                        <span x-show="compressionProgress < 100"
                                                            class="text-xs font-medium text-slate-400"
                                                            x-text="compressionProgress + '%'"></span>
                                                    </div>
                                                    <div x-show="compressionProgress < 100"
                                                        class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                        <div class="h-full rounded-full transition-all duration-500 bg-blue-400"
                                                            :style="`width: ${compressionProgress}%`"></div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div x-show="packagingError" x-cloak
                                            class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg shadow-sm">
                                            <span class="text-sm font-semibold text-red-600">Error:</span>
                                            <span class="text-sm text-red-700" x-text="packagingError"></span>
                                        </div>
                                    </div>

                                    <!-- Status message -->
                                    <p class="mt-3 text-sm font-medium flex items-center gap-2 h-6"
                                        :class="packagingError ? 'text-red-500' : 'text-slate-500'">
                                        <span x-show="isRunning"
                                            class="rotate-anim inline-block opacity-70">⟳</span>
                                        <span x-text="packagingMessage || ''"></span>
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
