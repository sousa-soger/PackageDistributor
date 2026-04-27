@extends('layouts.app')

@section('title', 'Packages')
@section('subtitle', 'Update and rollback bundles across all your projects.')

@section('topbar_actions')
    <a href="{{ route('create-package') }}">
        <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft hover:shadow-glow hover:brightness-[1.03] active:brightness-95 transition-base h-9 rounded-md px-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package-plus h-4 w-4">
            <path d="M16 16h6"></path>
            <path d="M19 13v6"></path>
            <path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"></path>
            <path d="m7.5 4.27 9 5.15"></path>
            <polyline points="3.29 7 12 12 20.71 7"></polyline>
            <line x1="12" x2="12" y1="22" y2="12"></line>
            </svg>
            New Package
        </button>
    </a>
@endsection

@section('content')

<div class="space-y-6"
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

    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="relative flex-1 max-w-md">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
            <input x-model="search" class="flex h-10 w-full rounded-md border border-border/30 px-3 py-2 text-base ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm pl-9 bg-card text-foreground" placeholder="Search packages…">
        </div>
    </div>
    
    {{-- Main Card --}}
    <div class="section-card p-0 overflow-hidden">

        {{-- Empty state --}}
        @if($packages->isEmpty())
            <div class="p-8 text-center text-sm" style="color: hsl(var(--muted-foreground));">
                No packages found. <a href="{{ route('create-package') }}" class="font-medium hover:underline" style="color: hsl(var(--ring));">Create your first package</a>.
            </div>

        @else
            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-secondary/50 text-xs uppercase tracking-wider text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 w-8 font-medium text-center">
                                <input type="checkbox"
                                    class="h-3.5 w-3.5 rounded border-slate-300 cursor-pointer"
                                    :checked="allSelected"
                                    @change="toggleAll()">
                            </th>
                            <th class="text-left font-medium px-5 py-3">Package</th>
                            <th class="text-left font-medium px-5 py-3">Project</th>
                            <th class="text-left font-medium px-5 py-3">Versions</th>
                            <th class="text-left font-medium px-5 py-3">Env</th>
                            <th class="text-left font-medium px-5 py-3">Size</th>
                            <th class="text-left font-medium px-5 py-3">Status</th>
                            <th class="text-left font-medium px-5 py-3">Created</th>
                            <th class="text-right font-medium px-5 py-3"></th>
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
                            class="divide-y divide-border/60">

                            {{-- Main row --}}
                            <tr @click="expanded = !expanded"
                                class="cursor-pointer transition-base hover:bg-secondary/40"
                                :class="selected.includes({{ $package->id }}) ? 'bg-secondary/80' : ''">

                                {{-- Checkbox --}}
                                <td class="px-4 py-3 w-8 text-center" @click.stop>
                                    <input type="checkbox"
                                        class="h-3.5 w-3.5 rounded border-slate-300 cursor-pointer"
                                        :checked="selected.includes({{ $package->id }})"
                                        @change="toggleOne({{ $package->id }})">
                                </td>

                                {{-- Package name & repo --}}
                                <td class="px-5 py-3">
                                    <div class="font-mono text-[11px] text-foreground/80 max-w-xs truncate" title="{{ $package->package_name }}">
                                        {{ $package->package_name }}
                                    </div>
                                    @if($package->repository_name ?? null)
                                        <div class="text-[11px] mt-0.5 text-muted-foreground">{{ $package->repository_name }}</div>
                                    @endif
                                </td>

                                {{-- Project --}}
                                <td class="px-5 py-3 font-medium whitespace-nowrap">
                                    {{ $package->project_name }}
                                </td>

                                {{-- Versions --}}
                                <td class="px-5 py-3">
                                    <div class="font-mono text-xs">
                                        {{ $package->base_version }} <span class="text-muted-foreground">→</span> {{ $package->head_version }}
                                    </div>
                                </td>

                                {{-- Environment badge --}}
                                <td class="px-5 py-3">
                                    @php
                                        $envColors = [
                                            'PROD' => 'bg-failed/10 text-failed border-failed/30',
                                            'QA'   => 'bg-queued/10 text-queued border-queued/30',
                                            'DEV'  => 'bg-running/10 text-running border-running/30',
                                        ];
                                        $envClass = $envColors[$package->environment] ?? 'bg-secondary text-foreground border-border/60';
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[10px] font-semibold tracking-wider {{ $envClass }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $package->environment }}
                                    </span>
                                </td>

                                {{-- Size --}}
                                <td class="px-5 py-3 text-xs tabular-nums whitespace-nowrap" style="color: hsl(var(--muted-foreground));">
                                    {{ $package->zip_size ? $package->zip_size : '—' }}
                                </td>

                                {{-- Status badge --}}
                                <td class="px-5 py-3">
                                @php
                                    $statusConfig = [
                                        'success'   => ['bg' => 'bg-success/12 text-success border-success/30',   'label' => 'Success'],
                                        'running'   => ['bg' => 'bg-running/12 text-running border-running/30',   'label' => 'Running', 'pulse' => true],
                                        'queued'    => ['bg' => 'bg-queued/12 text-queued border-queued/30',    'label' => 'Queued'],
                                        'failed'    => ['bg' => 'bg-failed/12 text-failed border-failed/30',    'label' => 'Failed'],
                                        'cancelled' => ['bg' => 'bg-inactive/12 text-inactive border-inactive/30', 'label' => 'Cancelled'],
                                    ];
                                    $s = $statusConfig[$package->status] ?? $statusConfig['queued'];
                                @endphp
                                <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-xs font-medium {{ $s['bg'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current {{ isset($s['pulse']) ? 'animate-pulse' : '' }}"></span>{{ $s['label'] }}
                                </span>
                                </td>

                                {{-- Created at --}}
                                <td class="px-5 py-3 text-[11px] whitespace-nowrap text-muted-foreground">
                                    {{ $package->created_at->diffForHumans() }}
                                </td>

                                {{-- Expand chevron --}}
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4 inline-block shrink-0 transition-transform duration-200 text-muted-foreground"
                                            :class="expanded ? '' : 'rotate-90'"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </td>
                            </tr>

                            {{-- Expanded detail row --}}
                            <tr x-show="expanded" x-cloak class="bg-secondary/30 border-t border-border/50">
                                <td colspan="9" class="px-6 py-5">
                                    <div class="flex flex-col space-y-5 max-w-4xl">

                                        {{-- Package meta --}}
                                        <div>
                                            <div class="text-sm">
                                                <span class="font-bold">Package:</span>
                                                <span class="font-mono font-semibold ml-1 text-[11px]">{{ $package->package_name }}</span>
                                            </div>
                                            <div class="text-xs mt-1 flex items-center gap-2 flex-wrap text-muted-foreground">
                                                <span class="font-medium">zip :</span>
                                                <span>Size: {{ $package->zip_size ?? 'N/A' }}</span>
                                                <span class="opacity-50">|</span>
                                                <span>SHA256: <span class="font-mono text-[10px]">{{ $package->zip_sha256 ?? 'N/A' }}</span></span>
                                            </div>
                                            <div class="text-xs mt-1 flex items-center gap-2 flex-wrap text-muted-foreground">
                                                <span class="font-medium">tar.gz :</span>
                                                <span>Size: {{ $package->targz_size ?? 'N/A' }}</span>
                                                <span class="opacity-50">|</span>
                                                <span>SHA256: <span class="font-mono text-[10px]">{{ $package->targz_sha256 ?? 'N/A' }}</span></span>
                                            </div>

                                            {{-- File change stats (if available) --}}
                                            @if($package->files_added || $package->files_modified || $package->files_deleted)
                                                <div class="flex items-center gap-3 mt-3">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[11px] font-medium bg-success/10 text-success border-success/20">
                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                                        {{ $package->files_added ?? 0 }} added
                                                    </span>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[11px] font-medium bg-running/10 text-running border-running/20">
                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                        {{ $package->files_modified ?? 0 }} modified
                                                    </span>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[11px] font-medium bg-failed/10 text-failed border-failed/20">
                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                                        {{ $package->files_deleted ?? 0 }} deleted
                                                    </span>
                                                    @if($package->has_rollback)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[11px] font-medium bg-secondary text-muted-foreground border-border">
                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                                            Rollback included
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Download actions --}}
                                        <div>
                                            <h4 class="text-sm font-bold mb-3 text-foreground">Download Package</h4>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <a href="{{ route('download.archive', ['folder' => $package->package_name, 'format' => '.zip']) }}"
                                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-running/30 text-sm font-medium transition-colors bg-card text-running hover:bg-running/5">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 15 15" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z"/>
                                                    </svg>
                                                    Package <span class="font-bold">(.zip)</span>
                                                </a>
                                                <a href="{{ route('download.archive', ['folder' => $package->package_name, 'format' => '.tar.gz']) }}"
                                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-running/30 text-sm font-medium transition-colors bg-card text-running hover:bg-running/5">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 15 15" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z"/>
                                                    </svg>
                                                    Package <span class="font-bold">(.tar.gz)</span>
                                                </a>
                                            </div>
                                        </div>



                                    </div>
                                </td>
                            </tr>

                        </tbody>
                    @endforeach
                </table>
            </div>

            {{-- Floating Bulk Action Bar --}}
            <div class="sticky bottom-4 z-40 flex justify-center pointer-events-none">
                <div x-show="selected.length > 0" x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2"
                    class="pointer-events-auto flex items-center gap-2 rounded-xl border border-border shadow-lg px-4 py-2.5 text-sm bg-card">

                    <span class="font-semibold pr-3 border-r border-border mr-1 text-foreground">
                        <span x-text="selected.length"></span> Selected
                    </span>

                    {{-- Bulk Download ZIP --}}
                    <button @click="bulkDownload('.zip')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-medium transition-colors text-running hover:bg-running/10">
                        <svg class="h-4 w-4" viewBox="0 0 15 15" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z"/>
                        </svg>
                        <span class="font-bold">.zip</span>
                    </button>

                    <span class="text-border">|</span>

                    {{-- Bulk Download TAR.GZ --}}
                    <button @click="bulkDownload('.tar.gz')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-medium transition-colors text-running hover:bg-running/10">
                        <svg class="h-4 w-4" viewBox="0 0 15 15" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z"/>
                        </svg>
                        <span class="font-bold">.tar.gz</span>
                    </button>

                    <span class="text-border">|</span>

                    {{-- Bulk Delete --}}
                    <button @click="bulkDelete()"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-medium transition-colors text-failed hover:bg-failed/10">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>

                    <span class="text-border">|</span>

                    {{-- Clear selection --}}
                    <button @click="clearSelection()"
                        class="flex items-center justify-center p-1.5 rounded-lg transition-colors text-muted-foreground hover:bg-secondary hover:text-foreground">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

        @endif
    </div>

</div>
@endsection