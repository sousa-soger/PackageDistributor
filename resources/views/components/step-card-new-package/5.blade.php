<x-ui.card class="p-8 w-full">
    <div>
        <h3 class="text-2xl font-bold text-slate-900">
            Package: <span x-text="packagingResult?.folder_name"></span>
        </h3>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
            <span>Size: <span x-text="packagingResult?.file_size"></span></span>
            <span class="text-slate-300">|</span>
            <span>SHA256 Checksum: <span x-text="packagingResult?.sha256"></span></span>
        </div>
    </div>

    <div class="pt-6 grid grid-cols-1 gap-10 lg:grid-cols-[1fr_auto_1fr] lg:items-start">
        <!-- Left -->
        <div class="space-y-8 center">
            <div class="space-y-2">
                <h3 class="text-xl font-semibold text-slate-900">
                    Download Package Locally
                </h3>
                <p class="text-sm text-slate-500">
                    Download the file directly to your computer.
                </p>
            </div>

            <!-- File Visual -->
            <div class="flex flex-col items-center justify-center gap-3 pt-2">
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl border border-slate-200 bg-amber-50 text-3xl shadow-sm">
                    📦
                </div>
                <span class="text-xs font-medium tracking-wide text-slate-400 uppercase"
                    x-text="selectedFormat ? selectedFormat.toUpperCase() + ' Package' : 'Package'"></span>
            </div>

            <!-- Download Button -->
            <div class="pt-2">
                <button type="button"
                    @click="
                        const base = '{{ route('download.archive') }}';
                        const folder = encodeURIComponent(packagingResult?.folder_name);
                        if (selectedFormat === 'both') {
                            window.location.href = base + '?folder=' + folder + '&format=.zip';
                            setTimeout(() => {
                                const iframe = document.createElement('iframe');
                                iframe.style.display = 'none';
                                iframe.src = base + '?folder=' + folder + '&format=.tar.gz';
                                document.body.appendChild(iframe);
                                setTimeout(() => iframe.remove(), 10000);
                            }, 1000);
                        } else {
                            window.location.href = base + '?folder=' + folder + '&format=' + encodeURIComponent(selectedFormat);
                        }
                    "
                    class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-blue-600 px-6 py-4 text-base font-medium text-white shadow-sm transition hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v1a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1" />
                    </svg>

                    <span>Download Package</span>

                    <span class="rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                        x-text="selectedFormat || 'Select format'"></span>
                </button>
            </div>
        </div>

        <!-- Divider with OR -->
        <div class="relative hidden h-full items-center lg:flex">
            <div class="h-full w-px bg-slate-200"></div>
            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-200 bg-white text-sm font-medium text-slate-500 shadow-sm">
                    OR
                </div>
            </div>
        </div>

        <!-- Right -->
        <div class="space-y-6">
            <div class="space-y-2">
                <h3 class="text-2xl font-bold text-slate-900">
                    Deploy to Own Hosting Server
                </h3>
                <p class="text-sm text-slate-500">
                    Deploy this package to your pre-configured hosting environment.
                </p>
            </div>

            <!-- Server Type + Check -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_auto] md:items-start">
                <div class="space-y-2">
                    <label for="server_type" class="block text-sm font-semibold text-slate-800">
                        Server Type
                    </label>

                    <select id="server_type" name="server_type"
                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        <option selected disabled>Select a server profile...</option>
                        <option>Production (Apache)</option>
                        <option>Staging (Nginx)</option>
                        <option>Develop (Node.js)</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-800">
                        Server Check
                    </label>

                    <div class="flex items-center gap-2 pt-1">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white shadow-sm">
                            <svg class="h-5 w-5 animate-spin text-blue-600" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5"
                                    class="opacity-20" />
                                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5"
                                    stroke-linecap="round" />
                            </svg>
                        </div>

                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="space-y-1 text-sm">
                <p class="text-slate-700">
                    Server Status:
                    <span class="font-medium text-emerald-600">Online</span>
                </p>
                <p class="text-slate-700">
                    Authentication:
                    <span class="font-medium text-emerald-600">Verified</span>
                </p>
            </div>

            <!-- Deployment Path -->
            <div class="space-y-2">
                <label for="deploy_path" class="block text-sm font-semibold text-slate-800">
                    Deployment Path <span class="font-normal text-slate-400">(optional)</span>
                </label>

                <input id="deploy_path" name="deploy_path" type="text" value="/var/www/html/cybix/current"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
            </div>

            <!-- Deploy Button -->
            <div>
                <button type="button"
                    class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-blue-600 px-6 py-4 text-base font-medium text-white shadow-sm transition hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h16M7 17V7h10v10M9 7V5h6v2" />
                    </svg>

                    <span>Deploy Now</span>

                    <span class="rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                        Ready
                    </span>
                </button>
            </div>
        </div>
    </div>


    <div class="space-y-6">
        <div class="flex items-center justify-end gap-3 pt-2">
            <x-ui.clear-button type="button" @click="currentStep = 4">
                Back
            </x-ui.clear-button>

            <x-ui.primary-button type="button" @click="if (selectedVersion) currentStep = 5">
                Finish
            </x-ui.primary-button>
        </div>
    </div>
</x-ui.card>