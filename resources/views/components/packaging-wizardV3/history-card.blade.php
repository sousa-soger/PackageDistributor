<div>
    <x-ui.card class="p-8 w-full">
        <div class="space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Previously Generated Packages</h2>
                <p class="text-sm text-slate-500 mt-1">
                    View and download packages that have been generated previously.
                </p>
            </div>

            @if($packages->isEmpty())
                <div class="rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
                    No completed packages found.
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Env</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Project</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Version</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Created</th>
                                <th class="px-4 py-3"></th>
                                <th class="px-4 py-3"></th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        @foreach($packages as $package)
                            <tbody x-data="{ expanded: false }" class="divide-y divide-slate-100">
                                <tr @click="expanded = !expanded"
                                    class="cursor-pointer hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-slate-800">{{ $package->environment }}</td>
                                    <td class="px-4 py-3 text-sm font-bold text-slate-800">{{ $package->project_name }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-800">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="px-2 py-0.5 rounded border border-rose-100 bg-rose-50 text-rose-700 font-medium text-sm whitespace-nowrap">{{ $package->base_version }}</span>
                                            <span class="text-slate-700 text-lg">→</span>
                                            <span
                                                class="px-2 py-0.5 rounded border border-emerald-100 bg-emerald-50 text-emerald-700 font-medium text-sm whitespace-nowrap">{{ $package->head_version }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">
                                        {{ $package->created_at->format('d M Y, h:i A') }}
                                    </td>
                                    <td class="px-4 py-3" @click.stop>
                                        <div class="flex items-center gap-2">
                                            <svg aria-hidden="true" height="16" viewBox="0 0 16 16" version="1.1" width="16"
                                                class="octicon octicon-file-zip color-fg-muted shrink-0">
                                                <path
                                                    d="M3.5 1.75v11.5c0 .09.048.173.126.217a.75.75 0 0 1-.752 1.298A1.748 1.748 0 0 1 2 13.25V1.75C2 .784 2.784 0 3.75 0h5.586c.464 0 .909.185 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v8.586A1.75 1.75 0 0 1 12.25 15h-.5a.75.75 0 0 1 0-1.5h.5a.25.25 0 0 0 .25-.25V4.664a.25.25 0 0 0-.073-.177L9.513 1.573a.25.25 0 0 0-.177-.073H7.25a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5h-3a.25.25 0 0 0-.25.25Zm3.75 8.75h.5c.966 0 1.75.784 1.75 1.75v3a.75.75 0 0 1-.75.75h-2.5a.75.75 0 0 1-.75-.75v-3c0-.966.784-1.75 1.75-1.75ZM6 5.25a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 6 5.25Zm.75 2.25h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 6.75A.75.75 0 0 1 8.75 6h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 6.75ZM8.75 3h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 9.75A.75.75 0 0 1 8.75 9h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 9.75Zm-1 2.5v2.25h1v-2.25a.25.25 0 0 0-.25-.25h-.5a.25.25 0 0 0-.25.25Z">
                                                </path>
                                            </svg>
                                            <a href="{{ route('download.archive', ['folder' => $package->package_name, 'format' => '.zip']) }}"
                                                class="no-underline group">
                                                <span class="text-sm text-blue-600 font-medium group-hover:underline">Update
                                                    package</span>
                                                <span
                                                    class="text-sm text-blue-600 font-medium group-hover:underline">(.zip)</span>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3" @click.stop>
                                        <div class="flex items-center gap-2">
                                            <svg aria-hidden="true" height="16" viewBox="0 0 16 16" version="1.1" width="16"
                                                class="octicon octicon-file-zip color-fg-muted shrink-0">
                                                <path
                                                    d="M3.5 1.75v11.5c0 .09.048.173.126.217a.75.75 0 0 1-.752 1.298A1.748 1.748 0 0 1 2 13.25V1.75C2 .784 2.784 0 3.75 0h5.586c.464 0 .909.185 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v8.586A1.75 1.75 0 0 1 12.25 15h-.5a.75.75 0 0 1 0-1.5h.5a.25.25 0 0 0 .25-.25V4.664a.25.25 0 0 0-.073-.177L9.513 1.573a.25.25 0 0 0-.177-.073H7.25a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5h-3a.25.25 0 0 0-.25.25Zm3.75 8.75h.5c.966 0 1.75.784 1.75 1.75v3a.75.75 0 0 1-.75.75h-2.5a.75.75 0 0 1-.75-.75v-3c0-.966.784-1.75 1.75-1.75ZM6 5.25a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 6 5.25Zm.75 2.25h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 6.75A.75.75 0 0 1 8.75 6h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 6.75ZM8.75 3h.5a.75.75 0 0 1 0 1.5h-.5a.75.75 0 0 1 0-1.5ZM8 9.75A.75.75 0 0 1 8.75 9h.5a.75.75 0 0 1 0 1.5h-.5A.75.75 0 0 1 8 9.75Zm-1 2.5v2.25h1v-2.25a.25.25 0 0 0-.25-.25h-.5a.25.25 0 0 0-.25.25Z">
                                                </path>
                                            </svg>
                                            <a href="{{ route('download.archive', ['folder' => $package->package_name, 'format' => '.tar.gz']) }}"
                                                class="no-underline group">
                                                <span class="text-sm text-blue-600 font-medium group-hover:underline">Update
                                                    package</span>
                                                <span
                                                    class="text-sm text-blue-600 font-medium group-hover:underline">(.tar.gz)</span>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 inline-block shrink-0 text-slate-400 transition-transform duration-200"
                                            :class="expanded ? '' : 'rotate-90'" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr x-show="expanded" x-cloak class="bg-slate-50 border-t border-slate-100">
                                    <td colspan="7" class="px-6 py-5">
                                        <div class="flex flex-col space-y-5 max-w-4xl">
                                            <!-- Package Name & Meta -->
                                            <div>
                                                <div class="text-base text-slate-800">
                                                    <span class="font-bold">Package:</span> <span
                                                        class="font-bold">{{ $package->package_name }}</span>
                                                </div>
                                                <div class="text-xs text-slate-500 mt-1 flex items-center space-x-2">
                                                    <span>zip :</span>
                                                    <span>Size: {{ $package->zip_size ?? 'N/A' }}</span>
                                                    <span class="text-slate-300">|</span>
                                                    <span>SHA256:
                                                        {{ $package->zip_sha256 ?? 'N/A' }}</span>
                                                </div>
                                                <div class="text-xs text-slate-500 mt-1 flex items-center space-x-2">
                                                    <span>tar.gz :</span>
                                                    <span>Size: {{ $package->targz_size ?? 'N/A' }}</span>
                                                    <span class="text-slate-300">|</span>
                                                    <span>SHA256:
                                                        {{ $package->targz_sha256 ?? 'N/A' }}</span>
                                                </div>
                                            </div>

                                            <!-- Deploy to Hosting Server -->
                                            <div>
                                                <h4 class="text-base font-bold text-slate-800 mb-3">Deploy to Hosting
                                                    Server</h4>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <!-- Server Type -->
                                                    <div>
                                                        <label
                                                            class="block text-sm font-semibold text-slate-700 mb-2">Server
                                                            Type</label>
                                                        <select
                                                            class="block w-full rounded-md border border-slate-300 py-2 px-3 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 bg-white">
                                                            <option value="">Select a server profile...</option>
                                                        </select>
                                                    </div>
                                                    <!-- Deployment Path -->
                                                    <div>
                                                        <label
                                                            class="block text-sm font-semibold text-slate-700 mb-2">Deployment
                                                            Path</label>
                                                        <input type="text"
                                                            class="block w-full rounded-md border border-slate-300 py-2 px-3 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 bg-white"
                                                            value="">
                                                    </div>
                                                </div>

                                                <!-- Deploy Button -->
                                                <div class="mt-5 flex justify-center">
                                                    <button type="button"
                                                        class="inline-flex items-center justify-center gap-3 rounded-2xl bg-blue-600 px-6 py-4 text-base font-medium text-white shadow-sm transition hover:bg-blue-700">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M4 17h16M7 17V7h10v10M9 7V5h6v2" />
                                                        </svg>
                                                        <span>Deploy Now</span>
                                                        <span
                                                            class="rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">Ready</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>
    </x-ui.card>
</div>
