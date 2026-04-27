<header class="sticky top-0 z-30 backdrop-blur-xl"
    style="border-bottom: 1px solid hsl(var(--border) / 0.7); background-color: hsl(var(--background) / 0.7);">
    <div class="flex items-center gap-4 px-6 lg:px-8 h-16">

        {{-- Page title slot --}}
        <div class="flex-1 min-w-0">
            @hasSection('title')
                <div class="flex flex-col leading-tight">
                    <h1 class="text-base font-semibold tracking-tight truncate" style="color: hsl(var(--foreground));">
                        @yield('title')
                    </h1>
                    @hasSection('subtitle')
                        <p class="text-xs truncate" style="color: hsl(var(--muted-foreground));">@yield('subtitle')</p>
                    @endif
                </div>
            @endif
        </div>

        @yield('topbar_actions')

        {{-- Search --}}
        <div class="hidden md:flex relative w-72">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 pointer-events-none"
                style="color: hsl(var(--muted-foreground));"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text"
                placeholder="Search projects, packages…"
                class="w-full pl-9 pr-4 py-2 text-sm rounded-xl outline-none transition-all"
                style="background-color: hsl(var(--secondary) / 0.5); border: 1px solid hsl(var(--border) / 0.6); color: hsl(var(--foreground));">
        </div>

        {{-- Theme toggle --}}
        <div x-data="{
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
            toggle() {
                this.theme = this.theme === 'dark' ? 'light' : 'dark';
                if (this.theme === 'dark') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                }
            }
        }">
            <button @click="toggle()" type="button"
                class="h-9 w-9 flex items-center justify-center rounded-lg transition-colors"
                style="color: hsl(var(--muted-foreground));"
                x-bind:class="'hover:bg-[hsl(var(--secondary))]'"
                aria-label="Toggle theme">
                <svg x-show="theme === 'dark'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <svg x-show="theme === 'light'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9 9 0 008.354-5.646z"/>
                </svg>
            </button>
        </div>

        {{-- Notifications --}}
        <button type="button"
            class="relative h-9 w-9 flex items-center justify-center rounded-lg transition-colors"
            style="color: hsl(var(--muted-foreground));"
            x-bind:class="'hover:bg-[hsl(var(--secondary))]'"
            aria-label="Notifications">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.917V4a1 1 0 10-2 0v1.083A6 6 0 006 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
        </button>

        {{-- Avatar --}}
        <div class="h-9 w-9 rounded-full brand-gradient-bg ring-2 flex items-center justify-center text-white text-xs font-bold shrink-0"
            style="ring-color: hsl(var(--border));">
            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
        </div>

    </div>
</header>
