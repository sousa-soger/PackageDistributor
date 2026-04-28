@extends('layouts.app')

@section('title', 'Repositories')
@section('subtitle', 'GitHub, GitLab, company servers and local repositories.')

@section('topbar_actions')
  <button onclick="window.dispatchEvent(new CustomEvent('open-repo-modal'))"
    class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft hover:shadow-glow hover:brightness-[1.03] active:brightness-95 transition-base h-9 rounded-md px-3"
  >
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
    Connect Repository
  </button>
@endsection

@section('content')
<div class="animate-fade-in px-4 sm:px-6 lg:px-8 py-6 lg:py-8"
     x-data="repositoriesPage()"
     x-init="init()">

  {{-- ── Empty state ─────────────────────────────────────────────────────── --}}
  @if($repositories->isEmpty())
  <div class="section-card p-12 text-center">
    <div class="mx-auto mb-5 h-16 w-16 rounded-2xl brand-soft-bg flex items-center justify-center">
      <svg class="h-8 w-8" style="color:hsl(var(--primary))"
           fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
      </svg>
    </div>
    <p class="text-sm font-semibold" style="color:hsl(var(--foreground))">No repositories yet</p>
    <p class="text-xs mt-1 max-w-sm mx-auto leading-relaxed" style="color:hsl(var(--muted-foreground))">
      Connect your first repository to start generating deployment packages.
      Supports GitHub, GitLab, company servers, and local paths.
    </p>
    <button @click="openModal()"
            class="inline-flex items-center gap-1.5 mt-5 px-4 py-2 rounded-lg text-sm font-semibold text-white transition-base hover:opacity-90"
            style="background:var(--gradient-brand)">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
      </svg>
      Connect Repository
    </button>
  </div>
  @endif

  {{-- ── Repository grid ─────────────────────────────────────────────────── --}}
  @if($repositories->isNotEmpty())
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="repo-grid">
    @foreach($repositories as $repo)
    <div class="section-card p-5 group"
         id="repo-card-{{ $repo->id }}">

      {{-- Card header --}}
      <div class="flex items-start justify-between gap-3 mb-3">

        {{-- Provider icon --}}
        <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center flex-shrink-0"
             style="color:hsl(var(--primary))">
          @if($repo->provider === 'github')
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12"/>
            </svg>
          @elseif($repo->provider === 'gitlab')
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 01-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 014.82 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.49h8.1l2.44-7.51A.42.42 0 0118.6 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.51L23 13.45a.84.84 0 01-.35.94z"/>
            </svg>
          @elseif($repo->provider === 'company-server')
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 12H3a2 2 0 00-2 2v4a2 2 0 002 2h18a2 2 0 002-2v-4a2 2 0 00-2-2h-2M12 2v14m0-14l-4 4m4-4l4 4"/>
            </svg>
          @else
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
          @endif
        </div>

        {{-- Status badge --}}
        @php
          $stCss = match($repo->status) {
            'connected'  => 'background:hsl(var(--success)/0.10);color:hsl(var(--success));border-color:hsl(var(--success)/0.30)',
            'expired'    => 'background:hsl(var(--queued)/0.10);color:hsl(var(--queued));border-color:hsl(var(--queued)/0.30)',
            'needs-auth' => 'background:hsl(var(--failed)/0.10);color:hsl(var(--failed));border-color:hsl(var(--failed)/0.30)',
            default      => 'background:hsl(var(--inactive)/0.10);color:hsl(var(--inactive));border-color:hsl(var(--inactive)/0.30)',
          };
          $stLabel = match($repo->status) {
            'connected'  => 'Connected',
            'expired'    => 'Expired',
            'needs-auth' => 'Needs auth',
            default      => ucfirst($repo->status),
          };
        @endphp
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border" style="{{ $stCss }}">
          {{ $stLabel }}
        </span>
      </div>

      {{-- Repo name --}}
      <div class="text-sm font-semibold truncate" style="color:hsl(var(--foreground))">
        {{ $repo->label }}
      </div>
      <div class="text-xs mt-0.5" style="color:hsl(var(--muted-foreground))">
        {{ ucfirst(str_replace('-', ' ', $repo->provider)) }}
        @if($repo->server_host) · {{ $repo->server_host }} @endif
      </div>

      {{-- Metadata pills --}}
      <div class="mt-3 flex flex-wrap gap-1.5">
        <span class="text-[10px] font-mono px-2 py-0.5 rounded"
              style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))">
          {{ $repo->branch_count }} branches
        </span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded"
              style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))">
          {{ $repo->tag_count }} tags
        </span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded"
              style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))">
          default · {{ $repo->default_branch }}
        </span>
      </div>

      {{-- Actions (shown on hover) --}}
      <div class="mt-4 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-base">
        {{-- Sync --}}
        <button
          @click="syncRepo({{ $repo->id }})"
          :disabled="syncing === {{ $repo->id }}"
          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-base"
          style="background:hsl(var(--secondary));color:hsl(var(--foreground))"
          title="Sync branches & tags">
          <svg class="h-3.5 w-3.5" :class="syncing === {{ $repo->id }} ? 'animate-spin' : ''"
               fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          <span x-text="syncing === {{ $repo->id }} ? 'Syncing…' : 'Sync'"></span>
        </button>

        {{-- Remove --}}
        <button
          @click="removeRepo({{ $repo->id }})"
          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-base"
          style="background:hsl(var(--failed)/0.08);color:hsl(var(--failed))"
          title="Remove repository">
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
          </svg>
          Remove
        </button>
      </div>
    </div>
    @endforeach

    {{-- "Add another" card --}}
    <button @click="openModal()"
            class="section-card p-5 border-dashed flex flex-col items-center justify-center gap-3 min-h-[160px] transition-spring hover:shadow-soft cursor-pointer w-full text-center"
            style="border-style:dashed;border-color:hsl(var(--border))">
      <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center"
           style="color:hsl(var(--primary))">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
      </div>
      <div>
        <p class="text-sm font-semibold" style="color:hsl(var(--foreground))">Connect Repository</p>
        <p class="text-xs mt-0.5" style="color:hsl(var(--muted-foreground))">GitHub, GitLab, or custom server</p>
      </div>
    </button>
  </div>
  @endif

  {{-- ═══════════════════════════════════════════════════════════════════════
       CONNECT REPOSITORY MODAL
  ════════════════════════════════════════════════════════════════════════ --}}
  <div x-show="modal" x-cloak
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 flex items-center justify-center p-4"
       style="background:hsl(220 30% 5% / 0.55);backdrop-filter:blur(4px)"
       @click.self="closeModal()">

    <div class="w-full max-w-xl animate-slide-up grid gap-0 overflow-hidden relative"
         style="background:hsl(var(--card));border-radius:calc(var(--radius) * 1.5);border:1px solid hsl(var(--border)/0.7);box-shadow:var(--shadow-lg)"
         role="dialog" aria-modal="true">

      {{-- Header & Progress --}}
      <div class="brand-soft-bg px-6 py-5 border-b border-border/60">
        <div class="flex flex-col space-y-1.5 sm:text-left">
          <h2 class="font-semibold tracking-tight text-xl" style="color:hsl(var(--foreground))">Connect Repository</h2>
          <p class="text-sm" style="color:hsl(var(--muted-foreground))">
            Cybix Deployer reads code only when you build a package. Credentials are stored encrypted.
          </p>
        </div>
        
        <div class="mt-4 flex items-center gap-2">
          <template x-for="(s, i) in ['provider', 'auth', 'details', 'done']" :key="i">
            <div class="flex items-center gap-2 flex-1">
              <div class="h-1.5 flex-1 rounded-full transition-colors"
                   :style="( ['provider', 'auth', 'details', 'done'].indexOf(step === 'verifying' ? 'details' : step) >= i ) ? 'background:var(--gradient-brand)' : 'background:hsl(var(--border))'">
              </div>
            </div>
          </template>
        </div>
      </div>

      {{-- Body --}}
      <div class="px-6 py-5 max-h-[60vh] overflow-y-auto">
        
        {{-- STEP: provider --}}
        <template x-if="step === 'provider'">
          <div class="space-y-2">
            <p class="text-sm mb-3" style="color:hsl(var(--muted-foreground))">Pick a source for your repository.</p>
            <template x-for="p in providers" :key="p.id">
              <button @click="pickProvider(p)"
                      class="w-full text-left flex items-center gap-3 rounded-xl border p-3 hover:shadow-soft transition-base group"
                      style="background:hsl(var(--card));border-color:hsl(var(--border)/0.7)"
                      onmouseenter="this.style.borderColor='hsl(var(--primary)/0.4)'"
                      onmouseleave="this.style.borderColor='hsl(var(--border)/0.7)'">
                <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center shrink-0 transition-base group-hover:-translate-y-0.5"
                     style="color:hsl(var(--primary))" x-html="p.icon">
                </div>
                <div class="min-w-0 flex-1">
                  <div class="text-sm font-semibold" style="color:hsl(var(--foreground))" x-text="p.name"></div>
                  <div class="text-xs truncate" style="color:hsl(var(--muted-foreground))" x-text="p.description"></div>
                </div>
                <div class="text-[10px] font-medium uppercase tracking-wider" style="color:hsl(var(--muted-foreground))" x-text="p.authMethod"></div>
              </button>
            </template>
          </div>
        </template>

        {{-- STEP: auth --}}
        <template x-if="step === 'auth' && provider">
          <div class="space-y-4">
            <div class="flex items-center gap-3 rounded-xl brand-soft-bg p-3 border border-border/60">
              <div class="h-10 w-10 rounded-lg flex items-center justify-center"
                   style="background:hsl(var(--card));color:hsl(var(--primary))" x-html="provider.icon"></div>
              <div class="flex-1">
                <div class="text-sm font-semibold" style="color:hsl(var(--foreground))" x-text="provider.name"></div>
                <div class="text-xs" style="color:hsl(var(--muted-foreground))" x-text="provider.authLabel"></div>
              </div>
            </div>

            <template x-if="provider.authMethod === 'github'">
              <div class="space-y-2" role="radiogroup">
                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer transition-colors"
                       :style="authMethod === 'oauth' ? 'border-color:hsl(var(--primary)/0.4);background:hsl(var(--accent))' : 'border-color:hsl(var(--border)/0.7)'">
                  <input type="radio" value="oauth" x-model="authMethod" class="mt-1" />
                  <div class="flex-1">
                    <div class="text-sm font-medium flex items-center gap-2" style="color:hsl(var(--foreground))">
                      <svg class="h-4 w-4" style="color:hsl(var(--success))" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg> 
                      Sign in with GitHub
                    </div>
                    <div class="text-xs mt-1" style="color:hsl(var(--muted-foreground))">
                      Cybix will request <code class="font-mono bg-background px-1 rounded border">repo</code> read access. You can revoke any time.
                    </div>
                  </div>
                </label>

                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer transition-colors"
                       :style="authMethod === 'pat' ? 'border-color:hsl(var(--primary)/0.4);background:hsl(var(--accent))' : 'border-color:hsl(var(--border)/0.7)'">
                  <input type="radio" value="pat" x-model="authMethod" class="mt-1" />
                  <div class="flex-1 space-y-2">
                    <div class="text-sm font-medium flex items-center gap-2" style="color:hsl(var(--foreground))">
                      <svg class="h-4 w-4" style="color:hsl(var(--primary))" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                      Personal Access Token
                    </div>
                    <input type="password" x-model="token" placeholder="ghp_••••••••••••"
                           class="flex h-9 w-full rounded-md border text-sm shadow-sm transition-colors focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                           style="background:transparent;border-color:hsl(var(--input, var(--border)));padding-left:0.75rem;padding-right:0.75rem"
                           :disabled="authMethod !== 'pat'">
                  </div>
                </label>
              </div>
            </template>

            <template x-if="provider.authMethod === 'gitlab'">
              <div class="space-y-2">
                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer transition-colors"
                       :style="authMethod === 'oauth' ? 'border-color:hsl(var(--primary)/0.4);background:hsl(var(--accent))' : 'border-color:hsl(var(--border)/0.7)'">
                  <input type="radio" value="oauth" x-model="authMethod" class="mt-1" />
                  <div class="flex-1">
                    <div class="text-sm font-medium flex items-center gap-2" style="color:hsl(var(--foreground))">
                      <svg class="h-4 w-4" style="color:hsl(var(--success))" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg> 
                      Sign in with GitLab
                    </div>
                    <div class="text-xs mt-1" style="color:hsl(var(--muted-foreground))">
                      Cybix will request <code class="font-mono bg-background px-1 rounded border">repo</code> read access. You can revoke any time.
                    </div>
                  </div>
                </label>

                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer transition-colors"
                       :style="authMethod === 'pat' ? 'border-color:hsl(var(--primary)/0.4);background:hsl(var(--accent))' : 'border-color:hsl(var(--border)/0.7)'">
                  <input type="radio" value="pat" x-model="authMethod" class="mt-1" />
                  <div class="flex-1 space-y-2">
                    <div class="text-sm font-medium flex items-center gap-2" style="color:hsl(var(--foreground))">
                      <svg class="h-4 w-4" style="color:hsl(var(--primary))" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                      GitLab Personal Access Token (glpat)
                    </div>
                    <input type="password" x-model="token" placeholder="glpat_••••••••••••"
                           class="flex h-9 w-full rounded-md border text-sm shadow-sm transition-colors focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                           style="background:transparent;border-color:hsl(var(--input, var(--border)));padding-left:0.75rem;padding-right:0.75rem"
                           :disabled="authMethod !== 'pat'">
                  </div>
                </label>
              </div>
            </template>

            <template x-if="provider.authMethod === 'ssh'">
              <div class="space-y-3">
                <div class="space-y-2">
                  <label class="text-sm font-medium leading-none" style="color:hsl(var(--foreground))">Host</label>
                  <input type="text" x-model="host" placeholder="git.company.internal"
                         class="flex h-9 w-full rounded-md border text-sm shadow-sm transition-colors focus-visible:outline-none"
                         style="background:transparent;border-color:hsl(var(--input, var(--border)));padding-left:0.75rem;padding-right:0.75rem">
                </div>
                <div class="rounded-lg border border-dashed p-3" style="border-color:hsl(var(--border));background:hsl(var(--secondary)/0.4)">
                  <p class="text-xs font-medium mb-1" style="color:hsl(var(--foreground))">Add deploy key</p>
                  <p class="text-[11px] mb-2" style="color:hsl(var(--muted-foreground))">
                    Add the following public key to your repository's deploy keys.
                  </p>
                  <code class="block text-[10px] font-mono rounded p-2 break-all border"
                        style="background:hsl(var(--background));border-color:hsl(var(--border))">
                    ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI...cybix-deployer
                  </code>
                </div>
              </div>
            </template>

            <template x-if="provider.authMethod === 'path'">
              <div class="rounded-lg border p-3 space-y-1" style="border-color:hsl(var(--border)/0.7);background:hsl(var(--secondary)/0.3)">
                <div class="text-sm font-medium flex items-center gap-2" style="color:hsl(var(--foreground))">
                  <svg class="h-4 w-4" style="color:hsl(var(--primary))" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                  Local agent detected
                </div>
                <div class="text-xs" style="color:hsl(var(--muted-foreground))">
                  The Cybix agent is running on this machine. Continue to point at a folder.
                </div>
              </div>
            </template>
          </div>
        </template>

        {{-- STEP: details --}}
        <template x-if="step === 'details' && provider">
          <div class="space-y-4">
            <div class="space-y-2">
              <label class="text-sm font-medium leading-none" style="color:hsl(var(--foreground))">Project Name</label>
              <select x-model="projectId" class="flex h-9 w-full items-center justify-between rounded-md border text-sm shadow-sm focus:outline-none"
                      style="background:transparent;border-color:hsl(var(--input, var(--border)));padding-left:0.75rem;padding-right:0.75rem;color:hsl(var(--foreground))">
                <option value="">Select a project</option>
                <option value="atlas">Atlas Web</option>
                <option value="helios">Helios API</option>
              </select>
            </div>

            <template x-if="provider.authMethod === 'path'">
              <div class="space-y-2">
                <label class="text-sm font-medium leading-none" style="color:hsl(var(--foreground))">Local folder path</label>
                <input type="text" x-model="localPath" placeholder="/Users/you/code/my-app"
                       class="flex h-9 w-full rounded-md border text-sm shadow-sm disabled:cursor-not-allowed disabled:opacity-50"
                       style="background:transparent;border-color:hsl(var(--input, var(--border)));padding-left:0.75rem;padding-right:0.75rem;color:hsl(var(--foreground))">
              </div>
            </template>
            
            <template x-if="provider.authMethod !== 'path'">
              <div class="space-y-2">
                <label class="text-sm font-medium leading-none" style="color:hsl(var(--foreground))">Repository URL</label>
                <input type="text" x-model="repoUrl"
                       :placeholder="provider.id === 'github' ? 'https://github.com/org/repo' : provider.id === 'gitlab' ? 'https://gitlab.com/group/repo' : 'git@git.company.internal:group/repo.git'"
                       class="flex h-9 w-full rounded-md border text-sm shadow-sm disabled:cursor-not-allowed disabled:opacity-50"
                       style="background:transparent;border-color:hsl(var(--input, var(--border)));padding-left:0.75rem;padding-right:0.75rem;color:hsl(var(--foreground))">
              </div>
            </template>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none" style="color:hsl(var(--foreground))">Default branch</label>
              <input type="text" x-model="defaultBranch" placeholder="main"
                     class="flex h-9 w-full rounded-md border text-sm shadow-sm"
                     style="background:transparent;border-color:hsl(var(--input, var(--border)));padding-left:0.75rem;padding-right:0.75rem;color:hsl(var(--foreground))">
              <p class="text-xs" style="color:hsl(var(--muted-foreground))">
                Used as the base when nothing is selected during package creation.
              </p>
            </div>
          </div>
        </template>

        {{-- STEP: verifying --}}
        <template x-if="step === 'verifying'">
          <div class="py-10 flex flex-col items-center justify-center gap-3 text-center">
            <svg class="h-8 w-8 animate-spin" style="color:hsl(var(--primary))" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            <div class="text-sm font-medium" style="color:hsl(var(--foreground))">Verifying access…</div>
            <div class="text-xs" style="color:hsl(var(--muted-foreground))">
              Authenticating, fetching branches and tags.
            </div>
          </div>
        </template>

        {{-- STEP: done --}}
        <template x-if="step === 'done'">
          <div class="py-8 flex flex-col items-center justify-center gap-3 text-center">
            <div class="h-12 w-12 rounded-full flex items-center justify-center" style="background:hsl(var(--success)/0.15);color:hsl(var(--success))">
              <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <div class="text-base font-semibold" style="color:hsl(var(--foreground))">Repository connected</div>
            <div class="text-xs max-w-sm" style="color:hsl(var(--muted-foreground))">
              Cybix discovered branches and tags. You can now build a package from this repository.
            </div>
          </div>
        </template>
      </div>

      {{-- Footer --}}
      <div class="flex flex-col-reverse sm:flex-row sm:space-x-2 px-6 py-4 border-t gap-2 sm:justify-between"
           style="border-color:hsl(var(--border)/0.6);background:hsl(var(--secondary)/0.5)">
          
          <template x-if="step !== 'provider' && step !== 'done' && step !== 'verifying'">
              <button @click="step = (step === 'details' ? 'auth' : 'provider')"
                      class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium transition-base h-9 px-3"
                      style="color:hsl(var(--foreground))"
                      onmouseenter="this.style.background='hsl(var(--accent))'"
                      onmouseleave="this.style.background='transparent'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m15 18-6-6 6-6"/></svg>
                Back
              </button>
          </template>
          
          <template x-if="step === 'provider' || step === 'done' || step === 'verifying'">
              <span></span>
          </template>

          <div class="flex items-center gap-2">
              <button @click="closeModal()"
                      class="inline-flex items-center justify-center gap-2 rounded-md border text-sm font-medium transition-base h-9 px-3"
                      style="background:hsl(var(--background));border-color:hsl(var(--border));color:hsl(var(--foreground))"
                      onmouseenter="this.style.background='hsl(var(--accent))'"
                      onmouseleave="this.style.background='hsl(var(--background))'">
                  <span x-text="step === 'done' ? 'Close' : 'Cancel'"></span>
              </button>

              <template x-if="step === 'auth'">
                  <button @click="step = 'details'"
                          :disabled="!canSubmitAuth"
                          class="brand-gradient-bg shadow-soft inline-flex items-center justify-center rounded-md text-sm font-medium transition-base h-9 px-3 disabled:opacity-50 disabled:cursor-not-allowed"
                          style="color:hsl(var(--primary-foreground))"
                          onmouseenter="if(!this.disabled) this.classList.add('shadow-md')"
                          onmouseleave="this.classList.remove('shadow-md')">
                      Continue
                  </button>
              </template>

              <template x-if="step === 'details'">
                  <button @click="handleVerify()"
                          :disabled="!canSubmitDetails"
                          class="brand-gradient-bg shadow-soft inline-flex items-center justify-center rounded-md text-sm font-medium transition-base h-9 px-3 disabled:opacity-50 disabled:cursor-not-allowed"
                          style="color:hsl(var(--primary-foreground))"
                          onmouseenter="if(!this.disabled) this.classList.add('shadow-md')"
                          onmouseleave="this.classList.remove('shadow-md')">
                      Verify & connect
                  </button>
              </template>

              <template x-if="step === 'done'">
                  <button @click="handleFinish()"
                          class="brand-gradient-bg shadow-soft inline-flex items-center justify-center rounded-md text-sm font-medium transition-base h-9 px-3"
                          style="color:hsl(var(--primary-foreground))"
                          onmouseenter="this.classList.add('shadow-md')"
                          onmouseleave="this.classList.remove('shadow-md')">
                      Done
                  </button>
              </template>
          </div>
      </div>

      {{-- Close Cross --}}
      <button @click="closeModal()" type="button" class="absolute right-4 top-4 rounded-sm opacity-70 transition-opacity hover:opacity-100" style="color:hsl(var(--muted-foreground))">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
  </div>
{{-- /modal overlay --}}

</div>{{-- /page --}}

@endsection

@push('scripts')
<script>
function repositoriesPage() {
  return {
    // ── State ──────────────────────────────────────────────────────────────
    modal:   false,
    step:    'provider', // 'provider' | 'auth' | 'details' | 'verifying' | 'done'
    provider: null,
    loading: false,
    syncing: null,
    error:   '',
    
    // Form fields
    authMethod: 'oauth',
    token: '',
    host: '',
    localPath: '',
    projectId: '',
    repoUrl: '',
    defaultBranch: 'main',

    providers: [
      {
        id: 'github',
        name: 'GitHub',
        description: 'Connect a public or private GitHub repository via OAuth.',
        authMethod: 'github',
        authLabel: 'OAuth (recommended) or Personal Access Token',
        icon: `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"></path><path d="M9 18c-4.51 2-5-2-7-2"></path></svg>`,
      },
      {
        id: 'gitlab',
        name: 'GitLab',
        description: 'Connect a GitLab.com or self-hosted GitLab repository.',
        authMethod: 'gitlab',
        authLabel: 'Project Access Token',
        icon: `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"></path></svg>`,
      },
      {
        id: 'company-server',
        name: 'Company Server',
        description: 'Connect a self-hosted Git server over SSH.',
        authMethod: 'ssh',
        authLabel: 'SSH key + host',
        icon: `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect><rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect><line x1="6" x2="6.01" y1="6" y2="6"></line><line x1="6" x2="6.01" y1="18" y2="18"></line></svg>`,
      },
      {
        id: 'local-pc',
        name: 'Local PC',
        description: 'Index a local repository folder via the Cybix agent.',
        authMethod: 'path',
        authLabel: 'Local agent + folder path',
        icon: `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" x2="2" y1="12" y2="12"></line><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path><line x1="6" x2="6.01" y1="16" y2="16"></line><line x1="10" x2="10.01" y1="16" y2="16"></line></svg>`,
      },
    ],

    // ── Computed Props  ───────────────────────────────────────────────────
    get canSubmitAuth() {
        if (!this.provider) return false;
        if (this.provider.authMethod === 'oauth' || this.provider.authMethod === 'path') return true;
        return !!this.token || this.provider.authMethod === 'ssh';
    },

    get canSubmitDetails() {
        if (!this.projectId || !this.defaultBranch) return false;
        if (this.provider?.authMethod === 'path') return !!this.localPath;
        if (this.provider?.authMethod === 'ssh') return !!this.host && !!this.repoUrl;
        return !!this.repoUrl;
    },

    // ── Lifecycle ─────────────────────────────────────────────────────────
    init() {
      if (sessionStorage.getItem('flash_toast_msg')) {
        const msg  = sessionStorage.getItem('flash_toast_msg');
        const type = sessionStorage.getItem('flash_toast_type');
        sessionStorage.removeItem('flash_toast_msg');
        sessionStorage.removeItem('flash_toast_type');
        setTimeout(() => window.dispatchEvent(
          new CustomEvent('toast', { detail: { type, message: msg } })
        ), 50);
      }

      window.addEventListener('open-repo-modal', () => this.openModal());

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.modal && this.step !== 'verifying') {
            this.closeModal(false);
        }
      });
    },

    // ── Handlers ──────────────────────────────────────────────────────────
    openModal() {
      this.reset();
      this.modal = true;
    },
    closeModal(next = false) {
      if (!next) setTimeout(() => this.reset(), 200);
      this.modal = next;
    },
    reset() {
      this.step = 'provider';
      this.provider = null;
      this.projectId = '';
      this.repoUrl = '';
      this.defaultBranch = 'main';
      this.token = '';
      this.host = '';
      this.localPath = '';
      this.authMethod = 'oauth';
      this.error = '';
      this.loading = false;
    },
    pickProvider(p) {
        this.provider = p;
        this.step = 'auth';
    },
    handleVerify() {
        this.step = 'verifying';
        setTimeout(() => {
            this.step = 'done';
        }, 1600);
    },
    handleFinish() {
        this.closeModal(false);
        sessionStorage.setItem('flash_toast_msg', 'Repository connected successfully.');
        sessionStorage.setItem('flash_toast_type', 'success');
        window.location.reload();
    },

    // ── Remote Sync & Remove ──────────────────────────────────────────────
    async syncRepo(id) {
      this.syncing = id;
      try {
        const resp = await fetch(`/repositories/${id}/sync`, {
          method:  'POST',
          headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        });
        if (resp.ok) {
          sessionStorage.setItem('flash_toast_msg',  'Repository synced successfully.');
          sessionStorage.setItem('flash_toast_type', 'success');
          window.location.reload();
        }
      } finally {
        this.syncing = null;
      }
    },
    async removeRepo(id) {
      if (!confirm('Remove this repository?')) return;
      const resp = await fetch(`/repositories/${id}`, {
        method:  'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
      });
      if (resp.ok) {
        sessionStorage.setItem('flash_toast_msg',  'Repository removed.');
        sessionStorage.setItem('flash_toast_type', 'success');
        window.location.reload();
      }
    },
  };
}
</script>
@endpush
