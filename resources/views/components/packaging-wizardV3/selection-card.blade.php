<x-ui.card class="p-8 w-full">
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Package Selection</h2>
            <p class="text-sm text-slate-500 mt-1">
                Select repositories
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
            </div>
        </div>

    </div>
</x-ui.card>
