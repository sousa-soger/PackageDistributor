<div x-show="currentStep === 4" x-cloak class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-slate-800">Create Distribution Package</h2>
        <p class="mt-2 text-sm text-slate-500">
            The system will generate an update package and a rollback package based on the selected Git versions.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Target Environment</div>
            <span x-text="selectedEnvironment" class="mt-1 text-sm font-medium text-slate-800"></span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Project Name</div>
            <span x-text="selectedRepositoryLabel" class="mt-1 text-sm font-medium text-slate-800"></span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Base Version</div>
            <span x-text="selectedBaseLabel" class="mt-1 text-sm font-medium text-slate-800"></span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Head Version</div>
            <span x-text="selectedHeadLabel" class="mt-1 text-sm font-medium text-slate-800"></span>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-slate-700">Packaging Progress</span>
            <span class="text-sm font-medium text-slate-600" x-text="packagingProgress + '%'"></span>
        </div>

        <div class="h-3 overflow-hidden rounded-full bg-slate-200">
            <div class="h-full rounded-full bg-blue-500 transition-all duration-500"
                :style="`width: ${packagingProgress}%`"></div>
        </div>

        <p class="mt-3 text-sm text-slate-500" x-text="packagingMessage"></p>
    </div>

    <div x-show="packagingError" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <div class="font-semibold">Packaging failed</div>
        <div class="mt-1" x-text="packagingError"></div>
    </div>

    <div x-show="packagingResult" x-cloak class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
        <div class="text-sm font-semibold text-emerald-800">Package created successfully</div>

        <div class="mt-3 grid gap-3 md:grid-cols-2">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Folder</div>
                <div class="mt-1 break-all text-sm text-slate-700" x-text="packagingResult?.folder_name || '-'"></div>
            </div>

            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Package Root</div>
                <div class="mt-1 break-all text-sm text-slate-700" x-text="packagingResult?.package_root || '-'"></div>
            </div>

            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Update Zip</div>
                <div class="mt-1 break-all text-sm text-slate-700" x-text="packagingResult?.update_zip || '-'"></div>
            </div>

            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Rollback Zip</div>
                <div class="mt-1 break-all text-sm text-slate-700" x-text="packagingResult?.rollback_zip || '-'"></div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-between">
        <button type="button"
            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            @click="currentStep = 3" :disabled="isPackaging">
            Back
        </button>

        <div class="flex items-center gap-3">
            <button type="button"
                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                @click="runPackaging()" x-show="!packagingResult" :disabled="isPackaging">
                <span x-show="!isPackaging">Start Packaging</span>
                <span x-show="isPackaging">Packaging...</span>
            </button>

            <button type="button"
                class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                x-show="packagingResult" @click="currentStep = 5">
                Continue
            </button>
        </div>
    </div>
    {{--
    <p class="mt-4 text-xs text-slate-400">
        Navigation is locked while packaging is running.
    </p>
    --}}
</div>