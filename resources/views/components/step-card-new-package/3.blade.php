{{--<x-ui.card class="p-8 w-full">
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Configure Packaging Options</h2>
            <p class="mt-1 text-sm text-slate-500">Customize how the package is prepared for the target environment.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Target Environment</label>
                <select class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500">
                    <option>Production</option>
                    <option>Staging</option>
                    <option>Development</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Configuration Profile</label>
                <select class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500">
                    <option>Standard deployment profile</option>
                    <option>Minified build</option>
                    <option>Debug build</option>
                </select>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-800">Advanced Settings</div>
                <button class="rounded-full border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-600">ON</button>
            </div>

            <div class="mt-3 space-y-2 text-sm text-slate-700">
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Include documentation files
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" checked class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Optimize assets (CSS/JS)
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Generate changelog
                </label>
            </div>

            <div class="mt-3">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Custom build variables</label>
                <input type="text" placeholder="comma-separated, optional" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <x-ui.clear-button type="button" @click="currentStep = 2">Back</x-ui.clear-button>
            <x-ui.primary-button type="button" @click="currentStep = 4">Continue</x-ui.primary-button>
        </div>
    </div>
</x-ui.card>
--}}