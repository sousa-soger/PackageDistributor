<x-ui.card class="p-8 w-full">
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Select Version</h2>
            <p class="text-sm text-slate-500 mt-1">
                Choose the branch, tag, or specific release you want to package from the selected repository.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <span class="font-semibold">Repository:</span>
            <span x-text="selectedRepository || 'No repository selected'"></span>
        </div>

        <div
            x-show="repoData"
            class="rounded-xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-700"
        >
            <div class="flex flex-col gap-1">
                <span class="font-semibold text-slate-900" x-text="repoData?.full_name"></span>
                <span class="text-slate-500" x-text="repoData?.description || 'No description available'"></span>
            </div>
        </div>

        <div class="flex flex-col gap-4 lg:flex-row">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                    </svg>
                </span>

                <input
                    x-model="versionSearch"
                    type="text"
                    placeholder="Search branches, tags, or releases..."
                    class="w-full rounded-2xl border border-slate-200 bg-white py-4 pl-12 pr-4 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                >
            </div>

            <div class="w-full lg:w-48">
                <select
                    x-model="versionTypeFilter"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                >
                    <option value="">All Types</option>
                    <option value="branch">Branches</option>
                    <option value="tag">Tags</option>
                    <option value="release">Releases</option>
                </select>
            </div>
        </div>

        <div
            x-show="isLoadingVersions"
            class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-sm text-slate-500"
        >
            Loading repository versions...
        </div>

        <div
            x-show="!isLoadingVersions"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
        >
            <template x-for="version in filteredVersions" :key="version.unique_key">
                <label
                    class="flex cursor-pointer items-start justify-between gap-4 border-t border-slate-200 px-5 py-5 first:border-t-0 hover:bg-slate-50"
                    :class="selectedVersion === version.unique_key ? 'border border-blue-500 bg-blue-50' : ''"
                >
                    <div class="flex items-start gap-4">
                        <input
                            type="radio"
                            name="selected_version"
                            class="mt-1 h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500"
                            :value="version.unique_key"
                            x-model="selectedVersion"
                        >

                        <div class="space-y-1">
                            <div class="flex items-center gap-3 flex-wrap">
                                <h3
                                    class="text-sm font-semibold text-slate-900"
                                    x-text="version.name">
                                </h3>

                                <span
                                    class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600"
                                    x-text="version.type">
                                </span>

                                <span
                                    x-show="version.type === 'branch' && version.name === repoData?.default_branch"
                                    class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700"
                                >
                                    DEFAULT
                                </span>

                                <span
                                    x-show="version.type === 'release' && version.is_prerelease"
                                    class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700"
                                >
                                    PRERELEASE
                                </span>

                                <span
                                    x-show="version.type === 'release' && version.is_draft"
                                    class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-700"
                                >
                                    DRAFT
                                </span>
                            </div>

                            <p class="text-xs text-slate-700" x-text="version.subtitle"></p>

                            <p
                                x-show="version.type === 'release' && version.asset_count !== null"
                                class="text-xs text-slate-500"
                            >
                                Assets:
                                <span x-text="version.asset_count"></span>
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 pt-1 text-xs text-slate-500" x-text="version.date || ''"></div>
                </label>
            </template>

            <div
                x-show="filteredVersions.length === 0"
                class="px-5 py-8 text-sm text-slate-500"
            >
                No branches, tags, or releases found for the selected repository.
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <x-ui.clear-button
                type="button"
                @click="currentStep = 1"
            >
                Back
            </x-ui.clear-button>

            <x-ui.primary-button
                type="button"
                @click="if (selectedVersion) currentStep = 3"
            >
                Continue
            </x-ui.primary-button>
        </div>
    </div>
</x-ui.card>