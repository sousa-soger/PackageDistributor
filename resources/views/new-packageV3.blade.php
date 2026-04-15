@extends('layouts.app')

@section('title', 'New Package')

@section('content')
    <div class="max-w-7xl mx-auto space-y-8 pt-4 pb-12" x-data="newPackageWizard({
                repositories: @js($repositories),
                queueUrl: '{{ route('deployments.queue-job') }}',
                jobProgressBaseUrl: '{{ url('/deployments/jobs') }}',
                downloadUrl: '{{ route('download.archive') }}',
                csrfToken: '{{ csrf_token() }}',
                dbQueuedPackages: @js($queuedPackages)
            })">
        {{-- ================================================================ --}}
        {{-- CARD 1 — Repository selection + multi-row version picker --}}
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

                        <div class="flex text-sm font-semibold text-slate-800 pb-4">
                            <div class="w-[20%] text-center">BASE</div>
                            <div class="w-[20%] text-center">HEAD</div>
                            <div class="w-[12%] text-center">Environment</div>
                            <div class="w-[48%] text-left pl-6">Package Folder Name</div>
                        </div>

                        <!-- Relative container for continuous vertical lines -->
                        <div class="relative pt-2 pb-2">
                            <div class="absolute -top-6 bottom-0 left-[20%] w-px bg-slate-200"></div>
                            <div class="absolute -top-6 bottom-0 left-[40%] w-px bg-slate-200"></div>
                            <div class="absolute -top-6 bottom-0 left-[52%] w-px bg-slate-200"></div>

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

                                        <!-- Package Folder Name -->
                                        <div class="w-[48%] pl-6 pr-2 flex items-center gap-3">
                                            <input type="text" x-model="row.name"
                                                @input="row.customName = true; handleRowInteract(index)"
                                                :readonly="!row.customName || !isRowReadyForName(row)"
                                                :disabled="!isRowReadyForName(row)"
                                                :class="(!row.customName || !isRowReadyForName(row)) ? 'bg-slate-50 text-slate-400 border-slate-100 cursor-not-allowed' : 'bg-white text-slate-700 border-slate-200'"
                                                class="w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder:text-slate-300 transition-colors"
                                                placeholder="">
                                            <!-- Pencil / Revert icon -->
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
                                            <!-- Trash / Remove row icon (hidden on first row) -->
                                            <button type="button" x-show="index > 0" x-cloak @click="removeRow(index)"
                                                title="Remove this job"
                                                class="shrink-0 text-red-500 hover:text-red-600 transition-colors focus:outline-none ">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Add Job Button — shown when the last row is complete -->
                    <div x-show="canAddRow" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0" class="pt-3 flex justify-center">
                        <button type="button" @click="addRow()"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-blue-300 bg-blue-50 px-5 py-2.5 text-sm font-semibold text-blue-600 shadow-none transition hover:border-blue-500 hover:bg-blue-100 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Job
                        </button>
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
                        <button type="button" id="btn-process-queue" @click="startPackaging()"
                            :disabled="!canStartQueue || isQueuing || isRunning"
                            class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition disabled:bg-slate-300 disabled:cursor-not-allowed shadow-sm hover:bg-blue-700">
                            <span x-show="!isQueuing && !isRunning">Process Queue</span>
                            <span x-show="isQueuing" x-cloak>Submitting...</span>
                            <span x-show="isRunning && !isQueuing" x-cloak class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5"
                                        class="opacity-20" />
                                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5"
                                        stroke-linecap="round" />
                                </svg>
                                Running...
                            </span>
                        </button>

                        <!-- Queued job badge -->
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
        {{-- new CARD 2 — Queue + Progress + Result --}}
        {{-- ================================================================ --}}
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
                                                    width="16" class="flex-shrink-0"
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
                                                    width="16" class="flex-shrink-0"
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
                                    </tr>
                                    <tr x-show="job.jobId === currentJobId" x-cloak x-transition.origin.top
                                        class="bg-indigo-50/30 border-t border-indigo-100/50 shadow-inner">
                                        <td colspan="6" class="px-6 py-5">
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
                                                            }" :style="`width: ${packagingProgress}%`">
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

        {{-- ============================================================ --}}


        {{-- ================================================================ --}}
        {{-- CARD 3 — View list of previously generated packages by the user --}}
        {{-- ================================================================ --}}
        <div>
            <x-ui.card class="p-8 w-full">
                <div class="space-y-6">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Previously Generated Packages</h2>
                        <p class="text-sm text-slate-500 mt-1">
                            View and download packages that have been generated previously.
                        </p>
                    </div>

                    @if($packages->isEmpty())
                        <div class="rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
                            No completed packages found.
                        </div>
                    @else
                        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Env</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Project</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Version</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Created</th>
                                        <th class="px-4 py-3"></th>
                                        <th class="px-4 py-3"></th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                @foreach($packages as $package)
                                    <tbody x-data="{ expanded: false }" class="divide-y divide-slate-100">
                                        <tr @click="expanded = !expanded"
                                            class="cursor-pointer hover:bg-slate-50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-slate-800">{{ $package->environment }}</td>
                                            <td class="px-4 py-3 text-sm font-bold text-slate-800">{{ $package->project_name }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-800">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="px-2 py-0.5 rounded border border-rose-100 bg-rose-50 text-rose-700 font-medium text-sm whitespace-nowrap">{{ $package->base_version }}</span>
                                                    <span class="text-slate-700 text-lg">→</span>
                                                    <span
                                                        class="px-2 py-0.5 rounded border border-emerald-100 bg-emerald-50 text-emerald-700 font-medium text-sm whitespace-nowrap">{{ $package->head_version }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">
                                                {{ $package->created_at->format('d M Y, h:i A') }}
                                            </td>
                                            <td class="px-4 py-3" @click.stop>
                                                <div class="flex items-center gap-2">
                                                    <svg aria-hidden="true" height="16" viewBox="0 0 16 16" version="1.1" width="16"
                                                        class="octicon octicon-file-zip color-fg-muted flex-shrink-0">
                                                        <path
                                                            d="M3.5 1.75v11.5c0 .09.048.173.126.217a.75.75 0 0 1-.752 1.298A1.748 1.748 0 0 1 2 13.25V1.75C2 .784 2.784 0 3.75 0h5.586c.464 0 .909.185 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v8.586A1.75 1.75 0 0 1 12.25 15h-.5a.75.75 0 0 1 0-1.5h.5a.25.25 0 0 0 .25-.25V4.664a.25.25 0 0 0-.073-.177L9.513 1.573a.25.25 0 0 0-.177-.073H7.25a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5h-3a.25.25 0 0 0-.25.25Zm3.75 8.75h.5c.966 0 1.75.784 1.75 1.75v3a.75.75 0 0 1-.75.75h-2.5a.75.75 0 0 1-.75-.75v-3c0-.966.784-1.75 1.75-1.75ZM6 5.25a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 6 5.25Zm.75 2.25h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 6.75A.75.75 0 0 1 8.75 6h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 6.75ZM8.75 3h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 9.75A.75.75 0 0 1 8.75 9h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 9.75Zm-1 2.5v2.25h1v-2.25a.25.25 0 0 0-.25-.25h-.5a.25.25 0 0 0-.25.25Z">
                                                        </path>
                                                    </svg>
                                                    <a href="{{ route('download.archive', ['folder' => $package->package_name, 'format' => '.zip']) }}"
                                                        class="no-underline group">
                                                        <span class="text-sm text-blue-600 font-medium group-hover:underline">Update
                                                            package</span>
                                                        <span
                                                            class="text-sm text-blue-600 font-medium group-hover:underline">(.zip)</span>
                                                    </a>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3" @click.stop>
                                                <div class="flex items-center gap-2">
                                                    <svg aria-hidden="true" height="16" viewBox="0 0 16 16" version="1.1" width="16"
                                                        class="octicon octicon-file-zip color-fg-muted flex-shrink-0">
                                                        <path
                                                            d="M3.5 1.75v11.5c0 .09.048.173.126.217a.75.75 0 0 1-.752 1.298A1.748 1.748 0 0 1 2 13.25V1.75C2 .784 2.784 0 3.75 0h5.586c.464 0 .909.185 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v8.586A1.75 1.75 0 0 1 12.25 15h-.5a.75.75 0 0 1 0-1.5h.5a.25.25 0 0 0 .25-.25V4.664a.25.25 0 0 0-.073-.177L9.513 1.573a.25.25 0 0 0-.177-.073H7.25a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5h-3a.25.25 0 0 0-.25.25Zm3.75 8.75h.5c.966 0 1.75.784 1.75 1.75v3a.75.75 0 0 1-.75.75h-2.5a.75.75 0 0 1-.75-.75v-3c0-.966.784-1.75 1.75-1.75ZM6 5.25a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 6 5.25Zm.75 2.25h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 6.75A.75.75 0 0 1 8.75 6h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 6.75ZM8.75 3h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 9.75A.75.75 0 0 1 8.75 9h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 9.75Zm-1 2.5v2.25h1v-2.25a.25.25 0 0 0-.25-.25h-.5a.25.25 0 0 0-.25.25Z">
                                                        </path>
                                                    </svg>
                                                    <a href="{{ route('download.archive', ['folder' => $package->package_name, 'format' => '.tar.gz']) }}"
                                                        class="no-underline group">
                                                        <span class="text-sm text-blue-600 font-medium group-hover:underline">Update
                                                            package</span>
                                                        <span
                                                            class="text-sm text-blue-600 font-medium group-hover:underline">(.tar.gz)</span>
                                                    </a>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-5 w-5 inline-block shrink-0 text-slate-400 transition-transform duration-200"
                                                    :class="expanded ? '' : 'rotate-90'" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </td>
                                        </tr>
                                        <tr x-show="expanded" x-cloak class="bg-slate-50 border-t border-slate-100">
                                            <td colspan="7" class="px-6 py-5">
                                                <div class="flex flex-col space-y-5 max-w-4xl">
                                                    <!-- Package Name & Meta -->
                                                    <div>
                                                        <div class="text-base text-slate-800">
                                                            <span class="font-bold">Package:</span> <span
                                                                class="font-bold">{{ $package->package_name }}</span>
                                                        </div>
                                                        <div class="text-xs text-slate-500 mt-1 flex items-center space-x-2">
                                                            <span>zip :</span>
                                                            <span>Size: {{ $package->zip_size ?? 'N/A' }}</span>
                                                            <span class="text-slate-300">|</span>
                                                            <span>SHA256:
                                                                {{ $package->zip_sha256 ?? 'N/A' }}</span>
                                                        </div>
                                                        <div class="text-xs text-slate-500 mt-1 flex items-center space-x-2">
                                                            <span>tar.gz :</span>
                                                            <span>Size: {{ $package->targz_size ?? 'N/A' }}</span>
                                                            <span class="text-slate-300">|</span>
                                                            <span>SHA256:
                                                                {{ $package->targz_sha256 ?? 'N/A' }}</span>
                                                        </div>
                                                    </div>

                                                    <!-- Deploy to Hosting Server -->
                                                    <div>
                                                        <h4 class="text-base font-bold text-slate-800 mb-3">Deploy to Hosting
                                                            Server</h4>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <!-- Server Type -->
                                                            <div>
                                                                <label
                                                                    class="block text-sm font-semibold text-slate-700 mb-2">Server
                                                                    Type</label>
                                                                <select
                                                                    class="block w-full rounded-md border border-slate-300 py-2 px-3 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 bg-white">
                                                                    <option value="">Select a server profile...</option>
                                                                </select>
                                                            </div>
                                                            <!-- Deployment Path -->
                                                            <div>
                                                                <label
                                                                    class="block text-sm font-semibold text-slate-700 mb-2">Deployment
                                                                    Path</label>
                                                                <input type="text"
                                                                    class="block w-full rounded-md border border-slate-300 py-2 px-3 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 bg-white"
                                                                    value="">
                                                            </div>
                                                        </div>

                                                        <!-- Deploy Button -->
                                                        <div class="mt-5 flex justify-center">
                                                            <button type="button"
                                                                class="inline-flex items-center justify-center gap-3 rounded-2xl bg-blue-600 px-6 py-4 text-base font-medium text-white shadow-sm transition hover:bg-blue-700">
                                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor" stroke-width="1.8">
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
                                            </td>
                                        </tr>
                                    </tbody>
                                @endforeach
                            </table>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        </div>
        {{-- ============================================================ --}}

    </div>
@endsection

@push('scripts')
    <script>
        function newPackageWizard({ repositories, queueUrl, jobProgressBaseUrl, downloadUrl, csrfToken, dbQueuedPackages }) {
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
                unifiedQueue: dbQueuedPackages.map(dbJob => ({
                    jobId: dbJob.id,
                    status: dbJob.status,
                    created_at: dbJob.created_at,
                    row: {
                        environment: dbJob.environment,
                        project_name: dbJob.project_name,
                        base_version: dbJob.base_version,
                        head_version: dbJob.head_version,
                        name: dbJob.package_name,
                    }
                })),

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
                    return this.allRepoVersions.filter(v => !tf || v.type === tf);
                },

                // ── Lifecycle ────────────────────────────────────────────────

                init() {
                    this.fetchRateLimit();
                    this.$watch('selectedRepository', async () => {
                        this.floatDd.open = false;
                        this.packageRows = [
                            { id: Date.now(), base: '', head: '', environment: 'PROD', customName: false, name: '' }
                        ];
                        await this.fetchRepoData();
                        await this.fetchRepoVersions();
                        await this.fetchRateLimit();
                    });
                    setInterval(() => this.updateRowNames(), 60000);
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
                    // No longer auto-adds a row — user must click the "Add Job" button
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

                // ── Queue-based packaging ─────────────────────────────────────

                async startPackaging() {
                    const rows = this.completeRows;
                    if (!this.canStartQueue || rows.length === 0) return;

                    // Reset all state for a fresh multi-job run
                    this.isQueuing = true;
                    this.isRunning = false;
                    this.packagingResult = null;
                    this.packagingError = '';
                    this.currentJobId = null;
                    this.jobStatus = '';
                    this.jobQueue = [];
                    this.jobQueueIndex = 0;
                    this.jobResults = [];
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
                                created_at: new Date().toISOString()
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
                    this.jobStatus = entry.status;

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

                            this.jobStatus = payload.status;

                            // Let's propagate the new status to the unified list to instantly show "running" tag
                            const uq = this.unifiedQueue.find(q => q.jobId === this.currentJobId);
                            if (uq) uq.status = this.jobStatus;

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
                    this.currentJobId = null;
                    this.jobStatus = '';
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

                // ── GitHub API helpers ────────────────────────────────────────

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
                },
            };
        }
    </script>
@endpush