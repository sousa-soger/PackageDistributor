<x-ui.card class="p-8 w-full">
    <div class="space-y-6">

        <!-- Title -->
        <div>
            <h2 class="text-xl font-semibold text-slate-900">
                Create Distribution Package
            </h2>
            <p class="text-sm text-slate-500 mt-1">
                Package is being created. Please wait.
            </p>
        </div>

        <!-- Progress Bar -->
        <div class="space-y-3">
            <div class="flex items-center justify-between text-sm font-medium text-slate-600">
                <span id="progressLabel">Packaging...</span>
                <span id="progressPercent">0%</span>
            </div>

            <div class="w-full h-3 bg-slate-200 rounded-full overflow-hidden">
                <div id="progressBar" class="h-full bg-blue-600 transition-all duration-500" style="width: 0%">
                </div>
            </div>
        </div>

        <!-- Logs -->
        <div id="logBox" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 space-y-1">
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <x-ui.clear-button type="button" @click="currentStep = 3">
                Back
            </x-ui.clear-button>

            <x-ui.primary-button type="button" @click="if (selectedVersion) currentStep = 5">
                Continue
            </x-ui.primary-button>
        </div>

    </div>
</x-ui.card>

<!-- Footer -->
<div class="flex items-center justify-between pt-4">
    <p class="text-xs text-slate-400">
        Navigation is locked while packaging. Please wait for completion.
    </p>

    <div class="flex gap-2">
        <button disabled class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-400">
            Back
        </button>

        <button id="nextBtn" disabled class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white opacity-50">
            In Progress...
        </button>
    </div>
</div>