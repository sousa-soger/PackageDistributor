@extends('layouts.app-nosidebar')

@section('title', 'Loggin')

@section('content')
    <section class="min-h-screen px-6 py-4 lg:px-10">
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-[1600px] flex-col justify-between">
            <header class="flex items-center">
            <a href="{{ route('home') }}" class="" {{-- class="inline-flex items-center gap-3" --}}>
                <h1 class="text-[22px] leading-8 font-bold text-slate-900">CybixDeployer</h1>
            </a>
            </header>
            <div class="grid flex-1 items-center gap-14 py-10 lg:grid-cols-[1.15fr_0.85fr]">
                <div class="max-w-2xl">
                    <h1 class="text-5xl font-extrabold leading-[1.05] tracking-tight sm:text-6xl">
                        Cybix+ package deployment
                        <span class="block bg-gradient-to-r from-[#E9B3FB] to-[#E24FAC] bg-clip-text text-transparent">
                            now automated. {{-- -#FF5CCC (Vibrant Pink) to #512889 (Spanish Violet) for dark mode for future reference --}}
                        </span>
                    </h1>
                    <!--
                    <p class="mt-8 max-w-xl text-lg leading-8 text-slate-400">
                        Cybix+ pacakge delivery made easy.
                    </p>
                    -->
                    <!-- Icons below the words on loggin page
                    <div class="mt-10 flex flex-wrap gap-4">
                            <x-ui.auth-feature-pill icon="bolt">
                                Fast &amp; Secure
                            </x-ui.auth-feature-pill>

                            <x-ui.auth-feature-pill icon="chart">
                                Real time Tracking
                            </x-ui.auth-feature-pill>
                    </div>
                    -->
                </div>

                <div class="flex justify-center lg:justify-end">
                    <div class="w-full max-w-115 rounded-[28px] border border-black  p-8 {{--shadow-[0_0_0_1px_rgba(255,255,255,0.02),0_20px_80px_rgba(0,0,0,0.55)]--}} backdrop-blur-xl">
                        <div class="text-center">
                            <h2 class="text-4xl font-bold tracking-tight ">Welcome Back</h2>
                            <p class="mt-3 text-base text-slate-400">
                                Please enter your details to log in.
                            </p>
                        </div>

                        <form action="{{ route('login.user') }}" method="POST" class="mt-10 space-y-6">
                            @csrf
                            <div>
                                <label for="email" class="mb-2 block text-sm font-semibold text-slate-400">
                                    LDAP Email
                                </label>

                                <input
                                    name="loginemail"
                                    type="email"
                                    class="w-full rounded-2xl border border-slate-300 bg-slate-100 px-5 py-4 pr-14 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-purple-400 focus:outline-none focus:ring-4 focus:ring-purple-400/10"
                                    placeholder="Enter your email"
                                    value="{{ old('loginemail') }}"
                                >
                                @error('loginemail')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="mb-2 block text-sm font-semibold text-slate-400">
                                    Password
                                </label>

                                <div class="relative">
                                    <input
                                        id="loginpassword"
                                        name="loginpassword"
                                        type="password"
                                        class="w-full rounded-2xl border border-slate-300 bg-slate-100 px-5 py-4 pr-14 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-purple-400 focus:outline-none focus:ring-4 focus:ring-purple-400/10"
                                        placeholder="Enter your password"
                                    >

                                    <button
                                        id="togglePassword"
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex w-14 items-center justify-center text-slate-500 hover:text-slate-700"
                                        aria-label="Show password"
                                    >   
                                        <svg id="eyeOpen" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M3 3l18 18"/>
                                            <path d="M10.58 10.58a2 2 0 102.83 2.83"/>
                                            <path d="M9.88 5.09A10.94 10.94 0 0112 5c6.5 0 10 7 10 7a13.16 13.16 0 01-4.21 5.17"/>
                                            <path d="M6.61 6.61A13.18 13.18 0 002 12s3.5 7 10 7a9.77 9.77 0 005.39-1.61"/>
                                        </svg>

                                        <svg id="eyeClosed" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>

                                    </button>
                                </div>

                                @error('loginpassword')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r 
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
                <button onclick="document.getElementById('registerModal').classList.remove('hidden')" class="px-4 py-2 rounded-lg bg-white outline text-black hover:bg-blue-700 transition">
                    Register User
                </button>
                <div
                    id="registerModal"
                    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                >
                    <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-800">Register User</h2>
                            <button
                                onclick="document.getElementById('registerModal').classList.add('hidden')"
                                class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                            >
                                ✕
                            </button>
                        </div>

                        <form action="{{ route('register.user') }}" method="POST" class="px-6 py-5 space-y-4" >
                            @csrf
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                                <input
                                    name="name"
                                    type="text"
                                    placeholder="Enter full name"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                                <input
                                    name="email"
                                    type="email"
                                    placeholder="Enter email"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                                <input
                                    name="password"
                                    type="password"
                                    placeholder="Enter password"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                            </div>
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    onclick="document.getElementById('registerModal').classList.add('hidden')"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                >
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
                <a href="{{ route('new-package') }}">
                    <x-ui.primary-button>Create New Package</x-ui.primary-button>
                </a>
            </div>
        </x-ui.card>
        </div>
    </div>
</div>
-->
@endsection