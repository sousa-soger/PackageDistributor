@extends('layouts.app-nosidebar')

@section('title', 'Log in')

@section('content')
    <section
        class="relative min-h-screen overflow-hidden"
        x-data="authLoginPageData({
            initialLoginMode: @js(old('loginmode', config('ldap.enabled') ? 'ldap' : 'local')),
            sessionStatusUrl: @js(route('auth.session-status')),
            revokeSessionUrl: @js(route('auth.revoke-current-session')),
            homeUrl: @js(route('home')),
        })"
        x-init="init()"
    >
        <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-6 sm:px-6 lg:px-10 lg:py-10">

            <div class="flex items-center justify-between">
                {{-- Brand header --}}
                <header class="inline-flex w-fit items-center gap-2.5">
                    <div class="flex flex-col leading-none">
                        <span class="text-xl font-semibold tracking-tight sm:text-2xl">
                            Cybix <span class="brand-gradient-text">Deployer</span>
                        </span>
                        <span class="mt-1 text-[10px] uppercase tracking-[0.2em] text-muted-foreground sm:text-xs">
                            CI / CD Platform
                        </span>
                    </div>
                </header>

                <div class=" justify-end rounded-lg border border-border/60 bg-card/30 p-1 backdrop-blur">
                    <div class="grid grid-cols-2 gap-1">
                        <button
                            type="button"
                            @click="loginMode = 'ldap'"
                            :disabled="isLoginBlocked()"
                            class="rounded-md px-2.5 py-1.5 text-[11px] font-medium tracking-tight transition-base"
                            :class="loginMode === 'ldap'
                                ? 'bg-background/80 text-foreground shadow-sm ring-1 ring-border/70'
                                : 'text-muted-foreground hover:bg-background/40 hover:text-foreground'"
                        >
                            LDAP login
                        </button>
                        <button
                            type="button"
                            @click="loginMode = 'local'"
                            :disabled="isLoginBlocked()"
                            class="rounded-md px-2.5 py-1.5 text-[11px] font-medium tracking-tight transition-base"
                            :class="loginMode === 'local'
                                ? 'bg-background/80 text-foreground shadow-sm ring-1 ring-border/70'
                                : 'text-muted-foreground hover:bg-background/40 hover:text-foreground'"
                        >
                            Local test user
                        </button>
                    </div>
                </div>

                <div x-data="themeToggleData()">
                    <button @click="toggle()" type="button"
                        class="h-9 w-9 flex items-center justify-center rounded-lg transition-colors"
                        style="color: hsl(var(--muted-foreground));" x-bind:class="'hover:bg-[hsl(var(--secondary))]'"
                        aria-label="Toggle theme">
                        <svg x-show="theme === 'dark'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg x-show="theme === 'light'" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9 9 0 008.354-5.646z" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Main grid --}}
            <div
                class="grid flex-1 grid-cols-1 items-center gap-10 py-10 lg:grid-cols-[minmax(0,1fr)_minmax(380px,440px)] lg:gap-16 lg:py-1">

                {{-- Left hero --}}
                <section class="flex max-w-xl flex-col">
                    <h1 class="text-3xl font-semibold leading-[1.1] tracking-tight sm:text-4xl lg:text-[2.75rem]">
                        Package deployment,
                        <span class="brand-gradient-text">
                            styled for the rest of the workspace.
                        </span>
                    </h1>

                    <p class="mt-5 max-w-md text-sm leading-relaxed text-muted-foreground sm:text-base">
                        Sign in with your company LDAP username to manage repositories, create packages, and track
                        deployments from one place.
                    </p>

                    <div class="mt-10 space-y-3">
                        @php
                            $features = [
                                [
                                    'title' => 'Repository access',
                                    'desc' => 'Connect code sources and keep permissions aligned with your directory account.',
                                    'icon' => '<path d="M15 6a9 9 0 0 0-9 9V3"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/>',
                                ],
                                [
                                    'title' => 'Fast packaging',
                                    'desc' => 'Create, update and rollback bundles without leaving the deployment flow.',
                                    'icon' => '<path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M12 22V12"/><polyline points="3.29 7 12 12 20.71 7"/><path d="m7.5 4.27 9 5.15"/>',
                                ],
                                [
                                    'title' => 'Shared visibility',
                                    'desc' => 'See project ownership, active jobs, and release status in one dashboard.',
                                    'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M16 3.128a4 4 0 0 1 0 7.744"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/>',
                                ],
                            ];
                        @endphp

                        @foreach ($features as $f)
                            <article
                                class="group flex items-start gap-4 rounded-xl border border-border/60 bg-card/50 p-4 backdrop-blur transition-all hover:border-border hover:bg-card/80">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg brand-soft-bg text-foreground">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="h-[18px] w-[18px]" aria-hidden="true">
                                        {!! $f['icon'] !!}
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold tracking-tight">{{ $f['title'] }}</h3>
                                    <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                                        {{ $f['desc'] }}
                                    </p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                {{-- Right login panel --}}
                <aside class="w-full lg:justify-self-end">
                    <div class="glass-panel overflow-hidden">
                        <header class="border-b border-border/60 brand-soft-bg px-7 py-6">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                Welcome back
                            </p>
                            <h2 class="mt-2 text-xl font-semibold tracking-tight">
                                Sign in to continue
                            </h2>
                            <p class="mt-1.5 text-xs leading-relaxed text-muted-foreground">
                                Use your LDAP username and password to access the deployment portal.
                            </p>
                        </header>

                        <form id="loginForm" action="{{ route('login.user') }}" method="POST" class="space-y-5 px-7 py-6">
                            @csrf
                            <input type="hidden" name="loginmode" :value="loginMode">

                            <div class="space-y-2">
                                <label for="loginusername" class="block text-xs font-semibold">
                                    <span x-show="loginMode === 'ldap'">LDAP Username</span>
                                    <span x-show="loginMode === 'local'">Email or Local Username</span>
                                </label>
                                <input id="loginusername" name="loginusername" type="text" autocomplete="username" required
                                    :placeholder="loginMode === 'ldap' ? 'Enter your LDAP username' : 'Enter your email or local username'"
                                    value="{{ old('loginusername') }}"
                                    :disabled="isLoginBlocked()"
                                    class="h-11 w-full rounded-lg border border-border/70 bg-background/40 px-3.5 text-sm placeholder:text-muted-foreground/70 focus:border-primary/60 focus:outline-none focus:ring-2 focus:ring-ring/30">
                                <p class="text-[11px] text-muted-foreground" x-show="loginMode === 'local'">
                                    Use this mode to sign in with a registered non-LDAP test account.
                                </p>
                                @error('loginusername')
                                    <p class="text-xs text-failed">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="loginpassword" class="block text-xs font-semibold">
                                    Password
                                </label>

                                <div class="relative">
                                    <input id="loginpassword" name="loginpassword" type="password" required
                                        autocomplete="current-password" placeholder="Enter your password"
                                        :disabled="isLoginBlocked()"
                                        class="h-11 w-full rounded-lg border border-border/70 bg-background/40 px-3.5 pr-12 text-sm placeholder:text-muted-foreground/70 focus:border-primary/60 focus:outline-none focus:ring-2 focus:ring-ring/30">

                                    <button id="togglePassword" type="button" aria-label="Show password"
                                        :disabled="isLoginBlocked()"
                                        class="absolute right-1.5 top-1/2 -translate-y-1/2 inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted/40 hover:text-foreground">
                                        <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"
                                            aria-hidden="true">
                                            <path
                                                d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4"
                                            width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            aria-hidden="true">
                                            <path d="M3 3l18 18" />
                                            <path d="M10.58 10.58a2 2 0 1 0 2.83 2.83" />
                                            <path
                                                d="M9.88 5.09A10.94 10.94 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-4.21 5.17" />
                                            <path
                                                d="M6.61 6.61A13.18 13.18 0 0 0 2 12s3.5 7 10 7a9.77 9.77 0 0 0 5.39-1.61" />
                                        </svg>
                                    </button>
                                </div>

                                @error('loginpassword')
                                    <p class="text-xs text-failed">{{ $message }}</p>
                                @enderror
                            </div>

                            <button id="loginSubmitButton" type="submit" :disabled="isLoginBlocked()"
                                class="brand-gradient-bg mt-2 h-11 w-full rounded-lg text-sm font-semibold tracking-tight shadow-md transition-transform hover:scale-[1.01] active:scale-[0.99] text-[hsl(var(--on-brand))]">
                                Log in
                            </button>
                        </form>

                        <footer class="border-t border-border/60 bg-card/30 px-7 py-4" x-show="loginMode === 'local'">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                {{--
                                <p class="text-xs text-muted-foreground">
                                    No LDAP account?
                                </p>
                                --}}
                                <button type="button"
                                    onclick="document.getElementById('registerModal').classList.remove('hidden')"
                                    class="text-xs font-semibold tracking-tight text-foreground underline-offset-4 hover:underline">
                                    Register a local user for testing →
                                </button>
                            </div>
                        </footer>
                    </div>

                    <p class="mt-4 text-center text-[11px] text-muted-foreground">
                        Use the same LDAP username you use elsewhere in the company workspace.
                    </p>
                </aside>
            </div>
        </div>

        <div
            x-show="sessionConflictVisible"
            x-cloak
            class="fixed inset-0 z-40 flex items-center justify-center bg-background/70 px-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-2xl border border-border bg-card shadow-lg">
                <div class="border-b border-border/60 px-6 py-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">Session conflict</p>
                    <h2 class="mt-2 text-xl font-semibold tracking-tight text-foreground">A session is already active</h2>
                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                        Currently Logged in as:
                        <span class="font-semibold text-foreground" x-text="sessionConflictUsername"></span>
                    </p>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <button
                        type="button"
                        @click="continueToLogin()"
                        :disabled="isRevokingSession"
                        class="inline-flex h-10 w-full items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span x-show="!isRevokingSession">Continue to Login</span>
                        <span x-show="isRevokingSession">Revoking session...</span>
                    </button>

                    <button
                        type="button"
                        @click="continueAsActiveUser()"
                        :disabled="isRevokingSession"
                        class="inline-flex h-10 w-full items-center justify-center rounded-md brand-gradient-bg px-4 text-sm font-medium text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span>Continue as </span>
                        <span class="ml-1" x-text="sessionConflictUsername"></span>
                    </button>

                    <p x-show="sessionConflictError" x-text="sessionConflictError" class="text-xs text-failed"></p>
                </div>
            </div>
        </div>
    </section>

    {{-- Register modal --}}
    <div id="registerModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-background/70 px-4 backdrop-blur-sm">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-border bg-card shadow-lg">
            <div class="brand-soft-bg flex items-start justify-between gap-4 border-b border-border/60 px-6 py-5">
                <div>
                    <h2 class="text-base font-semibold tracking-tight text-foreground">Register User</h2>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Create a local account for environments that are not using LDAP.
                    </p>
                </div>
                <button type="button" onclick="document.getElementById('registerModal').classList.add('hidden')"
                    class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-background/80 hover:text-foreground">
                    <span class="sr-only">Close</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('register.user') }}" method="POST" class="space-y-4 px-6 py-5">
                @csrf

                <div class="space-y-2">
                    <label for="register-name" class="text-xs font-semibold text-foreground">Username</label>
                    <input id="register-name" name="name" type="text" placeholder="Enter full name"
                        class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                </div>

                <div class="space-y-2">
                    <label for="register-email" class="text-xs font-semibold text-foreground">Email</label>
                    <input id="register-email" name="email" type="email" placeholder="Enter email"
                        class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                </div>

                <div class="space-y-2">
                    <label for="register-password" class="text-xs font-semibold text-foreground">Password</label>
                    <input id="register-password" name="password" type="password" placeholder="Enter password"
                        class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-border/60 pt-4 sm:flex-row sm:justify-end">
                    <button type="button" onclick="document.getElementById('registerModal').classList.add('hidden')"
                        class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 text-xs font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                        Cancel
                    </button>
                    <button type="submit"
                        class="inline-flex h-9 items-center justify-center rounded-md brand-gradient-bg px-4 text-xs font-semibold text-[hsl(var(--on-brand))] shadow-md transition hover:brightness-[1.03]">
                        Register
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function authLoginPageData({
            initialLoginMode,
            sessionStatusUrl,
            revokeSessionUrl,
            homeUrl,
        }) {
            return {
                loginMode: initialLoginMode,
                sessionStatusUrl,
                revokeSessionUrl,
                homeUrl,
                sessionConflictVisible: false,
                sessionConflictUsername: '',
                sessionConflictError: '',
                sessionCheckPending: false,
                isRevokingSession: false,

                init() {
                    this.initSessionConflict();
                },

                isLoginBlocked() {
                    return this.sessionCheckPending || this.sessionConflictVisible || this.isRevokingSession;
                },

                async initSessionConflict() {
                    this.sessionCheckPending = true;
                    this.sessionConflictError = '';

                    try {
                        const response = await fetch(this.sessionStatusUrl, {
                            headers: {
                                Accept: 'application/json',
                            },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            throw new Error('Could not verify your current session.');
                        }

                        const payload = await response.json();

                        if (payload.active) {
                            this.sessionConflictUsername = payload.username || 'Current user';
                            this.sessionConflictVisible = true;
                        }
                    } catch (error) {
                        this.sessionConflictError = error.message || 'Could not verify your current session.';
                    } finally {
                        this.sessionCheckPending = false;
                    }
                },

                continueAsActiveUser() {
                    window.location.href = this.homeUrl;
                },

                async continueToLogin() {
                    this.isRevokingSession = true;
                    this.sessionConflictError = '';

                    try {
                        const csrfToken = document.querySelector('#loginForm input[name="_token"]')?.value;
                        const response = await fetch(this.revokeSessionUrl, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': csrfToken || '',
                            },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            throw new Error('Could not revoke the active session.');
                        }

                        const payload = await response.json();
                        const csrfInput = document.querySelector('#loginForm input[name="_token"]');
                        if (csrfInput && payload.csrfToken) {
                            csrfInput.value = payload.csrfToken;
                        }

                        this.sessionConflictVisible = false;
                        this.sessionConflictUsername = '';
                    } catch (error) {
                        this.sessionConflictError = error.message || 'Could not revoke the active session.';
                    } finally {
                        this.isRevokingSession = false;
                    }
                },
            };
        }

        function themeToggleData() {
            return {
                theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                toggle() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                    window.applyThemePreference(this.theme);
                },
            };
        }

        const passwordInput = document.getElementById('loginpassword');
        const toggleButton = document.getElementById('togglePassword');
        const eyeOpen = document.getElementById('eyeOpen');
        const eyeClosed = document.getElementById('eyeClosed');
        const loginForm = document.getElementById('loginForm');
        const loginSubmitButton = document.getElementById('loginSubmitButton');
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

        if (loginForm && loginSubmitButton) {
            loginForm.addEventListener('submit', function (event) {
                if (loginForm.hasAttribute('x-data')) {
                    // No-op placeholder so Alpine can mount before submit guard runs.
                }

                const alpineState = Alpine.$data(document.querySelector('section[x-data]'));
                if (alpineState?.isLoginBlocked && alpineState.isLoginBlocked()) {
                    event.preventDefault();
                    return;
                }

                if (loginForm.dataset.submitting === 'true') {
                    event.preventDefault();
                    return;
                }

                loginForm.dataset.submitting = 'true';
                loginSubmitButton.disabled = true;
                loginSubmitButton.textContent = 'Signing in...';
                loginSubmitButton.classList.add('opacity-70', 'cursor-not-allowed');

                if (passwordInput) {
                    passwordInput.readOnly = true;
                }

                const usernameInput = document.getElementById('loginusername');
                if (usernameInput) {
                    usernameInput.readOnly = true;
                }

                if (toggleButton) {
                    toggleButton.disabled = true;
                }
            });
        }

        // Modal: keep `flex` toggling consistent with `hidden`
        function openRegister() {
            registerModal.classList.remove('hidden');
            registerModal.classList.add('flex');
        }
        function closeRegister() {
            registerModal.classList.add('hidden');
            registerModal.classList.remove('flex');
        }

        document.querySelectorAll('[onclick*="registerModal"]').forEach(el => {
            const code = el.getAttribute('onclick') || '';
            el.removeAttribute('onclick');
            el.addEventListener('click', code.includes('remove') ? openRegister : closeRegister);
        });

        if (registerModal) {
            registerModal.addEventListener('click', function (event) {
                if (event.target === registerModal) closeRegister();
            });
        }
    </script>
@endpush
