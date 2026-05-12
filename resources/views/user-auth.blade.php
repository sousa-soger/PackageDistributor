@extends('layouts.app-nosidebar')

@section('title', 'Log in')

    @section('content')
        <section class="min-h-screen px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex min-h-[calc(100vh-3rem)] w-full flex-col">
                <header class="flex items-center">
                    <div class="px-5 py-5">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="relative flex items-center justify-center rounded-xl brand-gradient-bg shadow-soft h-9 w-9">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 64 64"
                                    fill="currentColor" class="text-[hsl(var(--on-brand))]" fill="none" stroke="current-color">
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
                </header>

                <div
                    class="grid flex-1 gap-6 py-4 md:grid-cols-[minmax(0,7fr)_minmax(20rem,3fr)] md:items-stretch md:gap-6 lg:grid-cols-[minmax(0,7fr)_minmax(24rem,3fr)]">
                    <div
                        class="relative h-full min-h-[34rem] overflow-hidden rounded-[2rem] border border-border/70 px-6 py-8 shadow-soft sm:px-8 sm:py-10 lg:px-10">
                        <div class="absolute inset-0 brand-soft-bg opacity-80 pointer-events-none"></div>
                        <div
                            class="absolute -top-24 right-0 h-56 w-56 rounded-full bg-brand-iris/20 blur-3xl pointer-events-none">
                        </div>
                        <div
                            class="absolute -bottom-20 left-0 h-48 w-48 rounded-full bg-brand-rose/20 blur-3xl pointer-events-none">
                        </div>

                        <div class="relative max-w-2xl">
                            <div
                                class="inline-flex items-center gap-2 rounded-full border border-border/70 bg-background/80 px-3 py-1 text-xs font-medium text-muted-foreground backdrop-blur-sm">
                                <span class="status-dot bg-success animate-pulse-soft"></span>
                                LDAP-backed access
                            </div>

                            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-foreground sm:text-5xl lg:text-6xl">
                                Package deployment,
                                <span class="brand-gradient-text">styled for the rest of the workspace.</span>
                            </h1>

                            <p class="mt-4 max-w-xl text-sm leading-7 text-muted-foreground sm:text-base">
                                Sign in with your company LDAP username to manage repositories, create packages, and track
                                deployments from one place.
                            </p>

                            <div class="mt-8 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-border/70 bg-background/80 p-4 backdrop-blur-sm">
                                    <p class="text-sm font-semibold text-foreground">Repository access</p>
                                    <p class="mt-1 text-xs leading-6 text-muted-foreground">Connect code sources and keep
                                        permissions aligned with your directory account.</p>
                                </div>
                                <div class="rounded-2xl border border-border/70 bg-background/80 p-4 backdrop-blur-sm">
                                    <p class="text-sm font-semibold text-foreground">Fast packaging</p>
                                    <p class="mt-1 text-xs leading-6 text-muted-foreground">Create update and rollback bundles
                                        without leaving the deployment flow.</p>
                                </div>
                                <div class="rounded-2xl border border-border/70 bg-background/80 p-4 backdrop-blur-sm">
                                    <p class="text-sm font-semibold text-foreground">Shared visibility</p>
                                    <p class="mt-1 text-xs leading-6 text-muted-foreground">See project ownership, active jobs,
                                        and release status in one dashboard.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-card animate-slide-up flex h-full w-full flex-col overflow-hidden border-border/70 p-0">
                        <div class="brand-soft-bg border-b border-border/60 px-6 py-5 sm:px-7">
                            <p class="text-xs font-semibold tracking-[0.3em] text-muted-foreground uppercase">Welcome Back</p>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-foreground">Sign in to continue</h2>
                            <p class="mt-1 text-sm text-muted-foreground">Use your LDAP username and password to access the
                                deployment portal.</p>
                        </div>

                        <form action="{{ route('login.user') }}" method="POST" class="flex flex-1 flex-col px-6 py-6 sm:px-7">
                            @csrf

                            <div class="space-y-5">
                                <div class="space-y-2">
                                    <label for="loginusername" class="text-sm font-medium leading-none text-foreground">LDAP
                                        Username</label>
                                    <input id="loginusername" name="loginusername" type="text" autocomplete="username"
                                        placeholder="Enter your LDAP username" value="{{ old('loginusername') }}"
                                        class="flex h-11 w-full rounded-xl border border-input bg-background px-3 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                    @error('loginusername')
                                        <p class="text-xs text-failed">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-2">
                                    <label for="loginpassword"
                                        class="text-sm font-medium leading-none text-foreground">Password</label>

                                    <div class="relative">
                                        <input id="loginpassword" name="loginpassword" type="password"
                                            autocomplete="current-password" placeholder="Enter your password"
                                            class="flex h-11 w-full rounded-xl border border-input bg-background px-3 pr-11 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">

                                        <button id="togglePassword" type="button"
                                            class="absolute inset-y-0 right-0 inline-flex w-11 items-center justify-center text-muted-foreground transition-colors hover:text-foreground"
                                            aria-label="Show password">
                                            <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24"
                                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                aria-hidden="true">
                                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                aria-hidden="true">
                                                <path d="M3 3l18 18"></path>
                                                <path d="M10.58 10.58a2 2 0 1 0 2.83 2.83"></path>
                                                <path
                                                    d="M9.88 5.09A10.94 10.94 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-4.21 5.17">
                                                </path>
                                                <path
                                                    d="M6.61 6.61A13.18 13.18 0 0 0 2 12s3.5 7 10 7a9.77 9.77 0 0 0 5.39-1.61">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>

                                    @error('loginpassword')
                                        <<<<<<< ours <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror

                                        <script>
                                            const passwordInput = document.getElementById('loginpassword');
                                            const toggleButton = document.getElementById('togglePassword');
                                            const eyeOpen = document.getElementById('eyeOpen');
                                            const eyeClosed = document.getElementById('eyeClosed');

                                            toggleButton.addEventListener('click', function () {
                                                const isPassword = passwordInput.type === 'password';

                                                passwordInput.type = isPassword ? 'text' : 'password';

                                                eyeOpen.classList.toggle('hidden', isPassword);
                                                eyeClosed.classList.toggle('hidden', !isPassword);

                                                toggleButton.setAttribute(
                                                    'aria-label',
                                                    isPassword ? 'Hide password' : 'Show password'
                                                );
                                            });
                                        </script>

                                </div>
                                <!-- stay logged in for 30 days ---------
                                    <label class="flex items-center gap-3 text-sm text-slate-400">
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 rounded border border-white/10 bg-slate-800 text-emerald-500 focus:ring-emerald-400/30"
                                        >
                                        <span>Remember me for 30 days</span>
                                    </label>
                                    -->
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r 
                                        from-[#E9B3FB] to-purple-500 px-5 py-4 text-base font-bold text-slate-950 shadow-[0_10px_35px_rgba(16,185,129,0.28)] transition 
                                        hover:scale-[1.01] hover:from-purple-400 hover:to-cyan-400">
                                    Log in
                                </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- to try for login only --}}
            <footer class="pt-6 text-sm text-slate-500">
                <button onclick="document.getElementById('registerModal').classList.remove('hidden')"
                    class="px-4 py-2 rounded-lg bg-white outline text-black hover:bg-blue-700 transition">
                    Register User
                </button>
                <div id="registerModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
                    <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-800">Register User</h2>
                            <button onclick="document.getElementById('registerModal').classList.add('hidden')"
                                class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                ✕
                            </button>
                        </div>

                        <form action="{{ route('register.user') }}" method="POST" class="px-6 py-5 space-y-4">
                            @csrf
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                                <input name="name" type="text" placeholder="Enter full name"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                                <input name="email" type="email" placeholder="Enter email"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                                <input name="password" type="password" placeholder="Enter password"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            </div>
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button type="button" onclick="document.getElementById('registerModal').classList.add('hidden')"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    Cancel
                                </button>

                                <button type="submit"
                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                    Register
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </footer>
            </div>


        </section>

        <!--
        <div class="flex h-full flex-col">
            <div class="p-4">
                <h1 class="text-[22px] leading-8 font-bold text-slate-900">CybixDeployer</h1>
            </div>
            <div class="flex">
                <div class="w-full bg-purple-500 flex flex-col justify-center items-center">
                    <h1>Texttext text tex</h1>
                    <h3>tex tex text</h3>
                    <div>
                        <p>a;sldkfjlaksdj alksdjfl aksdjf alskdjf lk alkdsjf alskdjf lkasjd flaksdjf </p>
                    </div>
                </div>

                <div class="flex">
                    <x-ui.card class="flex flex-col justify-center items">
                    <h2 class="text-lg font-semibold text-slate-900">Welcomee Back</h2>
                    <p class="text-sm text-slate-500 mt-2">
                        Please enter your details to log in
                    </p>

                    <div class="mt-6">
                            <a href="{{ route('new-packageV3') }}">
                                <x-ui.primary-button>Create New Package</x-ui.primary-button>
                            </a>
                    </div>
                </x-ui.card>
                </div>
            </div>
        </div>
        -->
    @endsection
    =======
    <p class="text-xs text-failed">{{ $message }}</p>
    @enderror
    </div>
    </div>

    <div class="mt-auto space-y-5 pt-8">
        <button type="submit"
            class="inline-flex h-11 w-full items-center justify-center rounded-xl brand-gradient-bg px-4 text-sm font-semibold text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03]">
            Log in
        </button>

        <p class="text-center text-xs leading-6 text-muted-foreground">
            Use the same LDAP username you use elsewhere in the company workspace.
        </p>
    </div>
    </form>
    </div>
    </div>

    <footer class="flex justify-end pt-2">
        <button type="button" onclick="document.getElementById('registerModal').classList.remove('hidden')"
            class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
            Register User
        </button>
    </footer>
    </div>

    <div id="registerModal"
        class="fixed inset-0 z-50 hidden flex items-center justify-center bg-background/70 px-4 backdrop-blur-sm">
        <div class="w-full max-w-md overflow-hidden rounded-[1.5rem] border border-border bg-card shadow-lg">
            <div class="brand-soft-bg flex items-start justify-between gap-4 border-b border-border/60 px-6 py-5">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-foreground">Register User</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Create a local account for environments that are not
                        using LDAP.</p>
                </div>
                <button type="button" onclick="document.getElementById('registerModal').classList.add('hidden')"
                    class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-background/80 hover:text-foreground">
                    <span class="sr-only">Close</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>

            <form action="{{ route('register.user') }}" method="POST" class="space-y-4 px-6 py-6">
                @csrf

                <div class="space-y-2">
                    <label for="register-name" class="text-sm font-medium leading-none text-foreground">Username</label>
                    <input id="register-name" name="name" type="text" placeholder="Enter full name"
                        class="flex h-10 w-full rounded-xl border border-input bg-background px-3 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                </div>

                <div class="space-y-2">
                    <label for="register-email" class="text-sm font-medium leading-none text-foreground">Email</label>
                    <input id="register-email" name="email" type="email" placeholder="Enter email"
                        class="flex h-10 w-full rounded-xl border border-input bg-background px-3 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                </div>

                <div class="space-y-2">
                    <label for="register-password" class="text-sm font-medium leading-none text-foreground">Password</label>
                    <input id="register-password" name="password" type="password" placeholder="Enter password"
                        class="flex h-10 w-full rounded-xl border border-input bg-background px-3 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-border/60 pt-4 sm:flex-row sm:justify-end">
                    <button type="button" onclick="document.getElementById('registerModal').classList.add('hidden')"
                        class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                        Cancel
                    </button>
                    <button type="submit"
                        class="inline-flex h-10 items-center justify-center rounded-md brand-gradient-bg px-4 text-sm font-medium text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03]">
                        Register
                    </button>
                </div>
            </form>
        </div>
    </div>
    </section>
@endsection

@push('scripts')
    <script>
        const passwordInput = document.getElementById('loginpassword');
        const toggleButton = document.getElementById('togglePassword');
        const eyeOpen = document.getElementById('eyeOpen');
        const eyeClosed = document.getElementById('eyeClosed');
        const registerModal = document.getElementById('registerModal');

        if (toggleButton && passwordInput && eyeOpen && eyeClosed) {
            toggleButton.addEventListener('click', function () {
                const isPassword = passwordInput.type === 'password';

                passwordInput.type = isPassword ? 'text' : 'password';
                eyeOpen.classList.toggle('hidden', !isPassword);
                eyeClosed.classList.toggle('hidden', isPassword);
                toggleButton.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            });
        }

        if (registerModal) {
            registerModal.addEventListener('click', function (event) {
                if (event.target === registerModal) {
                    registerModal.classList.add('hidden');
                }
            });
        }
    </script>
@endpush
>>>>>>> theirs