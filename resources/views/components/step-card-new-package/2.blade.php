<x-ui.card class="p-8 w-full">
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Select Version</h2>
            <p class="text-sm text-slate-500 mt-1">
                Choose the branch, tag, or specific commit you want to package from the selected repository.
            </p>
        </div>

        <!-- Selected repository -->
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <span class="font-semibold">Repository:</span>
            <span x-text="selectedRepository || 'No repository selected'"></span>
        </div>

        <!-- Search + Filter -->
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
                    placeholder="Search versions..."
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
                    <option value="commit">Commits</option>
                </select>
            </div>
        </div>

        <!-- Version List -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <template
                x-for="version in allVersions.filter(v => {
                    const matchesRepository = v.app_name === selectedRepository;

                    const matchesType = versionTypeFilter === '' || v.commit_type === versionTypeFilter;

                    const keyword = versionSearch.toLowerCase();
                    const matchesSearch =
                        keyword === '' ||
                        (v.version_name && v.version_name.toLowerCase().includes(keyword)) ||
                        (v.update_type && v.update_type.toLowerCase().includes(keyword)) ||
                        (v.release_notes && v.release_notes.toLowerCase().includes(keyword)) ||
                        (v.commit_type && v.commit_type.toLowerCase().includes(keyword));

                    return matchesRepository && matchesType && matchesSearch;
                })"
                :key="version.id"
            >
                <label
                    class="flex cursor-pointer items-start justify-between gap-4 border-t border-slate-200 px-5 py-5 first:border-t-0 hover:bg-slate-50"
                    :class="selectedVersion == version.id ? 'border border-blue-500 bg-blue-50' : ''"
                >
                    <div class="flex items-start gap-4">
                        <input
                            type="radio"
                            name="selected_version"
                            class="mt-1 h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500"
                            :value="version.id"
                            x-model="selectedVersion"
                        >

                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-sm font-semibold text-slate-900" x-text="version.version_name"></h3>

                                <span
                                    x-show="version.is_active"
                                    class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700"
                                >
                                    ACTIVE
                                </span>

                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 uppercase"
                                      x-text="version.commit_type">
                                </span>
                            </div>

                            <p class="text-xs text-slate-700">
                                <span class="capitalize" x-text="version.update_type"></span>
                                <span> • </span>
                                <span x-text="version.release_notes"></span>
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 pt-1 text-xs text-slate-500"
                         x-text="new Date(version.created_at).toLocaleDateString()">
                    </div>
                </label>
            </template>

            <!-- Empty state -->
            <div
                x-show="allVersions.filter(v => {
                    const matchesRepository = v.app_name === selectedRepository;
                    const matchesType = versionTypeFilter === '' || v.commit_type === versionTypeFilter;

                    const keyword = versionSearch.toLowerCase();
                    const matchesSearch =
                        keyword === '' ||
                        (v.version_name && v.version_name.toLowerCase().includes(keyword)) ||
                        (v.update_type && v.update_type.toLowerCase().includes(keyword)) ||
                        (v.release_notes && v.release_notes.toLowerCase().includes(keyword)) ||
                        (v.commit_type && v.commit_type.toLowerCase().includes(keyword));

                    return matchesRepository && matchesType && matchesSearch;
                }).length === 0"
                class="px-5 py-8 text-sm text-slate-500"
            >
                No versions found for the selected repository.
            </div>
        </div>

        <!-- Footer buttons -->
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