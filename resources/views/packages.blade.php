@extends('layouts.app')

@section('title', 'Packages')
@section('subtitle', 'Update and rollback bundles across all your projects.')

@section('topbar_actions')
    <a href="{{ route('create-package') }}">
        <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft hover:shadow-glow hover:brightness-[1.03] active:brightness-95 transition-base h-9 rounded-md px-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-package-plus h-4 w-4">
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
    @php
        $initialsFor = static function (?string $value): string {
            $parts = preg_split('/\s+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            if ($parts === []) {
                return '?';
            }

            return collect($parts)
                ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
                ->take(2)
                ->implode('');
        };
    @endphp

    <div class="animate-fade-in space-y-3" x-data="packagesPage({
        repositories: @js($repositoryClientIndex),
        packages: @js($packageClientIndex),
        bulkDeleteUrl: @js(route('deployments.bulk-delete')),
        csrfToken: @js(csrf_token()),
        repositoryStateStorageKey: @js('packages.repository-state.'.auth()->id()),
    })">
        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center">
            <div class="relative max-w-md flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-search absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg>
                <input x-model="search"
                    class="flex h-10 w-full rounded-md border border-input bg-card px-3 py-2 pl-9 text-base text-foreground ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                    placeholder="Search packages, repos, creators...">
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-[200px]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-git-branch pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground">
                        <line x1="6" x2="6" y1="3" y2="15"></line>
                        <circle cx="18" cy="6" r="3"></circle>
                        <circle cx="6" cy="18" r="3"></circle>
                        <path d="M18 9a9 9 0 0 1-9 9"></path>
                    </svg>
                    <select x-model="repositoryFilter"
                        class="flex h-10 w-full appearance-none items-center justify-between rounded-md border border-input bg-card px-3 py-2 pl-9 pr-9 text-sm text-foreground ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <option value="all">All repositories</option>
                        @foreach ($repositoryFilters as $repository)
                            <option value="{{ $repository['key'] }}">{{ $repository['name'] }}</option>
                        @endforeach
                    </select>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-chevron-down pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 opacity-50"
                        aria-hidden="true">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                </div>

                <div class="relative w-full sm:w-[180px]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-users pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <select x-model="creatorFilter"
                        class="flex h-10 w-full appearance-none items-center justify-between rounded-md border border-input bg-card px-3 py-2 pl-9 pr-9 text-sm text-foreground ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <option value="all">All creators</option>
                        @foreach ($creatorFilters as $creator)
                            <option value="{{ $creator['id'] }}">{{ $creator['name'] }}</option>
                        @endforeach
                    </select>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-chevron-down pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 opacity-50"
                        aria-hidden="true">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                </div>
            </div>

            <button type="button" @click="allCollapsed() ? expandAll() : collapseAll()"
                class="ml-auto inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                <template x-if="allCollapsed()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevrons-up-down h-3.5 w-3.5">
                        <path d="m7 15 5 5 5-5"></path>
                        <path d="m7 9 5-5 5 5"></path>
                    </svg>
                </template>
                <template x-if="!allCollapsed()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevrons-down-up h-3.5 w-3.5">
                        <path d="m7 20 5-5 5 5"></path>
                        <path d="m7 4 5 5 5-5"></path>
                    </svg>
                </template>
                <span x-text="allCollapsed() ? 'Expand all' : 'Collapse all'"></span>
            </button>
        </div>

        @if ($packages->isEmpty())
            <div class="section-card p-8 text-center text-sm text-muted-foreground">
                No packages found.
                <a href="{{ route('create-package') }}" class="font-medium text-primary hover:underline">Create your first package</a>.
            </div>
        @else
            <div x-show="!hasVisiblePackages()" x-cloak class="section-card p-8 text-center text-sm text-muted-foreground">
                No packages match the current filters.
            </div>

            <div class="space-y-3">
                @foreach ($packageGroups as $group)
                    <div x-show="repositoryMatches(@js($group['key']))" x-cloak
                        :data-state="isRepositoryOpen(@js($group['key'])) ? 'open' : 'closed'"
                        class="section-card overflow-hidden p-0">
                        <button type="button" class="w-full" @click="toggleRepository(@js($group['key']))"
                            :aria-expanded="isRepositoryOpen(@js($group['key']))"
                            :data-state="isRepositoryOpen(@js($group['key'])) ? 'open' : 'closed'">
                            <div class="flex items-center justify-between gap-3 bg-secondary/40 px-5 py-3 transition-base hover:bg-secondary/60">
                                <div class="flex min-w-0 items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-chevron-down h-4 w-4 text-muted-foreground transition-transform"
                                        :class="isRepositoryOpen(@js($group['key'])) ? '' : '-rotate-90'">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                    <span x-html="repoIcon(@js($group['provider'] ?? ''))"></span>
                                    <div class="min-w-0 text-left">
                                        <div class="truncate font-mono text-sm">{{ $group['name'] }}</div>
                                        <div class="truncate text-[11px] text-muted-foreground">
                                            Owner &middot; {{ $group['ownerName'] }} &middot;
                                            {{ $group['contributorCount'] }}
                                            {{ $group['contributorCount'] === 1 ? 'contributor' : 'contributors' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <div class="hidden -space-x-2 sm:flex">
                                        @foreach ($group['contributors']->take(4) as $contributor)
                                            <span class="relative flex h-6 w-6 shrink-0 overflow-hidden rounded-full border-2 border-card"
                                                title="{{ $contributor['role'] ? $contributor['role'].' - '.$contributor['name'] : $contributor['name'] }}">
                                                @if ($contributor['avatar'])
                                                    <img src="{{ $contributor['avatar'] }}" alt="{{ $contributor['name'] }}"
                                                        class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center rounded-full bg-muted text-[10px]">
                                                        {{ $contributor['initials'] }}
                                                    </span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                    <div class="inline-flex items-center rounded-full border border-transparent bg-secondary px-2.5 py-0.5 text-xs font-semibold text-secondary-foreground transition-colors hover:bg-secondary/80 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        x-text="visiblePackageCount(@js($group['key']))">
                                        {{ $group['packages']->count() }}
                                    </div>
                                </div>
                            </div>
                        </button>

                        <div x-show="isRepositoryOpen(@js($group['key']))" x-cloak
                            :data-state="isRepositoryOpen(@js($group['key'])) ? 'open' : 'closed'">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="border-t border-border/60 bg-background text-xs uppercase tracking-wider text-muted-foreground">
                                        <tr>
                                            <th class="px-5 py-2.5 text-left font-medium">Package</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Versions</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Env</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Size</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Created by</th>
                                            {{-- <th class="px-5 py-2.5 text-left font-medium">Created</th> --}}
                                            <th class="px-5 py-2.5 text-left font-medium">Download</th>
                                            <th class="px-5 py-2.5 text-center font-medium">Actions</th>
                                        </tr>
                                    </thead>

                                    @foreach ($group['packages'] as $package)
                                        @php
                                            $creatorName = $package->creator?->name ?: ($package->creator?->email ?: 'Unknown user');
                                            $creatorInitials = $initialsFor($creatorName);
                                            $isPackageReady = $package->status === 'completed';
                                            $permissions = $packagePermissions[$package->id] ?? ['canDelete' => false, 'canDeploy' => false];
                                            $canDeletePackage = $permissions['canDelete'] ?? false;
                                            $canDeployPackage = $isPackageReady && ($permissions['canDeploy'] ?? false);
                                            $environmentClasses = [
                                                'PROD' => 'bg-failed/10 text-failed border-failed/30',
                                                'QA' => 'bg-queued/10 text-queued border-queued/30',
                                                'DEV' => 'bg-running/10 text-running border-running/30',
                                            ][$package->environment] ?? 'bg-secondary text-foreground border-border/60';
                                        @endphp

                                        <tbody x-show="packageMatches({{ $package->id }})" x-cloak
                                            x-data="{ confirmDelete: false }"
                                            class="border-t border-border/60 first:border-t-0">
                                            <tr x-show="!confirmDelete" x-cloak
                                                @click="togglePackage({{ $package->id }})"
                                                class="cursor-pointer transition-base hover:bg-secondary/40">
                                                <td class="px-5 py-3">
                                                    <div class=" max-w-xs truncate font-mono text-[14px] text-foreground/80"
                                                        title="{{ $package->package_name }}">
                                                        {{ $package->package_name }}
                                                    </div>
                                                    <div class="whitespace-nowrap text-[10px] text-muted-foreground"
                                                        title="{{ $package->created_at?->format('d M Y, h:i A') }}">
                                                        {{ $package->created_at?->diffForHumans() ?? '-' }}
                                                    </div>
                                                </td>

                                                <td class="px-5 py-3">
                                                    <div class="font-mono text-xs">
                                                        <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[10px] font-bold tracking-wider bg-failed/10 text-failed border-failed/30">{{ $package->base_version }}</span>
                                                        <span class="text-muted-foreground">&rarr;</span>
                                                        <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[10px] font-bold tracking-wider bg-success/10 text-success border-success/30">{{ $package->head_version }}</span>
                                                    </div>
                                                </td>

                                                <td class="px-5 py-3">
                                                    <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[10px] font-semibold tracking-wider {{ $environmentClasses }}">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                                        {{ $package->environment }}
                                                    </span>
                                                </td>

                                                <td class="whitespace-nowrap px-5 py-3 text-xs tabular-nums">
                                                    @if ($package->zip_size)
                                                        {{ $package->zip_size }}
                                                    @else
                                                        &mdash;
                                                    @endif
                                                </td>

                                                <td class="px-5 py-3">
                                                    <div class="flex max-w-[11rem] items-center gap-2">
                                                        <span class="relative flex h-6 w-6 shrink-0 overflow-hidden rounded-full">
                                                            @if ($package->creator?->avatar_url)
                                                                <img src="{{ $package->creator->avatar_url }}" alt="{{ $creatorName }}"
                                                                    class="h-full w-full object-cover">
                                                            @else
                                                                <span class="flex h-full w-full items-center justify-center rounded-full bg-muted text-[10px]">
                                                                    {{ $creatorInitials }}
                                                                </span>
                                                            @endif
                                                        </span>
                                                        <span class="truncate text-xs">{{ $creatorName }}</span>
                                                    </div>
                                                </td>
                                                <!--
                                                <td class="whitespace-nowrap px-5 py-3 text-[11px] text-muted-foreground"
                                                    title="{{ $package->created_at?->format('d M Y, h:i A') }}">
                                                    {{ $package->created_at?->diffForHumans() ?? '-' }}
                                                </td>
                                                -->
                                                <td class="px-5 py-3">
                                                    @if ($isPackageReady)
                                                        <a href="{{ route('download.archive', ['folder' => $package->package_name, 'format' => '.zip']) }}"
                                                            @click.stop
                                                            class="inline-flex h-8 items-center justify-center gap-1.5 rounded-md border border-running/30 bg-card px-2.5 text-xs font-semibold text-running transition-colors hover:bg-running/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                            title="Download ZIP">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                class="lucide lucide-download h-3.5 w-3.5">
                                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                                <polyline points="7 10 12 15 17 10"></polyline>
                                                                <line x1="12" x2="12" y1="15" y2="3"></line>
                                                            </svg>
                                                            zip
                                                        </a>
                                                    @else
                                                        <button type="button" disabled
                                                            class="inline-flex h-8 cursor-not-allowed items-center justify-center gap-1.5 rounded-md border border-border bg-card px-2.5 text-xs font-semibold text-muted-foreground opacity-60">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                class="lucide lucide-download h-3.5 w-3.5">
                                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                                <polyline points="7 10 12 15 17 10"></polyline>
                                                                <line x1="12" x2="12" y1="15" y2="3"></line>
                                                            </svg>
                                                            zip
                                                        </button>
                                                    @endif

                                                    @if ($isPackageReady)
                                                        <a href="{{ route('download.archive', ['folder' => $package->package_name, 'format' => '.tar.gz']) }}"
                                                            @click.stop
                                                            class="inline-flex h-8 items-center justify-center gap-1.5 rounded-md border border-running/30 bg-card px-2.5 text-xs font-semibold text-running transition-colors hover:bg-running/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                            title="Download tar.gz">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                class="lucide lucide-download h-3.5 w-3.5">
                                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                                <polyline points="7 10 12 15 17 10"></polyline>
                                                                <line x1="12" x2="12" y1="15" y2="3"></line>
                                                            </svg>
                                                            tar.gz
                                                        </a>
                                                    @else
                                                        <button type="button" disabled
                                                            class="inline-flex h-8 cursor-not-allowed items-center justify-center gap-1.5 rounded-md border border-border bg-card px-2.5 text-xs font-semibold text-muted-foreground opacity-60">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                class="lucide lucide-download h-3.5 w-3.5">
                                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                                <polyline points="7 10 12 15 17 10"></polyline>
                                                                <line x1="12" x2="12" y1="15" y2="3"></line>
                                                            </svg>
                                                            tar.gz
                                                        </button>
                                                    @endif

                                                </td>

                                                <td class="px-5 py-3">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <button type="button" @click.stop="deployPackage(@js($package->package_name))"
                                                            @disabled(! $canDeployPackage)
                                                            class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-border/60 px-3 text-sm font-medium text-foreground ring-offset-background transition-base hover:shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 {{ $canDeployPackage ? 'brand-soft-bg' : 'bg-secondary/60' }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                class="lucide lucide-rocket h-3.5 w-3.5">
                                                                <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path>
                                                                <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path>
                                                                <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path>
                                                                <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path>
                                                            </svg>
                                                            Deploy
                                                        </button>

                                                        @if ($canDeletePackage)
                                                            <button type="button"
                                                                @click.stop="confirmDelete = true; expandedPackageId = null"
                                                                class="inline-flex h-9 items-center justify-center rounded-md px-3 text-sm font-medium text-failed transition-colors hover:bg-failed/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                                aria-label="Delete package"
                                                                title="Delete package">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                    class="lucide lucide-trash-2 h-3.5 w-3.5">
                                                                    <path d="M3 6h18"></path>
                                                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                                                    <path d="M10 11v6"></path>
                                                                    <path d="M14 11v6"></path>
                                                                </svg>
                                                            </button>
                                                        @endif

                                                        <button type="button" @click.stop="togglePackage({{ $package->id }})"
                                                            class="inline-flex h-9 items-center justify-center rounded-md px-3 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                            aria-label="Toggle package details">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 shrink-0 transition-transform duration-200"
                                                                :class="isPackageExpanded({{ $package->id }}) ? '' : 'rotate-90'" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr x-show="confirmDelete" x-cloak class="border-t border-failed/20 bg-failed/5">
                                                <td colspan="8" class="px-5 py-3">
                                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                            <div class="text-sm text-foreground animate-fade-in">
                                                                Remove <span class="font-semibold">{{ $package->package_name }}</span> permanently?
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <button type="button" @click.stop="confirmDelete = false"
                                                                    class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground transition-base hover:bg-secondary">
                                                                    Cancel
                                                                </button>
                                                                <button type="button"
                                                                    @click.stop="deletePackage({{ $package->id }}, @js($package->package_name))"
                                                                    class="inline-flex items-center justify-center rounded-lg border border-failed/40 px-3 py-1.5 text-xs font-semibold text-failed transition-base hover:bg-failed/10">
                                                                    Confirm remove
                                                                </button>
                                                            </div>
                                                        </div>
                                                </td>
                                            </tr>

                                            <tr x-show="!confirmDelete && isPackageExpanded({{ $package->id }})" x-cloak class="border-t border-border/50 bg-secondary/30">
                                                <td colspan="8" class="px-6 py-5">
                                                    <div class="grid max-w-6xl gap-5 lg:grid-cols-[minmax(0,1fr)_auto]">
                                                        <div class="min-w-0">
                                                            <div class="text-sm">
                                                                <span class="text-lg font-bold">{{ $package->package_name }}</span>
                                                            </div>

                                                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                                <span class="font-semibold">zip:</span>
                                                                <span>Size: {{ $package->zip_size ?? 'N/A' }}</span>
                                                                <span class="opacity-50">|</span>
                                                                <span>
                                                                    <button type="button"
                                                                        @click.stop="copyHash(@js($package->zip_sha256 ?? ''), 'zip-{{ $package->id }}')"
                                                                        class="inline-flex flex-row items-center gap-1 rounded px-0.5 font-mono text-running/70 transition-colors hover:text-running/90"
                                                                        :title="isHashCopied('zip-{{ $package->id }}') ? 'Copied!' : 'Click to copy SHA256'"
                                                                    >
                                                                        SHA256
                                                                        <svg x-show="!isHashCopied('zip-{{ $package->id }}')" width="14" height="14" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0">
                                                                            <path d="M12.5 3A1.5 1.5 0 0 1 14 4.5V6h1.5A1.5 1.5 0 0 1 17 7.5v8a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 6 15.5V14H4.5A1.5 1.5 0 0 1 3 12.5v-8A1.5 1.5 0 0 1 4.5 3zm1.5 9.5a1.5 1.5 0 0 1-1.5 1.5H7v1.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-8a.5.5 0 0 0-.5-.5H14zM4.5 4a.5.5 0 0 0-.5.5v8a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-8a.5.5 0 0 0-.5-.5z" />
                                                                        </svg>
                                                                        <svg x-show="isHashCopied('zip-{{ $package->id }}')" x-cloak width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;color:var(--color-success)">
                                                                            <path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                        </svg>
                                                                    </button>
                                                                </span>
                                                            </div>

                                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                                <span class="font-semibold">tar.gz:</span>
                                                                <span>Size: {{ $package->targz_size ?? 'N/A' }}</span>
                                                                <span class="opacity-50">|</span>
                                                                <span>
                                                                    <button type="button"
                                                                        @click.stop="copyHash(@js($package->targz_sha256 ?? ''), 'targz-{{ $package->id }}')"
                                                                        class="inline-flex flex-row items-center gap-1 rounded px-0.5 font-mono text-running/70 transition-colors hover:text-running/90"
                                                                        :title="isHashCopied('targz-{{ $package->id }}') ? 'Copied!' : 'Click to copy SHA256'"
                                                                    >
                                                                        SHA256
                                                                        <svg x-show="!isHashCopied('targz-{{ $package->id }}')" width="14" height="14" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0">
                                                                            <path d="M12.5 3A1.5 1.5 0 0 1 14 4.5V6h1.5A1.5 1.5 0 0 1 17 7.5v8a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 6 15.5V14H4.5A1.5 1.5 0 0 1 3 12.5v-8A1.5 1.5 0 0 1 4.5 3zm1.5 9.5a1.5 1.5 0 0 1-1.5 1.5H7v1.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-8a.5.5 0 0 0-.5-.5H14zM4.5 4a.5.5 0 0 0-.5.5v8a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-8a.5.5 0 0 0-.5-.5z" />
                                                                        </svg>
                                                                        <svg x-show="isHashCopied('targz-{{ $package->id }}')" x-cloak width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;color:var(--color-success)">
                                                                            <path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                        </svg>
                                                                    </button>
                                                                </span>
                                                            </div>

                                                            @if ($package->files_added || $package->files_modified || $package->files_deleted)
                                                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                                                    <span class="inline-flex items-center gap-1 rounded border border-success/20 bg-success/10 px-2 py-0.5 text-[11px] font-medium text-success">
                                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor" stroke-width="2">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                d="M12 4v16m8-8H4"></path>
                                                                        </svg>
                                                                        {{ $package->files_added ?? 0 }} added
                                                                    </span>
                                                                    <span class="inline-flex items-center gap-1 rounded border border-running/20 bg-running/10 px-2 py-0.5 text-[11px] font-medium text-running">
                                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor" stroke-width="2">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                        </svg>
                                                                        {{ $package->files_modified ?? 0 }} modified
                                                                    </span>
                                                                    <span class="inline-flex items-center gap-1 rounded border border-failed/20 bg-failed/10 px-2 py-0.5 text-[11px] font-medium text-failed">
                                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor" stroke-width="2">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                d="M20 12H4"></path>
                                                                        </svg>
                                                                        {{ $package->files_deleted ?? 0 }} deleted
                                                                    </span>
                                                                    @if ($package->has_rollback)
                                                                        <span class="inline-flex items-center gap-1 rounded border border-border bg-secondary px-2 py-0.5 text-[11px] font-medium text-muted-foreground">
                                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                                                stroke="currentColor" stroke-width="2">
                                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                                            </svg>
                                                                            Rollback included
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <!--
                                                        <div class="min-w-[14rem]">
                                                            <h4 class="mb-3 text-sm font-bold text-foreground">Download Package</h4>
                                                            <div class="flex flex-wrap items-center gap-3 lg:flex-col lg:items-stretch">
                                                                @if ($isPackageReady)
                                                                    <a href="{{ route('download.archive', ['folder' => $package->package_name, 'format' => '.zip']) }}"
                                                                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-running/30 bg-card px-4 py-2 text-sm font-medium text-running transition-colors hover:bg-running/5">
                                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 15 15" fill="currentColor"
                                                                            xmlns="http://www.w3.org/2000/svg">
                                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                                d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z"></path>
                                                                        </svg>
                                                                        Package <span class="font-bold">(.zip)</span>
                                                                    </a>
                                                                    <a href="{{ route('download.archive', ['folder' => $package->package_name, 'format' => '.tar.gz']) }}"
                                                                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-running/30 bg-card px-4 py-2 text-sm font-medium text-running transition-colors hover:bg-running/5">
                                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 15 15" fill="currentColor"
                                                                            xmlns="http://www.w3.org/2000/svg">
                                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                                d="M7.50005 1.04999C7.74858 1.04999 7.95005 1.25146 7.95005 1.49999V8.41359L10.1819 6.18179C10.3576 6.00605 10.6425 6.00605 10.8182 6.18179C10.994 6.35753 10.994 6.64245 10.8182 6.81819L7.81825 9.81819C7.64251 9.99392 7.35759 9.99392 7.18185 9.81819L4.18185 6.81819C4.00611 6.64245 4.00611 6.35753 4.18185 6.18179C4.35759 6.00605 4.64251 6.00605 4.81825 6.18179L7.05005 8.41359V1.49999C7.05005 1.25146 7.25152 1.04999 7.50005 1.04999ZM2.5 10C2.77614 10 3 10.2239 3 10.5V12C3 12.5539 3.44565 13 3.99635 13H11.0012C11.5529 13 12 12.5528 12 12V10.5C12 10.2239 12.2239 10 12.5 10C12.7761 10 13 10.2239 13 10.5V12C13 13.1041 12.1062 14 11.0012 14H3.99635C2.89019 14 2 13.103 2 12V10.5C2 10.2239 2.22386 10 2.5 10Z"></path>
                                                                        </svg>
                                                                        Package <span class="font-bold">(.tar.gz)</span>
                                                                    </a>
                                                                @else
                                                                    <button type="button" disabled
                                                                        class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 py-2 text-sm font-medium text-muted-foreground opacity-60">
                                                                        Package <span class="font-bold">(.zip)</span>
                                                                    </button>
                                                                    <button type="button" disabled
                                                                        class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 py-2 text-sm font-medium text-muted-foreground opacity-60">
                                                                        Package <span class="font-bold">(.tar.gz)</span>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        -->
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        function packagesPage(config) {
            return {
                search: '',
                repositoryFilter: 'all',
                creatorFilter: 'all',
                expandedPackageId: null,
                copiedHashKey: null,
                copiedHashTimeout: null,
                openRepositories: {},
                repositories: config.repositories || {},
                packages: config.packages || {},
                bulkDeleteUrl: config.bulkDeleteUrl || '',
                csrfToken: config.csrfToken || '',
                repositoryStateStorageKey: config.repositoryStateStorageKey || 'packages.repository-state',

                init() {
                    const savedRepositoryState = this.loadRepositoryState();

                    this.openRepositories = Object.fromEntries(
                        Object.keys(this.repositories).map((key) => [key, savedRepositoryState[key] === true])
                    );

                    if (sessionStorage.getItem('flash_toast_msg')) {
                        const message = sessionStorage.getItem('flash_toast_msg');
                        const type = sessionStorage.getItem('flash_toast_type');
                        sessionStorage.removeItem('flash_toast_msg');
                        sessionStorage.removeItem('flash_toast_type');

                        setTimeout(() => {
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: { type, message },
                            }));
                        }, 50);
                    }
                },

                toggleRepository(repositoryKey) {
                    this.openRepositories[repositoryKey] = !this.isRepositoryOpen(repositoryKey);
                    this.saveRepositoryState();
                },

                collapseAll() {
                    Object.keys(this.openRepositories).forEach((key) => {
                        this.openRepositories[key] = false;
                    });
                    this.saveRepositoryState();
                },

                expandAll() {
                    Object.keys(this.openRepositories).forEach((key) => {
                        this.openRepositories[key] = true;
                    });
                    this.saveRepositoryState();
                },

                allCollapsed() {
                    return Object.keys(this.openRepositories).every((key) => !this.openRepositories[key]);
                },

                isRepositoryOpen(repositoryKey) {
                    return this.openRepositories[repositoryKey] === true;
                },

                togglePackage(packageId) {
                    this.expandedPackageId = this.isPackageExpanded(packageId) ? null : packageId;
                },

                isPackageExpanded(packageId) {
                    return this.expandedPackageId === packageId;
                },

                isHashCopied(hashKey) {
                    return this.copiedHashKey === hashKey;
                },

                async copyHash(text, hashKey) {
                    const wasCopied = await this.copyToClipboard(text);

                    if (!wasCopied) {
                        return;
                    }

                    this.copiedHashKey = hashKey;

                    if (this.copiedHashTimeout) {
                        clearTimeout(this.copiedHashTimeout);
                    }

                    this.copiedHashTimeout = setTimeout(() => {
                        if (this.copiedHashKey === hashKey) {
                            this.copiedHashKey = null;
                        }

                        this.copiedHashTimeout = null;
                    }, 2000);
                },

                get selectedRepositoryProvider() {
                    return this.selectedRepositoryOption?.provider || '';
                },

                get selectedRepositoryProviderLabel() {
                    return this.selectedRepositoryOption?.providerLabel || '';
                },
                
                repoIcon(provider) {
                    if (provider === 'github') {
                        return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-github h-3.5 w-3.5"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"></path><path d="M9 18c-4.51 2-5-2-7-2"></path></svg>`;
                    }
                    if (provider === 'gitlab') {
                        return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gitlab h-3.5 w-3.5"><path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"></path></svg>`;
                    }
                    if (provider === 'company-server') {
                        return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-server h-3.5 w-3.5"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect><rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect><line x1="6" x2="6.01" y1="6" y2="6"></line><line x1="6" x2="6.01" y1="18" y2="18"></line></svg>`;
                    }
                    return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hard-drive h-3.5 w-3.5"><line x1="22" x2="2" y1="12" y2="12"></line><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path><line x1="6" x2="6.01" y1="16" y2="16"></line><line x1="10" x2="10.01" y1="16" y2="16"></line></svg>`;
                },


                loadRepositoryState() {
                    try {
                        const storedState = localStorage.getItem(this.repositoryStateStorageKey);
                        const parsedState = storedState ? JSON.parse(storedState) : {};

                        if (!parsedState || typeof parsedState !== 'object' || Array.isArray(parsedState)) {
                            return {};
                        }

                        return parsedState;
                    } catch {
                        return {};
                    }
                },

                saveRepositoryState() {
                    try {
                        const repositoryState = Object.fromEntries(
                            Object.keys(this.repositories).map((key) => [key, this.isRepositoryOpen(key)])
                        );

                        localStorage.setItem(this.repositoryStateStorageKey, JSON.stringify(repositoryState));
                    } catch {
                    }
                },

                packageMatches(packageId) {
                    const item = this.packages[packageId];

                    if (!item) {
                        return false;
                    }

                    if (this.repositoryFilter !== 'all' && item.repositoryKey !== this.repositoryFilter) {
                        return false;
                    }

                    if (this.creatorFilter !== 'all' && item.creatorId !== this.creatorFilter) {
                        return false;
                    }

                    const query = this.search.trim().toLowerCase();

                    return !query || item.search.includes(query);
                },

                repositoryMatches(repositoryKey) {
                    const repository = this.repositories[repositoryKey];

                    return Boolean(repository?.packageIds?.some((packageId) => this.packageMatches(packageId)));
                },

                visiblePackageCount(repositoryKey) {
                    const repository = this.repositories[repositoryKey];

                    if (!repository?.packageIds) {
                        return 0;
                    }

                    return repository.packageIds.filter((packageId) => this.packageMatches(packageId)).length;
                },

                hasVisiblePackages() {
                    return Object.keys(this.repositories).some((repositoryKey) => this.repositoryMatches(repositoryKey));
                },

                deployPackage(packageName) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            type: 'info',
                            message: `Deployment flow is not connected for ${packageName} yet.`,
                        },
                    }));
                },

                async deletePackage(packageId, packageName) {
                    const response = await fetch(this.bulkDeleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({ ids: [packageId] }),
                    });

                    if (response.ok) {
                        sessionStorage.setItem('flash_toast_msg', 'Package deleted successfully.');
                        sessionStorage.setItem('flash_toast_type', 'success');
                        window.location.reload();

                        return;
                    }

                    let message = 'Delete failed. Please try again.';

                    try {
                        const payload = await response.json();
                        message = payload.message || message;
                    } catch (error) {
                    }

                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            type: 'error',
                            message,
                        },
                    }));
                },

                async copyToClipboard(text) {
                    if (!text || text === 'N/A') {
                        return false;
                    }

                    try {
                        await this.writeClipboardText(text);
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { type: 'success', message: 'SHA256 hash copied to clipboard.' },
                        }));

                        return true;
                    } catch {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { type: 'error', message: 'Failed to copy to clipboard.' },
                        }));

                        return false;
                    }
                },

                async writeClipboardText(text) {
                    if (navigator.clipboard?.writeText) {
                        try {
                            await navigator.clipboard.writeText(text);

                            return;
                        } catch {
                        }
                    }

                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.setAttribute('readonly', '');
                    textarea.style.position = 'fixed';
                    textarea.style.top = '-9999px';
                    textarea.style.left = '-9999px';

                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();
                    textarea.setSelectionRange(0, textarea.value.length);

                    const wasCopied = document.execCommand('copy');
                    textarea.remove();

                    if (!wasCopied) {
                        throw new Error('Clipboard copy failed.');
                    }
                },
            };
        }
    </script>
@endpush
