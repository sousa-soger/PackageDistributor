@extends('layouts.app')

@section('title', 'Profile & Connections')
@section('subtitle', 'Manage your profile and third-party VCS connections.')

@section('content')
<div class="max-w-4xl space-y-5">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-xl border px-4 py-3 text-sm"
             style="border-color:hsl(var(--success)/0.30);color:hsl(var(--success));background:hsl(var(--success)/0.07)">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border px-4 py-3 text-sm"
             style="border-color:hsl(var(--failed)/0.30);color:hsl(var(--failed));background:hsl(var(--failed)/0.07)">
            {{ session('error') }}
        </div>
    @endif

    {{-- Profile card --}}
    <section class="section-card p-6">
        <div class="flex items-center gap-4">
            @if($user->avatar_url)
                <img src="{{ $user->avatar_url }}"
                     alt="{{ $user->name }}"
                     class="h-14 w-14 rounded-full object-cover ring-2"
                     style="ring-color:hsl(var(--border))">
            @else
                <div class="h-14 w-14 rounded-full brand-gradient-bg text-[hsl(var(--on-brand))] flex items-center justify-center text-lg font-semibold shrink-0">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
            @endif
            <div>
                <h2 class="text-base font-semibold">{{ $user->name }}</h2>
                <p class="text-sm text-muted-foreground">{{ $user->email }}</p>
                @if($user->ldap_username)
                    <p class="text-xs text-muted-foreground mt-0.5 font-mono">{{ $user->ldap_username }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- GitHub --}}
    <section class="section-card p-6 space-y-5">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="h-11 w-11 rounded-lg brand-soft-bg flex items-center justify-center text-primary shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/>
                        <path d="M9 18c-4.51 2-5-2-7-2"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold">GitHub</h3>
                    <p class="text-xs text-muted-foreground">Personal Access Tokens (classic or fine-grained) with repo + workflow scopes.</p>
                </div>
            </div>
            @if($githubConnected)
                <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border bg-success/10 text-success border-success/30 shrink-0">Connected</span>
            @else
                <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border bg-muted/60 text-muted-foreground border-border/60 shrink-0">Not connected</span>
            @endif
        </div>

        {{-- OAuth row --}}
        <div class="rounded-lg border border-border/60 p-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>
                        </svg>
                        OAuth connection
                    </div>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        @if($githubConnected)
                            Signed in as {{ $githubUsername }}
                            @if($githubConnectedAt) · {{ $githubConnectedAt }}@endif
                        @else
                            Not connected via OAuth.
                        @endif
                    </p>
                </div>
                @if($githubConnected)
                    <form method="POST" action="{{ route('github.oauth.disconnect') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3 text-sm font-medium transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 17H7A5 5 0 0 1 7 7"/><path d="M15 7h2a5 5 0 0 1 4 8"/><line x1="8" x2="12" y1="12" y2="12"/><line x1="2" x2="22" y1="2" y2="22"/>
                            </svg>
                            Disconnect
                        </button>
                    </form>
                @else
                    <a href="{{ route('github.oauth.redirect') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-md brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft h-9 px-3 text-sm font-medium transition-base hover:brightness-[1.03]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" x2="16" y1="12" y2="12"/>
                        </svg>
                        Connect GitHub
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- GitLab --}}
    <section class="section-card p-6 space-y-5">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="h-11 w-11 rounded-lg brand-soft-bg flex items-center justify-center text-primary shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold">GitLab</h3>
                    <p class="text-xs text-muted-foreground">Personal Access Tokens with api, read_repository and write_repository scopes.</p>
                </div>
            </div>
            @if($gitlabConnected)
                <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border bg-success/10 text-success border-success/30 shrink-0">Connected</span>
            @else
                <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border bg-muted/60 text-muted-foreground border-border/60 shrink-0">Not connected</span>
            @endif
        </div>

        {{-- OAuth row --}}
        <div class="rounded-lg border border-border/60 p-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>
                        </svg>
                        OAuth connection
                    </div>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        @if($gitlabConnected)
                            Signed in as @{{ $gitlabUsername }}
                            @if($gitlabConnectedAt) · {{ $gitlabConnectedAt }}@endif
                        @else
                            Not connected via OAuth.
                        @endif
                    </p>
                </div>
                @if($gitlabConnected)
                    <form method="POST" action="{{ route('gitlab.oauth.disconnect') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3 text-sm font-medium transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 17H7A5 5 0 0 1 7 7"/><path d="M15 7h2a5 5 0 0 1 4 8"/><line x1="8" x2="12" y1="12" y2="12"/><line x1="2" x2="22" y1="2" y2="22"/>
                            </svg>
                            Disconnect
                        </button>
                    </form>
                @else
                    <a href="{{ route('gitlab.oauth.redirect') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-md brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft h-9 px-3 text-sm font-medium transition-base hover:brightness-[1.03]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" x2="16" y1="12" y2="12"/>
                        </svg>
                        Connect GitLab
                    </a>
                @endif
            </div>
        </div>
    </section>

</div>
@endsection