<x-ui.card class="p-8 w-full">
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Select Project Repository</h2>
            <p class="text-sm text-slate-500 mt-1">
                Choose the Git repository you want to create a distribution package from
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Repository</label>

            <select
                x-model="selectedRepository"
                class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500"
            >
                <option value="" disabled>Select a repository...</option>

                @foreach ($repositories as $repository)
                    <option value="{{ $repository['id'] }}">
                        {{ $repository['label'] }} ({{ $repository['owner'] }}/{{ $repository['repo'] }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <x-ui.primary-button
                type="button"
                @click="if (selectedRepository) { selectedVersionBase = ''; selectedVersionHead = ''; currentStep = 2 }"
            >
                Continue
            </x-ui.primary-button>
        </div>
    </div>
</x-ui.card>