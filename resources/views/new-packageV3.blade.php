@extends('layouts.app')

@section('title', 'New Package V3 Lifecycle')

@section('content')
    <div class="max-w-7xl mx-auto space-y-8 pt-4 pb-12" x-data="newPackageWizard({
                            repositories: @js($repositories),
                            queueUrl: '{{ route('deployments.queue-job') }}',
                            jobProgressBaseUrl: '{{ url('/deployments/jobs') }}',
                            downloadUrl: '{{ route('download.archive') }}',
                            csrfToken: '{{ csrf_token() }}'
                        })">
        {{-- ================================================================ --}}
        {{-- CARD 1 — Repository selection + multi-row version picker          --}}
        {{-- ================================================================ --}}
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
                        </p>
                    </div>

                    <div class="mt-6 flex items-center gap-4">
                        <!-- Process Queue button -->
                        <button type="button"
                            id="btn-process-queue"
                            @click="startPackaging()"
                            :disabled="!canStartQueue || isQueuing || isRunning"
                            class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition disabled:bg-slate-300 disabled:cursor-not-allowed shadow-sm hover:bg-blue-700">
                            <span x-show="!isQueuing && !isRunning">Process Queue</span>
                            <span x-show="isQueuing" x-cloak>Submitting...</span>
                            <span x-show="isRunning && !isQueuing" x-cloak class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" class="opacity-20"/>
                                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                                </svg>
                                Running...
                            </span>
                        </button>

                        <!-- Queued job badge -->
                        <div x-show="currentJobId && jobStatus" x-cloak
                            class="flex items-center gap-2 text-xs font-medium px-3 py-1.5 rounded-full border"
                            :class="{
                                'bg-amber-50 border-amber-200 text-amber-700': jobStatus === 'queued',
                                'bg-blue-50 border-blue-200 text-blue-700': jobStatus === 'running',
                                'bg-emerald-50 border-emerald-200 text-emerald-700': jobStatus === 'completed',
                                'bg-red-50 border-red-200 text-red-700': jobStatus === 'failed',
                            }">
                            <span class="inline-block h-1.5 w-1.5 rounded-full"
                                :class="{
                                    'bg-amber-400': jobStatus === 'queued',
                                    'bg-blue-500 animate-pulse': jobStatus === 'running',
                                    'bg-emerald-500': jobStatus === 'completed',
                                    'bg-red-500': jobStatus === 'failed',
                                }"></span>
                            <span x-text="'Job #' + currentJobId + ' — ' + (jobStatus ? jobStatus.charAt(0).toUpperCase() + jobStatus.slice(1) : '')"></span>
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

        {{-- ================================================================ --}}
        {{-- CARD 2 — Progress + Result (shown once a job is submitted)        --}}
        {{-- ================================================================ --}}
        <div x-show="currentJobId" x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0">
            <x-ui.card class="w-full relative overflow-hidden">
                <div class="flex flex-col lg:flex-row min-h-[400px]">

                    {{-- ── Left: Progress panel ─────────────────────────── --}}
                    <div class="p-8 flex flex-col justify-between transition-all duration-700"
                        :class="packagingResult ? 'w-full lg:w-[45%] lg:border-r border-slate-200' : 'w-full'">

                        <div>
                            <div class="mb-6">
                                <div class="flex items-center gap-3">
                                    <h2 class="text-xl font-semibold text-slate-800">Package Generation</h2>
                                    <span x-show="totalJobs > 1" x-cloak
                                        class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700"
                                        x-text="jobQueueLabel"></span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500"
                                    x-text="activeRowLabel || 'Processing queued job...'"></p>
                            </div>

                            <!-- Job meta chips -->
                            <div class="flex flex-wrap gap-2 mb-6" x-show="activeRow">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"
                                    x-text="activeRow?.environment || ''"></span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"
                                    x-text="activeRow?.format || ''"></span>
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 truncate max-w-[280px]"
                                    x-text="activeRow?.name || ''"></span>
                            </div>

                            <!-- Progress bars -->
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                                x-show="isRunning || packagingProgress > 0 || packagingResult || packagingError">

                                <!-- Overall -->
                                <div class="mb-4">
                                    <div class="mb-1.5 flex items-center justify-between">
                                        <span class="text-sm font-semibold"
                                            :class="packagingProgress === 100 ? 'text-green-600' : 'text-slate-700'">
                                            Overall Progress
                                            <span x-show="packagingProgress === 100" class="text-green-600"> ✓</span>
                                        </span>
                                        <span class="text-sm font-medium text-slate-600"
                                            x-text="packagingProgress + '%'"></span>
                                    </div>
                                    <div class="h-3 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full bg-blue-500 transition-all duration-500"
                                            :class="packagingProgress === 100 ? 'bg-emerald-500' : 'bg-blue-500'"
                                            :style="`width: ${packagingProgress}%`"></div>
                                    </div>
                                </div>

                                <hr class="border-slate-200 mb-4">

                                <!-- Stage bars -->
                                <div class="flex flex-col gap-3">

                                    <!-- Download -->
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-medium text-slate-600 flex items-center gap-1.5">
                                                <span x-show="fileDownloadProgress === 100" class="text-emerald-500">✓</span>
                                                <span x-show="fileDownloadProgress < 100 && fileDownloadProgress > 0" class="inline-block h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                                                <span x-show="fileDownloadProgress === 0" class="inline-block h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                                                Downloading repositories
                                            </span>
                                            <span class="text-xs text-slate-400" x-text="fileDownloadProgress + '% · 10%'"></span>
                                        </div>
                                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                :class="fileDownloadProgress === 100 ? 'bg-emerald-400' : 'bg-blue-400'"
                                                :style="`width: ${fileDownloadProgress}%`"></div>
                                        </div>
                                    </div>

                                    <!-- Extraction (base + head side by side until both done) -->
                                    <div :class="baseFileExtraction === 100 && headFileExtraction === 100 ? 'flex flex-col gap-2' : 'grid grid-cols-2 gap-3'">
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-medium text-slate-600 flex items-center gap-1.5">
                                                    <span x-show="baseFileExtraction === 100" class="text-emerald-500">✓</span>
                                                    <span x-show="baseFileExtraction < 100 && baseFileExtraction > 0" class="inline-block h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                                                    <span x-show="baseFileExtraction === 0" class="inline-block h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                                                    Base Extraction
                                                </span>
                                                <span class="text-xs text-slate-400" x-text="baseFileExtraction + '% · 20%'"></span>
                                            </div>
                                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                <div class="h-full rounded-full transition-all duration-500"
                                                    :class="baseFileExtraction === 100 ? 'bg-emerald-400' : 'bg-blue-400'"
                                                    :style="`width: ${baseFileExtraction}%`"></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-medium text-slate-600 flex items-center gap-1.5">
                                                    <span x-show="headFileExtraction === 100" class="text-emerald-500">✓</span>
                                                    <span x-show="headFileExtraction < 100 && headFileExtraction > 0" class="inline-block h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                                                    <span x-show="headFileExtraction === 0" class="inline-block h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                                                    Head Extraction
                                                </span>
                                                <span class="text-xs text-slate-400" x-text="headFileExtraction + '% · 20%'"></span>
                                            </div>
                                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                <div class="h-full rounded-full transition-all duration-500"
                                                    :class="headFileExtraction === 100 ? 'bg-emerald-400' : 'bg-blue-400'"
                                                    :style="`width: ${headFileExtraction}%`"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Compare -->
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-medium text-slate-600 flex items-center gap-1.5">
                                                <span x-show="compareFilesProgress === 100" class="text-emerald-500">✓</span>
                                                <span x-show="compareFilesProgress < 100 && compareFilesProgress > 0" class="inline-block h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                                                <span x-show="compareFilesProgress === 0" class="inline-block h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                                                Comparing Files
                                            </span>
                                            <span class="text-xs text-slate-400" x-text="compareFilesProgress + '% · 10%'"></span>
                                        </div>
                                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                :class="compareFilesProgress === 100 ? 'bg-emerald-400' : 'bg-blue-400'"
                                                :style="`width: ${compareFilesProgress}%`"></div>
                                        </div>
                                    </div>

                                    <!-- Gen + Compress -->
                                    <div :class="packageGenProgress === 100 && compressionProgress === 100 ? 'flex flex-col gap-2' : 'grid grid-cols-2 gap-3'">
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-medium text-slate-600 flex items-center gap-1.5">
                                                    <span x-show="packageGenProgress === 100" class="text-emerald-500">✓</span>
                                                    <span x-show="packageGenProgress < 100 && packageGenProgress > 0" class="inline-block h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                                                    <span x-show="packageGenProgress === 0" class="inline-block h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                                                    Generating Packages
                                                </span>
                                                <span class="text-xs text-slate-400" x-text="packageGenProgress + '% · 20%'"></span>
                                            </div>
                                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                <div class="h-full rounded-full transition-all duration-500"
                                                    :class="packageGenProgress === 100 ? 'bg-emerald-400' : 'bg-blue-400'"
                                                    :style="`width: ${packageGenProgress}%`"></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-medium text-slate-600 flex items-center gap-1.5">
                                                    <span x-show="compressionProgress === 100" class="text-emerald-500">✓</span>
                                                    <span x-show="compressionProgress < 100 && compressionProgress > 0" class="inline-block h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                                                    <span x-show="compressionProgress === 0" class="inline-block h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                                                    Compressing Archives
                                                </span>
                                                <span class="text-xs text-slate-400" x-text="compressionProgress + '% · 20%'"></span>
                                            </div>
                                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                                <div class="h-full rounded-full transition-all duration-500"
                                                    :class="compressionProgress === 100 ? 'bg-emerald-400' : 'bg-blue-400'"
                                                    :style="`width: ${compressionProgress}%`"></div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Status message -->
                            <p class="mt-3 text-sm text-slate-500 min-h-[1.25rem]"
                                x-show="isRunning || packagingResult || packagingError"
                                x-text="packagingMessage"></p>

                            <!-- Error panel -->
                            <div x-show="packagingError" x-cloak
                                class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                <div class="font-semibold">Package generation failed</div>
                                <div class="mt-1" x-text="packagingError"></div>
                                <button type="button" @click="resetJob()"
                                    class="mt-3 text-xs font-semibold text-red-700 underline hover:text-red-900">
                                    Try again
                                </button>
                            </div>

                            <!-- Success summary (before download panel slides in) -->
                            <div x-show="packagingResult && !packagingError" x-cloak
                                class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                <div class="text-sm font-semibold text-emerald-800">Package created successfully ✓</div>
                                <div class="mt-2 text-sm text-slate-600 break-all"
                                    x-text="packagingResult?.folder_name || ''"></div>
                            </div>
                        </div>

                        <!-- Bottom action: try again after completion -->
                        <div class="mt-6" x-show="jobStatus === 'completed' || jobStatus === 'failed'">
                            <button type="button" @click="resetJob()"
                                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                                ← New Package
                            </button>
                        </div>
                    </div>

                    {{-- ── Right: Download / deploy panel (slides in on complete) ─ --}}
                    <div x-show="packagingResult" x-cloak
                        x-transition:enter="transition-all ease-out duration-700 delay-200"
                        x-transition:enter-start="opacity-0 translate-x-12"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        class="p-8 w-full lg:w-[55%] flex flex-col bg-white">

                        <div>
                            <h3 class="text-2xl font-bold text-slate-900">
                                Package: <span x-text="packagingResult?.folder_name" class="break-all"></span>
                            </h3>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500 mt-2">
                                <span>Size: <span x-text="packagingResult?.file_size"></span></span>
                                <span class="text-slate-300">|</span>
                                <span class="break-all">SHA256: <span x-text="packagingResult?.sha256"></span></span>
                            </div>
                        </div>

                        <div class="pt-8 grid grid-cols-1 gap-10 xl:grid-cols-[1fr_auto_1fr] flex-1">

                            <!-- Download -->
                            <div class="space-y-6 flex flex-col justify-start">
                                <div class="space-y-2">
                                    <h3 class="text-xl font-semibold text-slate-900">Download Package</h3>
                                    <p class="text-sm text-slate-500">Download directly to your computer.</p>
                                </div>
                                <div class="flex flex-col items-center justify-center gap-3 pt-2">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-slate-200 bg-amber-50 text-3xl shadow-sm">
                                        📦
                                    </div>
                                    <span class="text-xs font-medium tracking-wide text-slate-400 uppercase"
                                        x-text="(activeRow?.format || '.zip').toUpperCase() + ' Package'"></span>
                                </div>
                                <div>
                                    <button type="button" @click="downloadPackage()"
                                        class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-blue-600 px-6 py-4 text-base font-medium text-white shadow-sm transition hover:bg-blue-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v1a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1" />
                                        </svg>
                                        <span>Download</span>
                                        <span class="rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                            x-text="activeRow?.format || '.zip'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- OR divider -->
                            <div class="relative hidden h-full items-center xl:flex">
                                <div class="h-full w-px bg-slate-200"></div>
                                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-200 bg-white text-sm font-medium text-slate-500 shadow-sm">OR</div>
                                </div>
                            </div>

                            <!-- Deploy (placeholder, matching V2 design) -->
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <h3 class="text-lg font-bold text-slate-900">Deploy to Hosting Server</h3>
                                </div>
                                <div class="space-y-3">
                                    <label class="block text-sm font-semibold text-slate-800">Server Type</label>
                                    <select class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                                        <option selected disabled>Select a server profile...</option>
                                        <option>Production (Apache)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-2">Deployment Path</label>
                                    <input type="text" value="/var/www/html/cybix/current"
                                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                                </div>
                                <div>
                                    <button type="button"
                                        class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-blue-600 px-6 py-4 text-base font-medium text-white shadow-sm transition hover:bg-blue-700">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h16M7 17V7h10v10M9 7V5h6v2"/>
                                        </svg>
                                        <span>Deploy Now</span>
                                        <span class="rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">Ready</span>
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
@endsection

@push('scripts')
    <script>
        function newPackageWizard({ repositories, queueUrl, jobProgressBaseUrl, downloadUrl, csrfToken }) {
            return {
                // ── Repository & version state ───────────────────────────────
                repositories,
                selectedRepository: '',
                repoData: null,
                repoBranches: [],
                repoTags: [],
                repoReleases: [],
                isLoadingVersions: false,
                rateLimit: null,

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
                    style: '',
                },

                // ── Queue / job state ────────────────────────────────────────
                currentJobId: null,         // DB ID of the currently-running job
                jobStatus: '',              // queued | running | completed | failed
                isQueuing: false,           // true while POSTing to create jobs
                isRunning: false,           // true while polling and not yet terminal
                pollIntervalId: null,       // setInterval handle
                activeRow: null,            // snapshot of the row currently being processed

                // Multi-job queue
                jobQueue: [],               // array of { row, jobId } entries to process
                jobQueueIndex: 0,           // index of the currently running job
                jobResults: [],             // accumulated results [{row, result, error}]

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
                    return this.allRepoVersions.filter(v => !tf || v.type === tf);
                },

                // ── Lifecycle ────────────────────────────────────────────────

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

                // ── Row helpers ───────────────────────────────────────────────

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
                            environment: prev.environment || 'PROD',
                            format: prev.format || '.zip',
                            customName: false, name: ''
                        });
                    }
                },

                updateRowNames() {
                    const proj = this.selectedRepositoryLabel || '[Project]';
                    const now  = new Date();
                    const pad  = n => String(n).padStart(2, '0');
                    const ts   = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}`;

                    this.packageRows.forEach(row => {
                        if (!row.customName && (row.base || row.head)) {
                            const env  = row.environment || '';
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

                // ── Floating dropdown helpers ─────────────────────────────────

                openFloatDd(btn, rowIndex, field) {
                    if (this.floatDd.open && this.floatDd.rowIndex === rowIndex && this.floatDd.field === field) {
                        this.floatDd.open = false;
                        return;
                    }
                    const r = btn.getBoundingClientRect();
                    this.floatDd.style = `position:fixed;z-index:99999;top:${r.bottom + 6}px;left:${r.left}px;width:${Math.max(r.width, 240)}px`;
                    this.floatDd.rowIndex   = rowIndex;
                    this.floatDd.field      = field;
                    this.floatDd.typeFilter = '';
                    this.floatDd.open       = true;
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

                // ── Queue-based packaging ─────────────────────────────────────

                async startPackaging() {
                    const rows = this.completeRows;
                    if (!this.canStartQueue || rows.length === 0) return;

                    // Reset all state for a fresh multi-job run
                    this.isQueuing        = true;
                    this.isRunning        = false;
                    this.packagingResult  = null;
                    this.packagingError   = '';
                    this.currentJobId     = null;
                    this.jobStatus        = '';
                    this.jobQueue         = [];
                    this.jobQueueIndex    = 0;
                    this.jobResults       = [];
                    this.packagingMessage = `Submitting ${rows.length} job(s) to queue...`;

                    // ── Submit ALL complete rows to the backend at once ──────
                    try {
                        for (const row of rows) {
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
                                    environment:  row.environment,
                                    project_name: this.selectedRepositoryLabel,
                                    base_version: baseRef,
                                    head_version: headRef,
                                    repo:         this.selectedRepository,
                                    package_name: row.name,
                                    format:       row.format,
                                }),
                            });

                            const data = await res.json();
                            if (!res.ok || !data.job_id) {
                                throw new Error(data.message || `Failed to queue job for row: ${row.name}`);
                            }

                            this.jobQueue.push({ row: { ...row }, jobId: data.job_id, status: data.status });
                        }
                    } catch (err) {
                        this.packagingError   = err.message || 'Unknown error submitting jobs.';
                        this.packagingMessage = 'Failed to queue jobs.';
                        this.isQueuing = false;
                        return;
                    }

                    this.isQueuing = false;
                    this.isRunning = true;
                    this.packagingMessage = `All ${this.jobQueue.length} job(s) queued — starting...`;

                    // ── Process jobs sequentially ────────────────────────────
                    await this.processNextJob();
                },

                async processNextJob() {
                    if (this.jobQueueIndex >= this.jobQueue.length) {
                        // All done
                        this.isRunning        = false;
                        this.packagingMessage = `All ${this.jobQueue.length} package(s) completed.`;
                        return;
                    }

                    const entry = this.jobQueue[this.jobQueueIndex];
                    this.activeRow     = entry.row;
                    this.currentJobId  = entry.jobId;
                    this.jobStatus     = entry.status;

                    // Reset per-job progress bars
                    this.packagingProgress    = 0;
                    this.fileDownloadProgress = 0;
                    this.headFileExtraction   = 0;
                    this.baseFileExtraction   = 0;
                    this.compareFilesProgress = 0;
                    this.packageGenProgress   = 0;
                    this.compressionProgress  = 0;
                    this.packagingResult      = null;
                    this.packagingError       = '';

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
                            const prog    = payload.progress || {};

                            this.jobStatus = payload.status;

                            // Advance stage fields (only forward, never retreat)
                            const adv = (key, val) => {
                                if (val !== undefined && Number(val) > this[key]) this[key] = Number(val);
                            };
                            adv('packagingProgress',   prog.packagingProgress);
                            adv('fileDownloadProgress', prog.fileDownloadProgress);
                            adv('headFileExtraction',   prog.headFileExtraction);
                            adv('baseFileExtraction',   prog.baseFileExtraction);
                            adv('compareFilesProgress', prog.compareFilesProgress);
                            adv('packageGenProgress',   prog.packageGenProgress);
                            adv('compressionProgress',  prog.compressionProgress);
                            if (prog.packagingMessage)  this.packagingMessage = prog.packagingMessage;

                            // Terminal states
                            if (payload.status === 'completed') {
                                this.stopPolling();
                                // Force all bars to 100
                                this.packagingProgress  = 100;
                                this.fileDownloadProgress = this.headFileExtraction = this.baseFileExtraction =
                                this.compareFilesProgress = this.packageGenProgress = this.compressionProgress = 100;
                                this.packagingMessage = 'Package created successfully.';
                                this.packagingResult  = payload.result;

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
                                this.packagingError  = payload.error || 'Job failed.';
                                this.packagingMessage = 'Packaging failed.';

                                // Record failure and stop (don't auto-advance on error)
                                this.jobResults.push({ row: this.activeRow, result: null, error: this.packagingError });
                                this.isRunning = false;
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
                    this.currentJobId       = null;
                    this.jobStatus          = '';
                    this.isQueuing          = false;
                    this.isRunning          = false;
                    this.packagingResult    = null;
                    this.packagingError     = '';
                    this.packagingProgress  = 0;
                    this.fileDownloadProgress = 0;
                    this.headFileExtraction   = 0;
                    this.baseFileExtraction   = 0;
                    this.compareFilesProgress = 0;
                    this.packageGenProgress   = 0;
                    this.compressionProgress  = 0;
                    this.packagingMessage   = '';
                    this.activeRow          = null;
                    this.jobQueue           = [];
                    this.jobQueueIndex      = 0;
                    this.jobResults         = [];
                },

                downloadPackage() {
                    if (!this.packagingResult) return;
                    const folder = encodeURIComponent(this.packagingResult.folder_name);
                    const fmt    = this.activeRow?.format || '.zip';

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

                // ── GitHub API helpers ────────────────────────────────────────

                async fetchRateLimit() {
                    try {
                        this.rateLimit = await (await fetch('/github/rate-limit')).json();
                    } catch (e) {}
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
                        const res  = await fetch(`/github/repo-versions?repo=${encodeURIComponent(this.selectedRepository)}`);
                        const data = res.ok ? await res.json() : {};
                        this.repoBranches  = data.branches || [];
                        this.repoTags      = data.tags     || [];
                        this.repoReleases  = data.releases || [];
                    } catch {
                        this.repoBranches = this.repoTags = this.repoReleases = [];
                    } finally {
                        this.isLoadingVersions = false;
                    }
                },
            };
        }
    </script>
@endpush