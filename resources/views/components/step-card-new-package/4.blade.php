<div x-show="currentStep === 4" x-cloak class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

    {{-- Stop confirmation modal --}}
    <div x-show="confirmation" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-slate-800">Attention</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Are you sure you want to stop packaging?
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="confirmation = false">
                    Cancel
                </button>

                <button type="button"
                    class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                    @click="stopPackaging(); confirmation = false">
                    Stop Packaging
                </button>
            </div>
        </div>
    </div>

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

    {{-- ─── Progress Section ──────────────────────────────────────────── --}}
    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-4">

        {{-- Overall packaging progress --}}
        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <span class="text-sm font-semibold"
                    :class="packagingProgress === 100 ? 'text-green-600' : 'text-slate-700'">
                    Packaging Progress
                    <span x-show="packagingProgress === 100" class="text-green-600">✓</span>
                </span>
                <span class="text-sm font-medium text-slate-600" x-text="packagingProgress + '%'"></span>
            </div>
            <div class="h-3 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-blue-500 transition-all duration-500"
                    :style="`width: ${packagingProgress}%`"></div>
            </div>
        </div>

        <hr class="border-slate-200">

        {{-- Stage 1 – Download (10 %) --}}
        @include('components.step-card-new-package.partials.progress-bar', [
            'field'   => 'fileDownloadProgress',
            'label'   => 'Downloading base and head repository',
            'weight'  => '10%',
        ])

        {{-- Stage 2 & 3 – Extraction (20 % each) --}}
        <div :class="baseFileExtraction === 100 && headFileExtraction === 100 ? 'flex flex-col gap-1' : 'grid grid-cols-2 gap-3'">
            @include('components.step-card-new-package.partials.progress-bar', [
                'field'  => 'baseFileExtraction',
                'label'  => 'Base File Extraction',
                'weight' => '20%',
            ])
            @include('components.step-card-new-package.partials.progress-bar', [
                'field'  => 'headFileExtraction',
                'label'  => 'Head File Extraction',
                'weight' => '20%',
            ])
        </div>

        {{-- Stage 4 – Compare (10 %) --}}
        @include('components.step-card-new-package.partials.progress-bar', [
            'field'  => 'compareFilesProgress',
            'label'  => 'Comparing Files',
            'weight' => '10%',
        ])

        {{-- Stage 5 & 6 – Generate + Compress (20 % each) --}}
        <div :class="packageGenProgress === 100 && compressionProgress === 100 ? 'flex flex-col gap-1' : 'grid grid-cols-2 gap-3'" >
            @include('components.step-card-new-package.partials.progress-bar', [
                'field'  => 'packageGenProgress',
                'label'  => 'Generating Update and Rollback Packages',
                'weight' => '20%',
            ])
            @include('components.step-card-new-package.partials.progress-bar', [
                'field'  => 'compressionProgress',
                'label'  => 'Compressing Update and Rollback Packages',
                'weight' => '20%',
            ])
        </div>

    </div>

    <p class="mt-3 text-sm text-slate-500" x-text="packagingMessage"></p>

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
                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                x-show="!isPackaging && !packagingResult" @click="runPackaging()">
                Start Packaging
            </button>

            <button type="button" @mouseover="hovered = true" @mouseleave="hovered = false"
                class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800"
                x-show="isPackaging && !packagingResult" @click="confirmation = true">
                <span x-show="!hovered">Packaging...</span>
                <span x-show="hovered">Stop Packaging</span>
            </button>

            <button type="button"
                class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                x-show="packagingResult" @click="currentStep = 5">
                Continue
            </button>
        </div>
    </div>

</div>