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
            </div>
        </div>

    </div>
</x-ui.card>
