<x-ui.card class="p-8 w-full">
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Select Comparison Versions</h2>
            <p class="text-sm text-slate-500 mt-1">
                Choose the base and head versions to build a patch package from this repository.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <span class="font-semibold">Repository:</span>
            <span x-text="selectedRepository || 'No repository selected'"></span>
        </div>

        <div x-show="repoData" class="rounded-xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-700">
            <div class="flex flex-col gap-1">
                <span class="font-semibold text-slate-900" x-text="repoData?.full_name"></span>
                <span class="text-slate-500" x-text="repoData?.description || 'No description available'"></span>
            </div>
        </div>

        <div x-show="isLoadingVersions"
            class="rounded-xl border border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-500">
            Loading repository versions...
        </div>

        <div x-show="!isLoadingVersions" x-cloak class="space-y-4">
            {{-- Search row: FROM | arrow | TO --}}
            <div class="grid grid-cols-1 items-end gap-3 lg:grid-cols-[1fr_auto_1fr]">
                <div class="min-w-0 space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">From (Base)</p>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative min-w-0 flex-1">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                </svg>
                            </span>
                            <input x-model="versionSearchBase" type="text"
                                placeholder="Search branches, tags, or releases..."
                                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/15">
                        </div>
                        <div class="w-full shrink-0 sm:w-40">
                            <select x-model="versionTypeFilterBase"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/15">
                                <option value="">All Types</option>
                                <option value="branch">Branches</option>
                                <option value="tag">Tags</option>
                                <option value="release">Releases</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center pb-2 lg:pb-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14" />
                        <path d="M13 5l7 7-7 7" />
                    </svg>
                </div>

                <div class="min-w-0 space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">To (Head)</p>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative min-w-0 flex-1">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                </svg>
                            </span>
                            <input x-model="versionSearchHead" type="text"
                                placeholder="Search branches, tags, or releases..."
                                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/15">
                        </div>
                        <div class="w-full shrink-0 sm:w-40">
                            <select x-model="versionTypeFilterHead"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/15">
                                <option value="">All Types</option>
                                <option value="branch">Branches</option>
                                <option value="tag">Tags</option>
                                <option value="release">Releases</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lists row: list | icons | list --}}
            <div class="grid grid-cols-1 items-stretch gap-3 lg:grid-cols-[1fr_auto_1fr]">
                <div class="min-w-0 space-y-2">
                    <p class="text-xs text-blue-600" x-cloak :class="selectedVersionBase ? 'visible' : 'invisible'" {{--
                        so that list doesn't move down when selected --}}>
                        Selected:
                        <span class="font-semibold" x-text="selectedBaseLabel"></span>
                        <span class="text-slate-500">(Base)</span>
                    </p>
                    <div
                        class="max-h-[min(420px,55vh)] overflow-y-auto overflow-x-hidden rounded-xl border border-slate-200 bg-white">
                        <template x-for="version in filteredVersionsBase" :key="'base-' + version.unique_key">
                            <label
                                class="flex cursor-pointer items-start justify-between gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0 hover:bg-slate-50"
                                :class="selectedVersionBase === version.unique_key ? 'border border-blue-500 bg-blue-50' : ''">
                                <div class="flex min-w-0 items-start gap-3">
                                    <input type="radio" name="selected_version_base"
                                        class="mt-0.5 h-4 w-4 shrink-0 border-slate-300 text-blue-600 focus:ring-blue-500"
                                        :value="version.unique_key" x-model="selectedVersionBase">
                                    <div class="min-w-0 space-y-0.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-semibold text-slate-900" x-text="version.name"></h3>
                                            <span
                                                class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600"
                                                x-text="version.type"></span>
                                            <span
                                                x-show="version.type === 'branch' && version.name === repoData?.default_branch"
                                                class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">DEFAULT</span>
                                            <span x-show="version.type === 'release'"
                                                class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-blue-700">Release</span>
                                            <span x-show="version.type === 'release' && version.is_prerelease"
                                                class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">PRERELEASE</span>
                                            <span x-show="version.type === 'release' && version.is_draft"
                                                class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-700">DRAFT</span>
                                        </div>
                                        <p class="text-xs text-slate-600" x-text="version.subtitle"></p>
                                        <p x-show="version.type === 'release' && version.asset_count !== null"
                                            class="text-xs text-slate-500">
                                            Assets: <span x-text="version.asset_count"></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 pt-0.5 text-xs text-slate-500" x-text="version.date || ''"></div>
                            </label>
                        </template>
                        <div x-show="filteredVersionsBase.length === 0" class="px-4 py-8 text-sm text-slate-500">
                            No branches, tags, or releases match your filters.
                        </div>
                    </div>
                </div>

                <div class="hidden flex-col items-center justify-center gap-3 self-stretch border-x border-slate-100 px-1 py-4 text-slate-400 lg:flex"
                    aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <line x1="6" y1="3" x2="6" y2="15" />
                        <circle cx="18" cy="6" r="3" />
                        <circle cx="6" cy="18" r="3" />
                        <path d="M18 9a9 9 0 0 1-9 9" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <div class="min-w-0 space-y-2">
                    <p class="text-xs text-blue-600" x-cloak :class="selectedVersionHead ? 'visible' : 'invisible'">
                        Selected:
                        <span class="font-semibold" x-text="selectedHeadLabel"></span>
                        <span class="text-slate-500">(Head)</span>
                    </p>
                    <div
                        class="max-h-[min(420px,55vh)] overflow-y-auto overflow-x-hidden rounded-xl border border-slate-200 bg-white">
                        <template x-for="version in filteredVersionsHead" :key="'head-' + version.unique_key">
                            <label
                                class="flex cursor-pointer items-start justify-between gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0 hover:bg-slate-50"
                                :class="selectedVersionHead === version.unique_key ? 'border border-blue-500 bg-blue-50' : ''">
                                <div class="flex min-w-0 items-start gap-3">
                                    <input type="radio" name="selected_version_head"
                                        class="mt-0.5 h-4 w-4 shrink-0 border-slate-300 text-blue-600 focus:ring-blue-500"
                                        :value="version.unique_key" x-model="selectedVersionHead">
                                    <div class="min-w-0 space-y-0.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-semibold text-slate-900" x-text="version.name"></h3>
                                            <span
                                                class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600"
                                                x-text="version.type"></span>
                                            <span
                                                x-show="version.type === 'branch' && version.name === repoData?.default_branch"
                                                class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">DEFAULT</span>
                                            <span x-show="version.type === 'release'"
                                                class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-blue-700">Release</span>
                                            <span x-show="version.type === 'release' && version.is_prerelease"
                                                class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">PRERELEASE</span>
                                            <span x-show="version.type === 'release' && version.is_draft"
                                                class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-700">DRAFT</span>
                                        </div>
                                        <p class="text-xs text-slate-600" x-text="version.subtitle"></p>
                                        <p x-show="version.type === 'release' && version.asset_count !== null"
                                            class="text-xs text-slate-500">
                                            Assets: <span x-text="version.asset_count"></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 pt-0.5 text-xs text-slate-500" x-text="version.date || ''"></div>
                            </label>
                        </template>
                        <div x-show="filteredVersionsHead.length === 0" class="px-4 py-8 text-sm text-slate-500">
                            No branches, tags, or releases match your filters.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col items-stretch gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
            <x-ui.clear-button type="button" class="order-2 sm:order-1" @click="currentStep = 1">
                Back
            </x-ui.clear-button>

            <div class="order-1 flex justify-center sm:order-2 sm:flex-1">
                <x-ui.primary-button type="button" class="min-w-56" @click="if (comparisonReady) currentStep = 3">
                    Create Patch Package
                </x-ui.primary-button>
            </div>
        </div>
    </div>
</x-ui.card>