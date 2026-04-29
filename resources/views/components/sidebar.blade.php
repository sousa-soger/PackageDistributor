<aside class="hidden lg:flex h-screen w-[260px] shrink-0 flex-col sticky top-0 z-40"
    style="background-color: hsl(var(--sidebar-bg)); border-right: 1px solid hsl(var(--sidebar-border));">

    {{-- Logo / Brand --}}
    <div class="px-5 py-5" style="border-bottom: 1px solid hsl(var(--sidebar-border));">
        <div class="flex items-center gap-2.5">
            <div class="relative flex items-center justify-center rounded-xl brand-gradient-bg shadow-soft h-9 w-9">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 64 64" fill="currentColor"
                    class="text-[hsl(var(--on-brand))]" fill="none" stroke="current-color">
                    <title>construction</title>
                    <path
                        d="M27.69,34.62l-4.85-7.84.37-.36,1.6,1.37a1.5,1.5,0,0,0,.53,1.93,1.54,1.54,0,0,0,.81.23,1.49,1.49,0,0,0,.87-.27L37,38.2a.62.62,0,0,0,.44.18.64.64,0,0,0,.47-.21.65.65,0,0,0,0-.91L27.8,28.66l5-7.88a1.49,1.49,0,0,0,0-1.62,2.26,2.26,0,0,0-.25-.29L26.74,12a1.5,1.5,0,0,0-1.24-.66h0l-7.67.07a1.22,1.22,0,0,0-1.07.69l-2.37,5.09-.63-.54a.63.63,0,0,0-.9,0,.64.64,0,0,0,0,.91l.93.8-.24.53a1.52,1.52,0,0,0,.59,2,1.42,1.42,0,0,0,.74.2,1.51,1.51,0,0,0,1.27-.7l.78.67L13.4,24.87a2.37,2.37,0,0,0-.76,1.66l-.26,8.58L10.05,45.19A2.35,2.35,0,0,0,11.81,48a2.48,2.48,0,0,0,.54.06,2.35,2.35,0,0,0,2.29-1.83l2.44-10.53.23-7.63,1.4,1,4,6.49-4.38,9.15a2.35,2.35,0,1,0,4.25,2L27.76,36A1.37,1.37,0,0,0,27.69,34.62Z" />
                    <circle cx="33.46" cy="12.22" r="4.3" />
                    <path
                        d="M61.39,45.33c-1.44-2.1-8.34-15.16-10.12-17s-3.36-2.08-4.44-1.64a6.94,6.94,0,0,0-3,2.24c-1.27,1.73-3,6.91-4.44,9.23a11.08,11.08,0,0,0-.8,1l-.08.06c-1.06.64-4,1.19-5.19,2.4a15.63,15.63,0,0,0-1.92,2.6l-.18.25a8.22,8.22,0,0,1-3.93,3.27l0,.32H60.43C60.66,48.08,63.15,47.9,61.39,45.33Z" />
                </svg>
            </div>
            <div class="flex flex-col leading-none">
                <span class="font-semibold tracking-tight text-lg">Cybix <span
                        class="brand-gradient-text">Deployer</span></span>
                <span class="text-[10px] uppercase tracking-[0.18em] text-muted-foreground mt-0.5">CI / CD
                    Platform</span>
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
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 group
                            {{ request()->routeIs('dashboard') ? 'sidebar-active' : 'sidebar-link' }}">
                        @if(request()->routeIs('dashboard'))
                            <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full brand-gradient-bg"></span>
                        @endif
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-layout-dashboard h-4 w-4">
                            <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                            <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                            <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                            <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                {{-- Create Package --}}
                <li>
                    <a href="{{ route('create-package') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 group
                            {{ request()->routeIs('create-package') ? 'sidebar-active' : 'sidebar-link' }}">
                        @if(request()->routeIs('create-package'))
                            <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full brand-gradient-bg"></span>
                        @endif
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 16h6M19 13v6M21 10V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l2-1.14M7.5 4.27l9 5.15" />
                            <polyline stroke-linecap="round" stroke-linejoin="round" points="3.29 7 12 12 20.71 7" />
                            <line stroke-linecap="round" stroke-linejoin="round" x1="12" x2="12" y1="22" y2="12" />
                        </svg>
                        <span>Create Package</span>
                    </a>
                </li>
                {{-- Packages --}}
                <li>
                    <a href="{{ route('packages.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 group
                            {{ request()->routeIs('packages.index') ? 'sidebar-active' : 'sidebar-link' }}">
                        @if(request()->routeIs('packages.index'))
                            <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full brand-gradient-bg"></span>
                        @endif
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11 21.73a2 2 0 002 0l7-4A2 2 0 0021 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 22V12M3.3 7l7.703 4.734a2 2 0 001.994 0L20.7 7m-13.2-2.73l9 5.15" />
                        </svg>
                        <span>Packages</span>
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
                {{-- Projects --}}
                <li>
                    <a href="{{ route('projects') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 group
                            {{ request()->routeIs('projects') ? 'sidebar-active' : 'sidebar-link' }}">
                        @if(request()->routeIs('projects'))
                            <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full brand-gradient-bg"></span>
                        @endif
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-folder-kanban h-4 w-4">
                            <path
                                d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z">
                            </path>
                            <path d="M8 10v4"></path>
                            <path d="M12 10v2"></path>
                            <path d="M16 10v6"></path>
                        </svg>
                        {{--
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        --}}
                        <span>Projects</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('repositories') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 group
                            {{ request()->routeIs('repositories') ? 'sidebar-active' : 'sidebar-link' }}">
                        @if(request()->routeIs('repositories'))
                            <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full brand-gradient-bg"></span>
                        @endif
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-git-branch h-4 w-4">
                            <line x1="6" x2="6" y1="3" y2="15"></line>
                            <circle cx="18" cy="6" r="3"></circle>
                            <circle cx="6" cy="18" r="3"></circle>
                            <path d="M18 9a9 9 0 0 1-9 9"></path>
                        </svg>
                        <span>Repositories</span>
                    </a>
                </li>
                <li>
                    <a href="#"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 sidebar-link opacity-50 cursor-not-allowed">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                        </svg>
                        <span>Servers</span>
                        <span class="ml-auto text-[10px] font-semibold px-1.5 py-0.5 rounded brand-soft-bg"
                            style="color:hsl(var(--brand-iris))">Soon</span>
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
                    <a href="{{ route('team') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 group
                            {{ request()->routeIs('team') ? 'sidebar-active' : 'sidebar-link' }}">
                        @if(request()->routeIs('team'))
                            <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full brand-gradient-bg"></span>
                        @endif
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-users h-4 w-4">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Teams & Roles</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('settings') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium relative transition-all duration-200 group
                            {{ request()->routeIs('settings') ? 'sidebar-active' : 'sidebar-link' }}">
                        @if(request()->routeIs('settings'))
                            <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full brand-gradient-bg"></span>
                        @endif
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
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
            <div class="h-8 w-8 rounded-full brand-gradient-bg flex items-center justify-center shrink-0 text-white text-xs font-bold ring-2"
                style="ring-color: hsl(var(--border));">
                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold truncate" style="color: hsl(var(--foreground));">
                    {{ Auth::user()->name }}
                </p>
                <p class="text-xs truncate" style="color: hsl(var(--muted-foreground));">{{ Auth::user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout.user') }}">
                @csrf
                <button type="submit" title="Logout"
                    class="p-1.5 rounded-lg transition-colors hover:bg-red-50 hover:text-red-600"
                    style="color: hsl(var(--muted-foreground));">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
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


/*
███████╗██╗██████╗ ███████╗██████╗  █████╗ ██████╗     ███████╗ ██████╗██████╗  ██████╗ ██╗     ██╗     ██████╗  █████╗ ██████╗     
██╔════╝██║██╔══██╗██╔════╝██╔══██╗██╔══██╗██╔══██╗    ██╔════╝██╔════╝██╔══██╗██╔═══██╗██║     ██║     ██╔══██╗██╔══██╗██╔══██╗    
███████╗██║██║  ██║█████╗  ██████╔╝███████║██████╔╝    ███████╗██║     ██████╔╝██║   ██║██║     ██║     ██████╔╝███████║██████╔╝    
╚════██║██║██║  ██║██╔══╝  ██╔══██╗██╔══██║██╔══██╗    ╚════██║██║     ██╔══██╗██║   ██║██║     ██║     ██╔══██╗██╔══██║██╔══██╗    
███████║██║██████╔╝███████╗██████╔╝██║  ██║██║  ██║    ███████║╚██████╗██║  ██║╚██████╔╝███████╗███████╗██████╔╝██║  ██║██║  ██║    
╚══════╝╚═╝╚═════╝ ╚══════╝╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝    ╚══════╝ ╚═════╝╚═╝  ╚═╝ ╚═════╝ ╚══════╝╚══════╝╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝    
*/
    nav::-webkit-scrollbar {
        width: 5px;
    }

    nav::-webkit-scrollbar-track {
        background: transparent;
    }

    nav::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.3); /* Softened for light mode */
        border-radius: 20px;
        border: 1px solid transparent;
        background-clip: padding-box;
    }

    nav::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 0, 0, 0.5);
    }

    nav {
        scrollbar-width: thin;
        scrollbar-color: rgba(0, 0, 0, 0.3) transparent;
    }

    /* --- Dark Mode --- */
    .dark nav::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        border: 1px solid transparent;
        background-clip: padding-box;
    }

    .dark nav::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.4);
    }

    /* Firefox Dark Mode */
    .dark nav {
        scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
    }
/*      
█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗█████╗      
╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝╚════╝
*/

</style>