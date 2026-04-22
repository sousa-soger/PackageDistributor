<x-ui.card class="p-8 w-full">
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Package Selection</h2>
            <p class="text-sm text-slate-500 mt-1">
                Choose the project and versions you want to create the update and rollback packages from.
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Project Name:</label>
            <select x-model="selectedRepository"
                class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500">
                <option value="" disabled>Select the repository of the project...</option>
                @foreach ($repositories as $repository)
                    <option value="{{ $repository['id'] }}">
                        {{ $repository['label'] }} ({{ $repository['owner'] }}/{{ $repository['repo'] }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Show Description -->
        {{-- 
        <div x-show="repoData" class="rounded-xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-700">
            <div class="flex flex-col gap-1">
                <span class="font-semibold text-slate-900">Description:</span>
                <span class="text-slate-500" x-text="repoData?.description || '-'"></span>
            </div>
        </div>
        --}}

        
        <style>
            .repo-loading-container {
                position: relative;
                overflow: hidden;
                min-height: 120px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
           
        </style>
        
        <div x-show="selectedRepository !== null && isLoadingVersions" x-cloak
            class="mt-1 repo-loading-container rounded-xl border border-slate-200 bg-white text-center text-sm text-slate-500">
            <!-- Animation Background Elements -->
            {{-- 
            <div class="rings-wrapper">
                <div class="ring"></div>
                <div class="ring"></div>
                <div class="ring"></div>
            </div>
            <div class="glow-orb"></div>
            --}}
            
            <div class="repo-loader" aria-label="Loading repository" role="img">
                <span class="seg s1"></span>
                <span class="seg s2"></span>
                <span class="seg s3"></span>
                <span class="seg s4"></span>
                <span class="seg s5"></span>
                <span class="seg s6"></span>
                <span class="seg s7"></span>
                <span class="beam"></span>
            </div>
    
            
            
            <div class="relative z-10 flex flex-col items-center justify-center">
                <span class="font-medium text-blue-900 animate-pulse">Loading repository versions...</span>
            </div>
        </div>

        <!-- Multi-row Base/Head Selection -->
        <div class="mt-10 overflow-visible" x-show="selectedRepository && !isLoadingVersions" x-cloak>
            <div class="min-w-[900px]">
                <div class="flex text-sm font-semibold text-slate-800 pb-4">
                    <div class="w-[20%] flex items-center justify-center gap-1.5 relative">
                        <span>Current Version</span>
                        <span class="inline-flex items-center" title="Outdated Version">
                            <div class="relative inline-flex items-center justify-center">
                                <svg class="w-7 h-7 text-slate-400 stroke-current drop-shadow-sm" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" fill="#f8fafc"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                                <div class="absolute -bottom-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 ring-[2.5px] ring-white">
                                    <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 3"/></svg>
                                </div>
                            </div>
                        </span>

                        <!-- Connector Arrow -->
                        <div class="absolute -right-3 top-1/2 -translate-y-1/2 text-slate-400 z-20">
                            
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </div>
                    </div>
                    <div class="w-[20%] flex items-center justify-center gap-1.5">
                        <span>Target Version</span>
                        <span class="inline-flex items-center" title="Updated Version">
                            <div class="relative inline-flex items-center justify-center">
                                <svg class="w-7 h-7 text-blue-500 stroke-current drop-shadow-md" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" fill="#eff6ff"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                                <div class="absolute -bottom-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-blue-50 ring-[2.5px] ring-white">
                                    <svg class="h-3.5 w-3.5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                                </div>
                            </div>
                        </span>
                    </div>
                    <div class="w-[12%] text-center">Environment</div>
                    <div class="w-[48%] text-left pl-6">Package Folder Name</div>
                </div>

                <!-- Relative container for continuous vertical lines -->
                <div class="relative pt-2 pb-2">
                    <div class="absolute -top-1 bottom-0 left-[20%] w-px bg-slate-300"></div>
                    <div class="absolute -top-7 bottom-0 left-[40%] w-px bg-slate-300"></div>
                    <div class="absolute -top-7 bottom-0 left-[52%] w-px bg-slate-300"></div>

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
                                        :class="{
                                            'bg-white text-slate-700 border-slate-200 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 cursor-text': row.customName,
                                            'bg-transparent text-slate-700 border-transparent shadow-none focus:outline-none focus:ring-0 cursor-default': !row.customName,
                                            'opacity-50 cursor-not-allowed select-none': !isRowReadyForName(row)
                                        }"
                                        class="w-full rounded-lg border px-4 py-2.5 text-sm placeholder:text-slate-400 transition-all"
                                        placeholder="">
                                    <!-- Pencil / Revert icon -->
                                    <button type="button"
                                        @click="row.customName = !row.customName; if(row.customName) { $nextTick(() => { $el.previousElementSibling.focus() }) }; handleRowInteract(index)"
                                        x-show="isRowReadyForName(row)" x-cloak
                                        class="shrink-0 transition focus:outline-none"
                                        :class="row.customName ? 'text-slate-500 hover:text-slate-600' : 'text-blue-600 hover:text-blue-800'"
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

        <div class="mt-8 relative z-10" x-show="selectedRepository && canStartQueue" x-cloak>
            <div class="mb-6 border-t border-slate-200 pt-6">
                <h2 class="text-xl font-semibold text-slate-800">Start Packaging</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Build the update and rollback packages based on the selected Git versions.
                </p>
            </div>

            <div class="mt-6 flex items-center gap-4">
                <!-- Process Queue button -->
                <button type="button" id="btn-process-queue" @click="startPackaging()"
                    {{--  :disabled="!canStartQueue || isQueuing || isRunning"--}}
                    class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition disabled:bg-slate-300 disabled:cursor-not-allowed shadow-sm hover:bg-blue-700">
                    <span>Process Queue</span>
                    <!-- 
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
                    -->
                </button>
            </div>
        </div>

    </div>
</x-ui.card>

{{-- ── Duplicate Package Modal ─────────────────────────────────────────── --}}
{{-- Sits in the parent Alpine scope (newPackageWizard) so it has access   --}}
{{-- to duplicateModal state. Fixed overlay — works anywhere in the DOM.   --}}
<div x-show="duplicateModal.open" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm !mb-0" {{-- !mb-0 to force the modal to be pushed below since the new-packV3.blade.php is space-y-8 --}}
    @click.self="duplicateModal.open = false">

    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-lg mx-4 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">

        {{-- Header --}}
        <div class="flex items-start justify-between px-6 py-5 border-b border-slate-100">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Package Exists in History</h3>
                <p class="text-sm text-slate-500 mt-0.5">Would you want to download the existing package?</p>
            </div>
            <button @click="duplicateModal.open = false"
                class="text-slate-400 hover:text-slate-600 transition-colors ml-4 shrink-0 mt-0.5 focus:outline-none"
                title="Close">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 space-y-4">

            {{-- Package name --}}
            <p class="text-sm font-bold text-slate-900"
                x-text="'Package: ' + (duplicateModal.match?.package_name ?? '')"></p>

            {{-- Job details row --}}
            <div class="flex flex-wrap items-center gap-3 text-sm py-1">
                <span class="font-semibold text-slate-700 shrink-0"
                    x-text="duplicateModal.match?.environment ?? ''"></span>
                <span class="font-bold text-slate-800"
                    x-text="duplicateModal.match?.project_name ?? ''"></span>
                <div class="flex items-center gap-2">
                    <span
                        class="px-2 py-0.5 rounded border border-rose-100 bg-rose-50 text-rose-700 font-medium text-xs whitespace-nowrap"
                        x-text="duplicateModal.match?.base_version ?? ''"></span>
                    <span class="text-slate-500 text-lg">→</span>
                    <span
                        class="px-2 py-0.5 rounded border border-emerald-100 bg-emerald-50 text-emerald-700 font-medium text-xs whitespace-nowrap"
                        x-text="duplicateModal.match?.head_version ?? ''"></span>
                </div>
                <span class="text-slate-400 text-xs ml-auto whitespace-nowrap">
                    Created at : 
                <span class="text-slate-400 text-xs ml-auto whitespace-nowrap"
                    x-text="duplicateModal.match?.finished_at
                        ? new Date(duplicateModal.match.finished_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                        : ''">
                </span>
                </span>
            </div>

            {{-- Checksums --}}
            <div class="text-xs text-slate-500 space-y-1.5 bg-slate-50 rounded-xl p-3 border border-slate-100">

                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5" x-show="duplicateModal.match?.zip_size">
                    <span>
                        <svg class="h-4 w-4 text-slate-500 shrink-0" aria-hidden="true" height="16" viewBox="0 0 16 16"
                            version="1.1" width="16">
                            <path fill="currentColor"
                                d="M3.5 1.75v11.5c0 .09.048.173.126.217a.75.75 0 0 1-.752 1.298A1.748 1.748 0 0 1 2 13.25V1.75C2 .784 2.784 0 3.75 0h5.586c.464 0 .909.185 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v8.586A1.75 1.75 0 0 1 12.25 15h-.5a.75.75 0 0 1 0-1.5h.5a.25.25 0 0 0 .25-.25V4.664a.25.25 0 0 0-.073-.177L9.513 1.573a.25.25 0 0 0-.177-.073H7.25a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5h-3a.25.25 0 0 0-.25.25Zm3.75 8.75h.5c.966 0 1.75.784 1.75 1.75v3a.75.75 0 0 1-.75.75h-2.5a.75.75 0 0 1-.75-.75v-3c0-.966.784-1.75 1.75-1.75ZM6 5.25a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 6 5.25Zm.75 2.25h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 6.75A.75.75 0 0 1 8.75 6h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 6.75ZM8.75 3h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 9.75A.75.75 0 0 1 8.75 9h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 9.75Zm-1 2.5v2.25h1v-2.25a.25.25 0 0 0-.25-.25h-.5a.25.25 0 0 0-.25.25Z" />
                        </svg>
                    </span>
                    <a :href="'{{ url('download-archive') }}?folder=' + encodeURIComponent(duplicateModal.match?.package_name ?? '') + '&format=.zip'"
                        class="flex items-center gap-1.5 group">
                        <svg class="h-4 w-4 text-blue-600" width="15" height="15" viewBox="0 0 15 15" fill="currentColor"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z" />
                        </svg>
                        <span class="text-sm text-blue-600 font-medium group-hover:underline">Package (.zip)</span>
                    </a>
                    <span class="text-slate-300">|</span>
                    <span>Size: <span class="font-medium" x-text="duplicateModal.match?.zip_size ?? '-'"></span></span>
                    <span class="text-slate-300">|</span>
                    <button
                        type="button"
                        x-data="{ copied: false }"
                        class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 transition-colors"
                        @click="
                            const txt = duplicateModal.match?.zip_sha256 ?? '';
                            if (navigator.clipboard) {
                                navigator.clipboard.writeText(txt);
                            } else {
                                const el = document.createElement('textarea');
                                el.value = txt;
                                el.style.position = 'fixed';
                                el.style.opacity = '0';
                                document.body.appendChild(el);
                                el.select();
                                document.execCommand('copy');
                                document.body.removeChild(el);
                            }
                            copied = true;
                            setTimeout(() => copied = false, 1500);
                        "
                        title="Copy ZIP SHA256"
                    >
                        <span x-text="copied ? 'Copied!' : 'SHA256'"></span>
                        <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <svg x-show="copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>
                    
                </div>

                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5" x-show="duplicateModal.match?.targz_size">
                    <span>
                        <svg class="h-4 w-4 text-slate-500 shrink-0" aria-hidden="true" height="16" viewBox="0 0 16 16"
                            version="1.1" width="16">
                            <path fill="currentColor"
                                d="M3.5 1.75v11.5c0 .09.048.173.126.217a.75.75 0 0 1-.752 1.298A1.748 1.748 0 0 1 2 13.25V1.75C2 .784 2.784 0 3.75 0h5.586c.464 0 .909.185 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v8.586A1.75 1.75 0 0 1 12.25 15h-.5a.75.75 0 0 1 0-1.5h.5a.25.25 0 0 0 .25-.25V4.664a.25.25 0 0 0-.073-.177L9.513 1.573a.25.25 0 0 0-.177-.073H7.25a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5h-3a.25.25 0 0 0-.25.25Zm3.75 8.75h.5c.966 0 1.75.784 1.75 1.75v3a.75.75 0 0 1-.75.75h-2.5a.75.75 0 0 1-.75-.75v-3c0-.966.784-1.75 1.75-1.75ZM6 5.25a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 6 5.25Zm.75 2.25h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 6.75A.75.75 0 0 1 8.75 6h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 6.75ZM8.75 3h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 9.75A.75.75 0 0 1 8.75 9h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 9.75Zm-1 2.5v2.25h1v-2.25a.25.25 0 0 0-.25-.25h-.5a.25.25 0 0 0-.25.25Z" />
                        </svg>
                    </span>
                    <a :href="'{{ url('download-archive') }}?folder=' + encodeURIComponent(duplicateModal.match?.package_name ?? '') + '&format=.tar.gz'"
                        class="flex items-center gap-1.5 group">
                        <svg class="h-4 w-4 text-blue-600" width="15" height="15" viewBox="0 0 15 15" fill="currentColor"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z" />
                        </svg>
                        <span class="text-sm text-blue-600 font-medium group-hover:underline">Package (.tar.gz)</span>
                    </a>
                    <span class="text-slate-300">|</span>
                    <span>Size: <span class="font-medium" x-text="duplicateModal.match?.targz_size ?? '-'"></span></span>
                    <span class="text-slate-300">|</span>
                    <button
                        type="button"
                        x-data="{ copied: false }"
                        class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 transition-colors"
                        @click="
                            const txt = duplicateModal.match?.targz_sha256 ?? '';
                            if (navigator.clipboard) {
                                navigator.clipboard.writeText(txt);
                            } else {
                                const el = document.createElement('textarea');
                                el.value = txt;
                                el.style.position = 'fixed';
                                el.style.opacity = '0';
                                document.body.appendChild(el);
                                el.select();
                                document.execCommand('copy');
                                document.body.removeChild(el);
                            }
                            copied = true;
                            setTimeout(() => copied = false, 1500);
                        "
                        title="Copy TAR.GZ SHA256"
                    >
                        <span x-text="copied ? 'Copied!' : 'SHA256'"></span>
                        <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <svg x-show="copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end px-6 py-4 border-t border-slate-100 bg-slate-50/60">
            <button @click="duplicateModal.open = false"
                class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">
                Proceed Anyway
            </button>
        </div>

    </div>
</div>
