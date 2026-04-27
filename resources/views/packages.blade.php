@extends('layouts.app')

@section('content')
<div class="p-6 md:p-8 space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Packages</h1>
            <p class="text-sm text-slate-500 mt-1">Update and rollback bundles across all your projects.</p>
        </div>
        <a href="{{ route('create-package') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors whitespace-nowrap">
            {{-- Package Plus icon --}}
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 11v6M9 14h6" />
            </svg>
            New Package
        </a>
    </div>

    {{-- Main Card --}}
    <x-ui.card class="p-0 overflow-hidden"
        x-data="{
            search: '',
            selected: [],
            get allIds() { return {{ $packages->pluck('id')->toJson() }}; },
            get allSelected() { return this.selected.length === this.allIds.length && this.allIds.length > 0; },
            init() {
                if (sessionStorage.getItem('flash_toast_msg')) {
                    const msg  = sessionStorage.getItem('flash_toast_msg');
                    const type = sessionStorage.getItem('flash_toast_type');
                    sessionStorage.removeItem('flash_toast_msg');
                    sessionStorage.removeItem('flash_toast_type');
                    setTimeout(() => {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type, message: msg } }));
                    }, 50);
                }
            },
            toggleAll() {
                this.selected = this.allSelected ? [] : [...this.allIds];
            },
            toggleOne(id) {
                const idx = this.selected.indexOf(id);
                idx === -1 ? this.selected.push(id) : this.selected.splice(idx, 1);
            },
            clearSelection() { this.selected = []; },
            matches(pkg_name, project, env, base, target) {
                if (!this.search) return true;
                const q = this.search.toLowerCase();
                return pkg_name.toLowerCase().includes(q)
                    || project.toLowerCase().includes(q)
                    || env.toLowerCase().includes(q)
                    || base.toLowerCase().includes(q)
                    || target.toLowerCase().includes(q);
            },
            async bulkDownload(format) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('deployments.bulk-download') }}';
                const token = document.createElement('input');
                token.type = 'hidden'; token.name = '_token'; token.value = '{{ csrf_token() }}';
                const fmtInput = document.createElement('input');
                fmtInput.type = 'hidden'; fmtInput.name = 'format'; fmtInput.value = format;
                form.appendChild(token);
                form.appendChild(fmtInput);
                this.selected.forEach(id => {
                    const el = document.createElement('input');
                    el.type = 'hidden'; el.name = 'ids[]'; el.value = id;
                    form.appendChild(el);
                });
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            },
            async bulkDelete() {
                if (!confirm(this.selected.length + ' package(s) will be permanently deleted. Continue?')) return;
                const resp = await fetch('{{ route('deployments.bulk-delete') }}', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ ids: this.selected })
                });
                if (resp.ok) {
                    sessionStorage.setItem('flash_toast_msg', this.selected.length + ' Package(s) deleted successfully.');
                    sessionStorage.setItem('flash_toast_type', 'success');
                    window.location.reload();
                } else {
                    sessionStorage.setItem('flash_toast_msg', 'Delete failed. Please try again.');
                    sessionStorage.setItem('flash_toast_type', 'error');
                    window.location.reload();
                }
            }
        }">

        {{-- Search bar --}}
        <div class="px-5 py-4 border-b border-slate-200">
            <div class="relative w-full max-w-sm">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" x-model="search"
                    class="block w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 py-2 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500 transition-all outline-none"
                    placeholder="Search packages…">
            </div>
        </div>

        {{-- Empty state --}}
        @if($packages->isEmpty())
            <div class="p-8 text-center text-slate-500 text-sm">
                No packages found. <a href="{{ route('packages.create') }}" class="text-blue-600 hover:underline font-medium">Create your first package</a>.
            </div>

        @else
            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 table-auto">
                    <thead class="bg-slate-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 w-10">
                                <input type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                    :checked="allSelected"
                                    @change="toggleAll()">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Package</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Project</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Versions</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Env</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Size</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Created</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>

                    @foreach($packages as $package)
                        <tbody
                            x-data="{ expanded: false }"
                            x-show="matches(
                                '{{ addslashes($package->package_name) }}',
                                '{{ addslashes($package->project_name) }}',
                                '{{ $package->environment }}',
                                '{{ addslashes($package->base_version) }}',
                                '{{ addslashes($package->head_version) }}'
                            )"
                            class="divide-y divide-slate-100">

                            {{-- Main row --}}
                            <tr @click="expanded = !expanded"
                                class="cursor-pointer hover:bg-slate-50 transition-colors"
                                :class="selected.includes({{ $package->id }}) ? 'bg-blue-50/60' : ''">

                                {{-- Checkbox --}}
                                <td class="px-4 py-3 w-10" @click.stop>
                                    <input type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                        :checked="selected.includes({{ $package->id }})"
                                        @change="toggleOne({{ $package->id }})">
                                </td>

                                {{-- Package name & repo --}}
                                <td class="px-4 py-3 max-w-xs">
                                    <div class="font-mono text-xs text-slate-800 truncate" title="{{ $package->package_name }}">
                                        {{ $package->package_name }}
                                    </div>
                                    @if($package->repository_name ?? null)
                                        <div class="text-xs text-slate-400 mt-0.5">{{ $package->repository_name }}</div>
                                    @endif
                                </td>

                                {{-- Project --}}
                                <td class="px-4 py-3 text-sm font-semibold text-slate-800 whitespace-nowrap">
                                    {{ $package->project_name }}
                                </td>

                                {{-- Versions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded border border-rose-100 bg-rose-50 text-rose-700 font-medium text-xs whitespace-nowrap">
                                            {{ $package->base_version }}
                                        </span>
                                        <span class="text-slate-400">→</span>
                                        <span class="px-2 py-0.5 rounded border border-emerald-100 bg-emerald-50 text-emerald-700 font-medium text-xs whitespace-nowrap">
                                            {{ $package->head_version }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Environment badge --}}
                                <td class="px-4 py-3">
                                    @php
                                        $envColors = [
                                            'PROD' => 'bg-red-100 text-red-700 border border-red-200',
                                            'QA'   => 'bg-amber-100 text-amber-700 border border-amber-200',
                                            'DEV'  => 'bg-blue-100 text-blue-700 border border-blue-200',
                                        ];
                                        $envClass = $envColors[$package->environment] ?? 'bg-slate-100 text-slate-600 border border-slate-200';
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $envClass }}">
                                        {{ $package->environment }}
                                    </span>
                                </td>

                                {{-- Size --}}
                                <td class="px-4 py-3 text-xs text-slate-600 tabular-nums whitespace-nowrap">
                                    {{ $package->zip_size ? $package->zip_size : '—' }}
                                </td>

                                {{-- Status badge --}}
                                <td class="px-4 py-3">
                                    @php
                                        $statusConfig = [
                                            'success'   => ['dot' => 'bg-emerald-500', 'text' => 'text-emerald-700', 'bg' => 'bg-emerald-50 border-emerald-200', 'label' => 'Success'],
                                            'running'   => ['dot' => 'bg-blue-500 animate-pulse', 'text' => 'text-blue-700', 'bg' => 'bg-blue-50 border-blue-200', 'label' => 'Running'],
                                            'queued'    => ['dot' => 'bg-amber-400', 'text' => 'text-amber-700', 'bg' => 'bg-amber-50 border-amber-200', 'label' => 'Queued'],
                                            'failed'    => ['dot' => 'bg-red-500', 'text' => 'text-red-700', 'bg' => 'bg-red-50 border-red-200', 'label' => 'Failed'],
                                            'cancelled' => ['dot' => 'bg-slate-400', 'text' => 'text-slate-600', 'bg' => 'bg-slate-50 border-slate-200', 'label' => 'Cancelled'],
                                        ];
                                        $s = $statusConfig[$package->status] ?? $statusConfig['queued'];
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded border text-xs font-medium {{ $s['bg'] }} {{ $s['text'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span>
                                        {{ $s['label'] }}
                                    </span>
                                </td>

                                {{-- Created at --}}
                                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                    {{ $package->created_at->format('d M Y, h:i A') }}
                                </td>

                                {{-- Expand chevron --}}
                                <td class="px-4 py-3 text-right">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 inline-block shrink-0 text-slate-400 transition-transform duration-200"
                                        :class="expanded ? '' : 'rotate-90'"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </td>
                            </tr>

                            {{-- Expanded detail row --}}
                            <tr x-show="expanded" x-cloak class="bg-slate-50 border-t border-slate-100">
                                <td colspan="9" class="px-6 py-5">
                                    <div class="flex flex-col space-y-5 max-w-4xl">

                                        {{-- Package meta --}}
                                        <div>
                                            <div class="text-sm text-slate-800">
                                                <span class="font-bold">Package:</span>
                                                <span class="font-mono font-semibold ml-1">{{ $package->package_name }}</span>
                                            </div>
                                            <div class="text-xs text-slate-500 mt-1 flex items-center gap-2 flex-wrap">
                                                <span class="font-medium">zip :</span>
                                                <span>Size: {{ $package->zip_size ?? 'N/A' }}</span>
                                                <span class="text-slate-300">|</span>
                                                <span>SHA256: {{ $package->zip_sha256 ?? 'N/A' }}</span>
                                            </div>
                                            <div class="text-xs text-slate-500 mt-1 flex items-center gap-2 flex-wrap">
                                                <span class="font-medium">tar.gz :</span>
                                                <span>Size: {{ $package->targz_size ?? 'N/A' }}</span>
                                                <span class="text-slate-300">|</span>
                                                <span>SHA256: {{ $package->targz_sha256 ?? 'N/A' }}</span>
                                            </div>

                                            {{-- File change stats (if available) --}}
                                            @if($package->files_added || $package->files_modified || $package->files_deleted)
                                                <div class="flex items-center gap-3 mt-3">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border border-emerald-200 bg-emerald-50 text-emerald-700 text-xs font-medium">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                                        {{ $package->files_added ?? 0 }} added
                                                    </span>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border border-blue-200 bg-blue-50 text-blue-700 text-xs font-medium">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                        {{ $package->files_modified ?? 0 }} modified
                                                    </span>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border border-red-200 bg-red-50 text-red-700 text-xs font-medium">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                                        {{ $package->files_deleted ?? 0 }} deleted
                                                    </span>
                                                    @if($package->has_rollback)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border border-slate-200 bg-slate-50 text-slate-600 text-xs font-medium">
                                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                                            Rollback included
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Download actions --}}
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800 mb-3">Download Package</h4>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <a href="{{ route('download.archive', ['folder' => $package->package_name, 'format' => '.zip']) }}"
                                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-blue-200 bg-white text-blue-600 hover:bg-blue-50 text-sm font-medium transition-colors">
                                                    <svg class="h-4 w-4" viewBox="0 0 15 15" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z"/>
                                                    </svg>
                                                    Package <span class="font-bold">(.zip)</span>
                                                </a>
                                                <a href="{{ route('download.archive', ['folder' => $package->package_name, 'format' => '.tar.gz']) }}"
                                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-blue-200 bg-white text-blue-600 hover:bg-blue-50 text-sm font-medium transition-colors">
                                                    <svg class="h-4 w-4" viewBox="0 0 15 15" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z"/>
                                                    </svg>
                                                    Package <span class="font-bold">(.tar.gz)</span>
                                                </a>
                                            </div>
                                        </div>

                                        {{-- Deploy section --}}
                                        @if($package->status === 'success')
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800 mb-3">Deploy to Hosting Server</h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Server Type</label>
                                                    <select class="block w-full rounded-md border border-slate-300 py-2 px-3 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 bg-white">
                                                        <option value="">Select a server profile...</option>
                                                        @foreach($servers ?? [] as $server)
                                                            <option value="{{ $server->id }}">{{ $server->name }} ({{ $server->environment }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Deployment Path</label>
                                                    <input type="text"
                                                        class="block w-full rounded-md border border-slate-300 py-2 px-3 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 bg-white"
                                                        placeholder="/var/www/html">
                                                </div>
                                            </div>
                                            <div class="mt-4 flex justify-center">
                                                <button type="button"
                                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h16M7 17V7h10v10M9 7V5h6v2"/>
                                                    </svg>
                                                    Deploy Now
                                                    <span class="rounded-md bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">Ready</span>
                                                </button>
                                            </div>
                                        </div>
                                        @else
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                            Deployment is only available for packages with a <span class="font-semibold text-slate-700">Success</span> status.
                                        </div>
                                        @endif

                                    </div>
                                </td>
                            </tr>

                        </tbody>
                    @endforeach
                </table>
            </div>

            {{-- 
            {{-- Pagination 
            @if($packages->hasPages())
                <div class="px-5 py-4 border-t border-slate-200">
                    {{ $packages->links() }}
                </div>
            @endif            
            --}}

            {{-- Floating Bulk Action Bar --}}
            <div class="sticky bottom-4 z-40 flex justify-center pointer-events-none">
                <div x-show="selected.length > 0" x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2"
                    class="pointer-events-auto flex items-center gap-1 rounded-xl border border-slate-200 bg-white shadow-xl px-3 py-2 text-sm">

                    <span class="font-semibold text-slate-700 pr-2 border-r border-slate-200 mr-1">
                        <span x-text="selected.length"></span> Selected
                    </span>

                    {{-- Bulk Download ZIP --}}
                    <button @click="bulkDownload('.zip')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-blue-600 hover:bg-slate-100 transition font-medium">
                        <svg class="h-4 w-4" viewBox="0 0 15 15" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z"/>
                        </svg>
                        Download <span class="font-bold">.zip</span>
                    </button>

                    <span class="text-slate-200">|</span>

                    {{-- Bulk Download TAR.GZ --}}
                    <button @click="bulkDownload('.tar.gz')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-blue-600 hover:bg-slate-100 transition font-medium">
                        <svg class="h-4 w-4" viewBox="0 0 15 15" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z"/>
                        </svg>
                        Download <span class="font-bold">.tar.gz</span>
                    </button>

                    <span class="text-slate-200">|</span>

                    {{-- Bulk Delete --}}
                    <button @click="bulkDelete()"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-red-600 hover:bg-red-50 transition font-medium">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>

                    <span class="text-slate-200">|</span>

                    {{-- Clear selection --}}
                    <button @click="clearSelection()"
                        class="flex items-center justify-center h-7 w-7 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

        @endif
    </x-ui.card>

</div>
@endsection