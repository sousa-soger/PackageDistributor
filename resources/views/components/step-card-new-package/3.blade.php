<x-ui.card class="p-8 w-full">
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Configure Packaging Options</h2>
            <p class="mt-1 text-sm text-slate-500">Customize how the package is prepared for the target environment.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Target Environment</label>
                <select x-model="selectedEnvironment"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/15">
                    <option value="" disabled>Select an environment...</option>
                    <option value="PROD">Production (PROD)</option>
                    <option value="QA">Quality Assurance (QA)</option>
                    <option value="DEV">Development (DEV)</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Patch Package Format</label>
                <select x-model="selectedFormat"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/15">
                    <option value="" disabled>Select a format...</option>
                    <option>.zip</option>
                    <option>.tar.gz</option>
                    <option>both</option>
                </select>
            </div>
        </div>

        <div>

            <div class="relative min-w-0 flex-1">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Package Name</label>

                <input x-model="packageName" type="text" :readonly="!customNaming"
                    :class="!customNaming ? 'bg-slate-100 cursor-not-allowed' : 'bg-white'"
                    class="w-full rounded-xl border border-slate-200 pl-4 py-2.5 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/15">
            </div>

            <button type="button" @click="customNaming = !customNaming" class="mb-2 mt-1 text-sm text-blue-500"
                x-text="customNaming ? 'Enable Formated Naming' : 'Enable Custom Naming'">
            </button>
        </div>

        {{--
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-800">Advanced Settings</div>
                <button
                    class="rounded-full border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-600">ON</button>
            </div>

            <div class="mt-3 space-y-2 text-sm text-slate-700">
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Include documentation files
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" checked
                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Optimize assets (CSS/JS)
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Generate changelog
                </label>
            </div>

            <div class="mt-3">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Custom build variables</label>
                <input type="text" placeholder="comma-separated, optional"
                    class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500" />
            </div>
        </div>
        --}}
    <div class="mt-6 flex items-center justify-between">
        <button type="button"
            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            @click="currentStep = 2">
            Back
        </button>

        <div class="flex items-center gap-3">
            <button type="button"
                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                @click="currentStep = 4" :disabled="!selectedEnvironment || !selectedFormat">
                Continue
            </button>
        </div>
    </div>
    </div>
</x-ui.card>