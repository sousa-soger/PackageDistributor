<aside x-data="{ 
        collapsed: localStorage.getItem('sidebar_collapsed') === 'true',
        init() {
            $watch('collapsed', val => localStorage.setItem('sidebar_collapsed', val))
        }
    }" :class="collapsed ? 'w-24' : 'w-72'"
    class="sticky top-0 h-screen bg-white border-r border-slate-200 transition-all duration-300 ease-in-out overflow-hidden">
    <div class="flex h-full flex-col">
        <div class="px-4 py-3 border-b border-slate-200">
            <div class="flex items-center gap-3 w-full" :class="collapsed ? 'justify-center' : ''">
                <div x-show="!collapsed" x-transition.opacity class="flex-1">
                    <h1 class="text-[22px] leading-8 font-bold text-slate-900 whitespace-nowrap">CybixDeployer</h1>
                    <p class="text-[14px] text-slate-500 mt-1 whitespace-nowrap">Package Distribution</p>
                </div>

                <div class="p-1 shrink-0" :class="collapsed ? '' : 'ml-auto'">
                    <button type="button" @click="collapsed = !collapsed"
                        class="p-2 rounded-lg hover:bg-slate-100 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="16" height="16" rx="2" ry="2" />
                            <line x1="9" y1="3" x2="9" y2="19" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Nav-link -->
        <div class="flex-1 overflow-y-auto">

            <!-- Home nav-link -->
            <nav class="px-4 py-3 space-y-2">
                <x-ui.nav-link :href="route('home')" :active="request()->routeIs('home')">
                    <div class="icon-container">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24"
                            aria-hidden="true">
                            <path d="M3 10.5L12 3l9 7.5" />
                            <path d="M5 9.5V21h14V9.5" />
                            <path d="M9 21v-6h6v6" />
                        </svg>
                    </div>
                    <span x-show="!collapsed" x-transition.opacity class="nav-text">
                        Home
                    </span>
                </x-ui.nav-link>


                <x-ui.nav-link >
                    <div class="icon-container">
                        <svg width="22" height="22" viewBox="0 0 24 24" role="img" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.844.904a1.007 1.007 0 00-.955.692l-2.53 7.783c0 .007-.005.012-.007.02L.07 13.335a1.437 1.437 0 00.522 1.607l11.072 8.045a.566.566 0 00.67-.004l11.074-8.04a1.436 1.436 0 00.522-1.61l-1.26-3.867a.547.547 0 00-.031-.104l-2.526-7.775a1.004 1.004 0 00-.957-.684.987.987 0 00-.949.69l-2.406 7.408H8.203l-2.41-7.408a.987.987 0 00-.943-.69h-.006zm-.006 1.42l2.174 6.678H2.674l2.164-6.678zm14.328 0l2.168 6.678h-4.342l2.174-6.678zm-10.594 7.81h6.862l-2.15 6.618L12 20.693 8.572 10.135zm-5.515.005h4.322l3.086 9.5-7.408-9.5zm13.568 0h4.326l-6.703 8.588-.709.914 2.959-9.108.127-.394zM2.1 10.762l6.978 8.947-7.818-5.682a.305.305 0 01-.112-.341l.952-2.924zm19.8 0l.952 2.922a.305.305 0 01-.11.341v.002l-7.82 5.68.025-.035 6.953-8.91Z"/>
                        </svg>
                    </div>
                    <span x-show="!collapsed" x-transition.opacity class="nav-text">
                        Register Repository
                    </span>
                </x-ui.nav-link>


                <!-- New PackageV3 nav-link -->
                <x-ui.nav-link :href="route('new-packageV3')" :active="request()->routeIs('new-packageV3')">
                    <div class="icon-container">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24"
                            aria-hidden="true">
                            <path d="M2 8h4" />
                            <path d="M0 12h6" />
                            <path d="M3 16h3" />
                            <path d="M12 3 5 7v10l7 4 7-4V7l-7-4Z" />
                            <path d="M5 7l7 4 7-4" />
                            <path d="M12 11v10" />
                            <path d="M8.5 5.2 16.5 9" />
                        </svg>
                    </div>
                    <span x-show="!collapsed" x-transition.opacity class="nav-text">
                        New PackageV3
                    </span>
                </x-ui.nav-link>

                <!-- Settings nav-link -->
                <x-ui.nav-link :href="route('settings')" :active="request()->routeIs('settings')">
                    <div class="icon-container">
                        <svg width="24" height="24" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor">
                            <path
                                d="M9.1 4.4L8.6 2H7.4l-.5 2.4-.7.3-2-1.3-.9.8 1.3 2-.2.7-2.4.5v1.2l2.4.5.3.8-1.3 2 .8.8 2-1.3.8.3.4 2.3h1.2l.5-2.4.8-.3 2 1.3.8-.8-1.3-2 .3-.8 2.3-.4V7.4l-2.4-.5-.3-.8 1.3-2-.8-.8-2 1.3-.7-.2zM9.4 1l.5 2.4L12 2.1l2 2-1.4 2.1 2.4.4v2.8l-2.4.5L14 12l-2 2-2.1-1.4-.5 2.4H6.6l-.5-2.4L4 13.9l-2-2 1.4-2.1L1 9.4V6.6l2.4-.5L2.1 4l2-2 2.1 1.4.4-2.4h2.8zm.6 7c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2zM8 9c.6 0 1-.4 1-1s-.4-1-1-1-1 .4-1 1 .4 1 1 1z" />
                        </svg>
                    </div>
                    <span x-show="!collapsed" x-transition.opacity class="nav-text">
                        Settings
                    </span>
                </x-ui.nav-link>
            </nav>


        </div>

        <div class="p-4 border-t border-slate-200">
            <div class="flex items-center gap-2" :class="collapsed ? 'justify-center' : ''">
                <div class="w-7 h-7 rounded-full bg-slate-200"></div>

                <div x-show="!collapsed" x-transition.opacity class="min-w-0">
                    <p class="text-sm font-semibold text-slate-700 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
                </div>

                <div class="ml-auto p-1" x-show="!collapsed" x-transition.opacity>
                    <form method="POST" action="{{ route('logout.user')}}">
                        @csrf
                        <button type="submit"
                            class="rounded p-2 text-slate-500 hover:bg-slate-100 hover:text-red-600 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3-3H9m0 0l3-3m-3 3l3 3" />
                            </svg>
                        </button>
                    </form>
                    {{-- make background of logout icon slightly grey on hover
                    <a href="user-auth">
                        <button class="text-slate-500 hover:text-red-600 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3-3H9m0 0l3-3m-3 3l3 3" />
                            </svg>
                        </button>
                    </a>
                    --}}
                </div>
            </div>
        </div>
</aside>