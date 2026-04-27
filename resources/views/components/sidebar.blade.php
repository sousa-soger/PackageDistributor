<aside class="hidden lg:flex h-screen w-[260px] shrink-0 flex-col sticky top-0 z-40"
    style="background-color: hsl(var(--sidebar-bg)); border-right: 1px solid hsl(var(--sidebar-border));">

    {{-- Logo / Brand --}}
    <div class="px-5 py-5" style="border-bottom: 1px solid hsl(var(--sidebar-border));">
        <div class="flex items-center gap-2.5">
            <div class="h-8 w-8 rounded-xl brand-gradient-bg flex items-center justify-center shrink-0 shadow-sm">
                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold leading-none" style="color: hsl(var(--foreground));">Cybix Deployer</p>
                <p class="text-[11px] mt-0.5" style="color: hsl(var(--muted-foreground));">Package Distribution</p>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-6">

        {{-- Workspace Group --}}
        <div>
            <div class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.16em]"
                style="color: hsl(var(--muted-foreground));">
                Workspace
            </div>
            <ul class="space-y-0.5">
                {{-- Dashboard --}}
                <li>
                    <a href="{{ route('home') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 group
                            {{ request()->routeIs('home') ? 'sidebar-active' : 'sidebar-link' }}">
                        @if(request()->routeIs('home'))
                            <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full brand-gradient-bg"></span>
                        @endif
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                {{-- Create Package --}}
                <li>
                    <a href="{{ route('create-package') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 group
                            {{ request()->routeIs('create-package') ? 'sidebar-active' : 'sidebar-link' }}">
                        @if(request()->routeIs('create-package'))
                            <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full brand-gradient-bg"></span>
                        @endif
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 16h6M19 13v6M21 10V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l2-1.14M7.5 4.27l9 5.15"/>
                            <polyline stroke-linecap="round" stroke-linejoin="round" points="3.29 7 12 12 20.71 7"/>
                            <line stroke-linecap="round" stroke-linejoin="round" x1="12" x2="12" y1="22" y2="12"/>
                        </svg>
                        <span>Create Package</span>
                    </a>
                </li>
                {{-- Packages --}}
                <li>
                    <a href="{{ route('packages.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 group
                            {{ request()->routeIs('packages.index') ? 'sidebar-active' : 'sidebar-link' }}">
                        @if(request()->routeIs('packages.index'))
                            <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full brand-gradient-bg"></span>
                        @endif
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 21.73a2 2 0 002 0l7-4A2 2 0 0021 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12M3.3 7l7.703 4.734a2 2 0 001.994 0L20.7 7m-13.2-2.73l9 5.15"/>
                        </svg>
                        <span>Packages</span>
                    </a>
                </li>
                {{-- Projects --}}
                <li>
                    <a href="{{ route('projects') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 group
                            {{ request()->routeIs('projects') ? 'sidebar-active' : 'sidebar-link' }}">
                        @if(request()->routeIs('projects'))
                            <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full brand-gradient-bg"></span>
                        @endif
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Projects</span>
                    </a>
                </li>
            </ul>
        </div>

        {{-- Infrastructure Group --}}
        <div>
            <div class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.16em]"
                style="color: hsl(var(--muted-foreground));">
                Infrastructure
            </div>
            <ul class="space-y-0.5">
                <li>
                    <a href="#"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 sidebar-link opacity-50 cursor-not-allowed">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        <span>Repositories</span>
                        <span class="ml-auto text-[10px] font-semibold px-1.5 py-0.5 rounded brand-soft-bg" style="color:hsl(var(--brand-iris))">Soon</span>
                    </a>
                </li>
                <li>
                    <a href="#"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 sidebar-link opacity-50 cursor-not-allowed">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                        <span>Servers</span>
                        <span class="ml-auto text-[10px] font-semibold px-1.5 py-0.5 rounded brand-soft-bg" style="color:hsl(var(--brand-iris))">Soon</span>
                    </a>
                </li>
            </ul>
        </div>

        {{-- Governance Group --}}
        <div>
            <div class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.16em]"
                style="color: hsl(var(--muted-foreground));">
                Governance
            </div>
            <ul class="space-y-0.5">
                <li>
                    <a href="{{ route('settings') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 group
                            {{ request()->routeIs('settings') ? 'sidebar-active' : 'sidebar-link' }}">
                        @if(request()->routeIs('settings'))
                            <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full brand-gradient-bg"></span>
                        @endif
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </div>

    </nav>

    {{-- Pro tip --}}
    <div class="m-3 p-4 rounded-xl brand-soft-bg" style="border: 1px solid hsl(var(--border) / 0.6);">
        <div class="text-xs font-semibold mb-1" style="color: hsl(var(--foreground));">Pro tip</div>
        <p class="text-xs leading-relaxed" style="color: hsl(var(--muted-foreground));">
            Always generate a rollback package alongside production updates.
        </p>
    </div>

    {{-- User footer --}}
    <div class="px-4 py-4" style="border-top: 1px solid hsl(var(--sidebar-border));">
        <div class="flex items-center gap-3">
            <div class="h-8 w-8 rounded-full brand-gradient-bg flex items-center justify-center shrink-0 text-white text-xs font-bold ring-2" style="ring-color: hsl(var(--border));">
                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold truncate" style="color: hsl(var(--foreground));">{{ Auth::user()->name }}</p>
                <p class="text-xs truncate" style="color: hsl(var(--muted-foreground));">{{ Auth::user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout.user') }}">
                @csrf
                <button type="submit" title="Logout"
                    class="p-1.5 rounded-lg transition-colors hover:bg-red-50 hover:text-red-600"
                    style="color: hsl(var(--muted-foreground));">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

</aside>

<style>
    .sidebar-link {
        color: hsl(var(--sidebar-fg) / 0.8);
    }
    .sidebar-link:hover {
        color: hsl(var(--sidebar-fg));
        background-color: hsl(var(--sidebar-accent));
    }
    .sidebar-active {
        background-color: hsl(var(--sidebar-accent));
        color: hsl(var(--sidebar-accent-fg));
        font-weight: 600;
    }
</style>