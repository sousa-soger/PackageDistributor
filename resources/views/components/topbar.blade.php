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

        {{-- Profile dropdown --}}
        <div x-data="{ open: false }" class="relative" @keydown.escape.window="open = false">
            <button
                @click="open = !open"
                type="button"
                aria-label="Open profile menu"
                :aria-expanded="open"
                :data-state="open ? 'open' : 'closed'"
                class="rounded-full focus:outline-none focus:ring-2 focus:ring-[hsl(var(--ring))] focus:ring-offset-2 focus:ring-offset-background">
                <span class="relative flex shrink-0 overflow-hidden rounded-full h-9 w-9 ring-2 transition-all duration-300 ease-out hover:ring-4 hover:ring-offset-2 hover:ring-offset-background hover:ring-[hsl(var(--primary)/0.35)] cursor-pointer"
                    style="ring-color: hsl(var(--border));"
                    :class="open ? 'ring-4! ring-[hsl(var(--primary)/0.5)]! ring-offset-2! ring-offset-background!' : ''">
                    @if(Auth::user()->avatar_url)
                        <img src="{{ Auth::user()->avatar_url }}"
                            alt="{{ Auth::user()->name }}"
                            class="h-full w-full object-cover rounded-full">
                    @else
                        <span class="flex h-full w-full items-center justify-center rounded-full brand-gradient-bg text-[hsl(var(--on-brand))] text-xs font-semibold">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </span>
                    @endif
                </span>
            </button>

            {{-- Dropdown panel --}}
            <div
                x-show="open"
                x-cloak
                @click.outside="open = false"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-64 z-50 rounded-md border overflow-hidden shadow-md"
                style="background:hsl(var(--card));border-color:hsl(var(--border));transform-origin:top right"
                role="menu" aria-orientation="vertical">

                {{-- User info --}}
                <div class="px-2 py-1.5 text-sm font-semibold">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold" style="color:hsl(var(--foreground))">{{ Auth::user()->name }}</span>
                        <span class="text-xs font-normal" style="color:hsl(var(--muted-foreground))">{{ Auth::user()->email }}</span>
                    </div>
                </div>
                <div role="separator" class="-mx-1 my-1 h-px" style="background:hsl(var(--muted))"></div>

                {{-- View profile --}}
                <a href="{{ route('settings') }}" role="menuitem" tabindex="-1"
                    class="relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent"
                    style="color:hsl(var(--foreground))">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    View profile
                </a>
                <div role="separator" class="-mx-1 my-1 h-px" style="background:hsl(var(--muted))"></div>

                {{-- Quick connect --}}
                <div class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider" style="color:hsl(var(--muted-foreground))">Quick connect</div>

                <div role="menuitem" tabindex="-1"
                    class="relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent"
                    style="color:hsl(var(--foreground))">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0">
                        <path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/>
                        <path d="M9 18c-4.51 2-5-2-7-2"/>
                    </svg>
                    <span class="flex-1">GitHub</span>
                    <span class="inline-flex items-center gap-1 text-[11px]" style="color:hsl(var(--muted-foreground))">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                            <path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" x2="16" y1="12" y2="12"/>
                        </svg>
                        Connect
                    </span>
                </div>

                <div role="menuitem" tabindex="-1"
                    class="relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent"
                    style="color:hsl(var(--foreground))">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0">
                        <path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"/>
                    </svg>
                    <span class="flex-1">GitLab</span>
                    <span class="inline-flex items-center gap-1 text-[11px]" style="color:hsl(var(--muted-foreground))">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                            <path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" x2="16" y1="12" y2="12"/>
                        </svg>
                        Connect
                    </span>
                </div>
                <div role="separator" class="-mx-1 my-1 h-px" style="background:hsl(var(--muted))"></div>

                {{-- Settings --}}
                <a role="menuitem" tabindex="-1"
                    href="{{ route('settings') }}"
                    class="relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent"
                    style="color:hsl(var(--foreground))">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    Settings
                </a>
                <div role="separator" class="-mx-1 my-1 h-px" style="background:hsl(var(--muted))"></div>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout.user') }}">
                    @csrf
                    <button type="submit" role="menuitem" tabindex="-1"
                        class="w-full relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent"
                        style="color:hsl(var(--failed))">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>
                        </svg>
                        Log out
                    </button>
                </form>
            </div>
        </div>

    </div>
</header>
