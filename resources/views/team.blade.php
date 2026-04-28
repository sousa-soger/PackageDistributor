@extends('layouts.app')

@section('title', 'Teams & Members')
@section('subtitle', 'Group people, assign projects, and decide who can ship.')


@section('content')

@php
  $roles = \App\Http\Controllers\TeamController::$roles;
  $currentUser = auth()->user();

  /* ── Fake single-team model until Team model exists ──────────────────
     Swap $team / $teamName / $teamSlug for real Team model attrs later. */
  $teamName = 'My Team';
  $teamSlug = 'my-team';
@endphp

<div class="animate-fade-in px-4 sm:px-6 lg:px-8 py-6 lg:py-8"
     x-data="teamPage()" x-init="init()">



  {{-- ── Mobile: horizontal team pills ──────────────────────────────── --}}
  <div class="lg:hidden -mx-1 mb-4 overflow-x-auto">
    <div class="flex items-center gap-2 px-1 h-12">
      {{-- Single team pill for now; map over teams when Team model exists --}}
      <button class="flex items-center gap-2 px-3 h-10 rounded-full border border-transparent brand-soft-bg shadow-soft whitespace-nowrap">
        <span class="h-6 w-6 rounded-full team-avatar-0 grid place-items-center text-[10px] font-bold text-white">
          {{ strtoupper(substr($teamName, 0, 1)) }}
        </span>
        <span class="text-sm font-semibold brand-gradient-text">{{ $teamName }}</span>
      </button>

      {{-- New team button --}}
      <button @click="createTeamModal = true"
              class="flex items-center gap-1 px-3 h-10 rounded-full border border-dashed text-xs whitespace-nowrap transition-base"
              style="border-color:hsl(var(--border));color:hsl(var(--muted-foreground))"
              onmouseenter="this.style.borderColor='hsl(var(--primary)/0.5)';this.style.color='hsl(var(--primary))'"
              onmouseleave="this.style.borderColor='hsl(var(--border))';this.style.color='hsl(var(--muted-foreground))'">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New team
      </button>
    </div>
  </div>

  {{-- ── Two-column layout ────────────────────────────────────────────── --}}
  <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-5">

    {{-- ══════════════════════════════════════════════════════════════════
         LEFT — team switcher (desktop only)
    ═══════════════════════════════════════════════════════════════════ --}}
    <aside class="hidden lg:block">
      <div class="section-card p-3 sticky top-4">

        <div class="flex items-center justify-between px-2 py-1.5 mb-1">
          <span class="text-xs font-semibold uppercase tracking-wider"
                style="color:hsl(var(--muted-foreground))">Your Teams</span>
          <button @click="createTeamModal = true"
                  class="h-7 w-7 grid place-items-center rounded-lg transition-base"
                  style="color:hsl(var(--primary))"
                  title="Create team"
                  onmouseenter="this.style.background='hsl(var(--secondary))'"
                  onmouseleave="this.style.background=''">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
          </button>
        </div>

        {{-- Team list — single entry for now, loop over teams when model exists --}}
        <ul class="space-y-1">
          <li>
            <button class="w-full flex items-center gap-3 rounded-lg px-2.5 py-2 text-left brand-soft-bg relative transition-base">
              <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r brand-gradient-bg"></span>
              <span class="h-9 w-9 rounded-full team-avatar-0 grid place-items-center text-sm font-bold shrink-0 text-white shadow-soft">
                {{ strtoupper(substr($teamName, 0, 1)) }}
              </span>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-semibold truncate" style="color:hsl(var(--foreground))">{{ $teamName }}</div>
                <div class="text-[10px] font-mono" style="color:hsl(var(--muted-foreground))">/{{ $teamSlug }}</div>
              </div>
              <span class="text-[10px] font-mono px-1.5 py-0.5 rounded"
                    style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))">
                {{ $members->count() }}
              </span>
            </button>
          </li>
        </ul>

        <button @click="createTeamModal = true"
                class="mt-2 w-full flex items-center justify-center gap-2 rounded-lg border border-dashed px-3 py-3 text-xs transition-base"
                style="border-color:hsl(var(--border)/0.70);color:hsl(var(--muted-foreground))"
                onmouseenter="this.style.borderColor='hsl(var(--primary)/0.5)';this.style.color='hsl(var(--primary))';this.style.background='hsl(var(--secondary)/0.3)'"
                onmouseleave="this.style.borderColor='hsl(var(--border)/0.70)';this.style.color='hsl(var(--muted-foreground))';this.style.background=''">
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          New Team
        </button>
      </div>
    </aside>

    {{-- ══════════════════════════════════════════════════════════════════
         RIGHT — active team detail
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="space-y-5 min-w-0">

      {{-- ── Flash messages ─────────────────────────────────────────── --}}
      @if(session('success'))
        <div class="rounded-xl px-4 py-3 text-xs font-medium animate-fade-in"
             style="background:hsl(var(--success)/0.08);border:1px solid hsl(var(--success)/0.25);color:hsl(var(--success))">
          {{ session('success') }}
        </div>
      @endif
      @if(session('error'))
        <div class="rounded-xl px-4 py-3 text-xs font-medium animate-fade-in"
             style="background:hsl(var(--failed)/0.08);border:1px solid hsl(var(--failed)/0.25);color:hsl(var(--failed))">
          {{ session('error') }}
        </div>
      @endif

      {{-- ── Section 1 — Team header card ───────────────────────────── --}}
      <div class="section-card p-5">
        <div class="flex items-center gap-4">
          <div class="h-12 w-12 rounded-full team-avatar-0 grid place-items-center text-lg font-bold text-white shadow-soft shrink-0">
            {{ strtoupper(substr($teamName, 0, 1)) }}
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-lg font-bold truncate" style="color:hsl(var(--foreground))">{{ $teamName }}</div>
            <div class="text-xs font-mono" style="color:hsl(var(--muted-foreground))">/{{ $teamSlug }}</div>
          </div>
          <div class="hidden sm:flex items-center gap-1.5">
            <span class="text-[11px] font-mono px-2 py-1 rounded-md"
                  style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))">
              {{ $members->count() }} members
            </span>
          </div>
          <button @click="createTeamModal = true"
                  class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-base"
                  style="color:hsl(var(--muted-foreground))"
                  onmouseenter="this.style.background='hsl(var(--secondary))'"
                  onmouseleave="this.style.background=''">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
            </svg>
            Edit team
          </button>
        </div>
      </div>

      {{-- ── Section 2 — Members ─────────────────────────────────────── --}}
      <div class="section-card p-0 overflow-hidden">

        <div class="flex items-center justify-between px-5 py-3.5"
             style="border-bottom:1px solid hsl(var(--border)/0.60)">
          <div class="text-sm font-semibold" style="color:hsl(var(--foreground))">Members</div>
          <button @click="inviteModal = true"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white shadow-soft transition-base hover:opacity-90"
                  style="background:var(--gradient-brand)">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Invite member
          </button>
        </div>

        @if($members->isEmpty())
          <div class="p-10 text-center">
            <div class="mx-auto h-12 w-12 rounded-full brand-soft-bg grid place-items-center mb-3">
              <svg class="h-5 w-5" style="color:hsl(var(--primary))" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
              </svg>
            </div>
            <div class="text-sm font-semibold" style="color:hsl(var(--foreground))">No members yet</div>
            <div class="text-xs mt-1" style="color:hsl(var(--muted-foreground))">Invite your first teammate to get started.</div>
          </div>
        @else
          <ul>
            @foreach($members as $member)
              @php
                $isCurrent = $member->id === $currentUser->id;
                $role      = $member->pivot->role   ?? 'viewer';
                $status    = $member->pivot->status ?? 'active';
                $isOwner   = $role === 'owner';
                $initials  = strtoupper(substr($member->name, 0, 1))
                           . strtoupper(substr(strstr($member->name, ' '), 1, 1));
              @endphp

              <li class="group flex items-center gap-3 sm:gap-4 px-5 py-3 transition-base"
                  style="border-bottom:1px solid hsl(var(--border)/0.50)"
                  x-data="{ confirmRemove: false }"
                  onmouseenter="this.style.background='hsl(var(--secondary)/0.4)'"
                  onmouseleave="this.style.background=''">

                {{-- Confirm-remove overlay --}}
                <template x-if="confirmRemove">
                  <div class="flex items-center justify-between w-full gap-3 animate-fade-in"
                       style="background:hsl(var(--failed)/0.05);margin:-0.75rem -1.25rem;padding:0.75rem 1.25rem">
                    <div class="text-sm" style="color:hsl(var(--foreground))">
                      Remove <span class="font-semibold">{{ $member->name }}</span> from {{ $teamName }}?
                    </div>
                    <div class="flex items-center gap-2">
                      <button @click="confirmRemove = false"
                              class="px-3 py-1.5 rounded-lg text-xs font-medium transition-base"
                              style="color:hsl(var(--muted-foreground))"
                              onmouseenter="this.style.background='hsl(var(--secondary))'"
                              onmouseleave="this.style.background=''">
                        Cancel
                      </button>
                      <form method="POST" action="{{ route('team.members.remove', $member->id) }}" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-base"
                                style="border-color:hsl(var(--failed)/0.40);color:hsl(var(--failed))"
                                onmouseenter="this.style.background='hsl(var(--failed)/0.10)'"
                                onmouseleave="this.style.background=''">
                          Confirm remove
                        </button>
                      </form>
                    </div>
                  </div>
                </template>

                <template x-if="!confirmRemove">
                  {{-- Avatar --}}
                  <div class="h-9 w-9 rounded-full brand-gradient-bg flex items-center justify-center shrink-0">
                    <span class="text-xs font-semibold text-white">{{ $initials }}</span>
                  </div>

                  {{-- Name + email --}}
                  <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate flex items-center gap-1.5"
                         style="color:hsl(var(--foreground))">
                      {{ $member->name }}
                      @if($isCurrent)
                        <span class="font-normal text-xs" style="color:hsl(var(--muted-foreground))">(You)</span>
                      @endif
                    </div>
                    <div class="text-xs truncate" style="color:hsl(var(--muted-foreground))">{{ $member->email }}</div>
                  </div>

                  {{-- Status badge --}}
                  <div class="flex items-center gap-1.5 shrink-0">
                    @if($status === 'active')
                      <span class="hidden sm:inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[10px] font-semibold tracking-wider"
                            style="background:hsl(var(--success)/0.10);color:hsl(var(--success));border-color:hsl(var(--success)/0.30)">
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span> ACTIVE
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[10px] font-semibold tracking-wider"
                            style="background:hsl(var(--queued)/0.10);color:hsl(var(--queued));border-color:hsl(var(--queued)/0.30)">
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span> PENDING
                      </span>
                    @endif
                  </div>

                  {{-- Role selector / badge --}}
                  <div class="shrink-0">
                    @if($isCurrent || !$currentUser->isTeamOwnerOrAdmin())
                      <span class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-md brand-soft-bg"
                            style="border:1px solid hsl(var(--border)/0.60);color:hsl(var(--foreground))">
                        <span class="h-2.5 w-2.5 rounded-full role-swatch-{{ $role }}"></span>
                        {{ ucfirst($role === 'creator' ? 'Pkg Creator' : $role) }}
                      </span>
                    @else
                      <form method="POST" action="{{ route('team.members.update-role', $member->id) }}">
                        @csrf @method('PATCH')
                        <select name="role" onchange="this.form.submit()" class="role-select">
                          @foreach(['owner','maintainer','creator','deployer','viewer'] as $r)
                            <option value="{{ $r }}" {{ $role === $r ? 'selected' : '' }}>
                              {{ ucfirst($r === 'creator' ? 'Pkg Creator' : $r) }}
                            </option>
                          @endforeach
                        </select>
                      </form>
                    @endif
                  </div>

                  {{-- Remove button --}}
                  @if(!$isCurrent && $currentUser->isTeamOwnerOrAdmin() && !$isOwner)
                    <button @click.prevent="confirmRemove = true"
                            class="shrink-0 h-8 w-8 rounded-md grid place-items-center opacity-0 group-hover:opacity-100 transition-base"
                            style="color:hsl(var(--failed)/0.80)"
                            title="Remove member"
                            onmouseenter="this.style.background='hsl(var(--failed)/0.10)'"
                            onmouseleave="this.style.background=''">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  @else
                    <div class="h-8 w-8 shrink-0"></div>
                  @endif
                </template>

              </li>
            @endforeach
          </ul>
        @endif
      </div>

      {{-- ── Section 3 — Projects ────────────────────────────────────── --}}
      <div class="section-card p-0 overflow-hidden">

        <div class="flex items-center justify-between px-5 py-3.5"
             style="border-bottom:1px solid hsl(var(--border)/0.60)">
          <div class="text-sm font-semibold" style="color:hsl(var(--foreground))">Projects</div>
          <button @click="assignProjectModal = true"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-base"
                  style="color:hsl(var(--muted-foreground))"
                  onmouseenter="this.style.background='hsl(var(--secondary))'"
                  onmouseleave="this.style.background=''">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
            Assign project
          </button>
        </div>

        {{-- Empty state --}}
        <div class="p-10 text-center">
          <div class="mx-auto h-12 w-12 rounded-full brand-soft-bg grid place-items-center mb-3">
            <svg class="h-5 w-5" style="color:hsl(var(--primary))" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
          </div>
          <div class="text-sm font-semibold" style="color:hsl(var(--foreground))">No projects assigned</div>
          <div class="text-xs mt-1 mb-4" style="color:hsl(var(--muted-foreground))">
            Add projects so this team can build packages for them.
          </div>
          <button @click="assignProjectModal = true"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-base hover:opacity-90"
                  style="background:var(--gradient-brand)">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
            Assign project
          </button>
        </div>

        {{--
          When you wire up GitLab projects to teams, replace the empty-state above
          with a <ul> like this (loop over $teamProjects):

          <ul>
            @foreach($teamProjects as $project)
              <li class="group flex items-center gap-3 px-5 py-3 transition-base"
                  style="border-bottom:1px solid hsl(var(--border)/0.50)"
                  onmouseenter="this.style.background='hsl(var(--secondary)/0.4)'"
                  onmouseleave="this.style.background=''">
                <span class="h-3 w-3 rounded-full role-swatch-creator shrink-0"></span>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-semibold truncate">{{ $project->name }}</div>
                  <div class="text-[11px] truncate" style="color:hsl(var(--muted-foreground))">{{ $project->description }}</div>
                </div>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded shrink-0"
                      style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))">
                  {{ $project->repoCount }} repos
                </span>
                <form method="POST" action="{{ route('team.projects.remove', $project->id) }}" class="inline">
                  @csrf @method('DELETE')
                  <button type="submit"
                          class="text-xs opacity-0 group-hover:opacity-100 transition-base hover:underline shrink-0"
                          style="color:hsl(var(--failed))">Remove</button>
                </form>
              </li>
            @endforeach
          </ul>
        --}}

      </div>

      {{-- ── Section 4 — Role permissions collapsible ───────────────── --}}
      <div class="section-card p-0 overflow-hidden">

        <button @click="permsOpen = !permsOpen"
                class="w-full flex items-center justify-between px-5 py-3.5 text-left transition-base"
                onmouseenter="this.style.background='hsl(var(--secondary)/0.4)'"
                onmouseleave="this.style.background=''">
          <span class="text-sm font-semibold flex items-center gap-2" style="color:hsl(var(--foreground))">
            <svg class="h-4 w-4" style="color:hsl(var(--primary))" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            Role permissions
          </span>
          <svg class="h-4 w-4 transition-transform duration-200"
               :class="permsOpen ? 'rotate-180' : ''"
               style="color:hsl(var(--muted-foreground))"
               fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <div x-show="permsOpen" x-cloak x-collapse
             style="border-top:1px solid hsl(var(--border)/0.60)">
          <div class="px-5 pb-5 pt-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
              @foreach($roles as $role)
                <div class="rounded-xl border p-4 transition-base"
                     style="background:hsl(var(--card));border-color:hsl(var(--border)/0.70)"
                     onmouseenter="this.style.boxShadow='var(--shadow-md)'"
                     onmouseleave="this.style.boxShadow=''">
                  <div class="h-8 w-8 rounded-lg shadow-soft mb-2 role-swatch-{{ $role['key'] }}"></div>
                  <div class="text-sm font-semibold" style="color:hsl(var(--foreground))">{{ $role['label'] }}</div>
                  <div class="text-[11px] mt-1 leading-snug" style="color:hsl(var(--muted-foreground))">{{ $role['desc'] }}</div>
                </div>
              @endforeach
            </div>
          </div>
        </div>

      </div>
    </div>{{-- /right col --}}
  </div>{{-- /grid --}}


  {{-- ══════════════════════════════════════════════════════════════════
       MODAL — Invite member
  ═══════════════════════════════════════════════════════════════════ --}}
  <div x-show="inviteModal" x-cloak
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 flex items-center justify-center p-4"
       style="background:hsl(220 30% 5% / 0.55);backdrop-filter:blur(4px)"
       @click.self="inviteModal = false"
       @keydown.escape.window="inviteModal = false">

    <div class="w-full max-w-md animate-slide-up"
         style="background:hsl(var(--card));border-radius:calc(var(--radius)*1.5);border:1px solid hsl(var(--border)/0.7);box-shadow:var(--shadow-lg)">

      <div class="flex items-center justify-between px-6 pt-6 pb-4"
           style="border-bottom:1px solid hsl(var(--border)/0.6)">
        <div>
          <h2 class="text-sm font-bold" style="color:hsl(var(--foreground))">Invite team member</h2>
          <p class="text-xs mt-0.5" style="color:hsl(var(--muted-foreground))">They'll receive an email to join your team.</p>
        </div>
        <button @click="inviteModal = false"
                class="h-8 w-8 rounded-lg flex items-center justify-center transition-base"
                style="color:hsl(var(--muted-foreground))"
                onmouseenter="this.style.background='hsl(var(--secondary))'"
                onmouseleave="this.style.background=''">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <form method="POST" action="{{ route('team.members.invite') }}" class="px-6 py-5 space-y-5">
        @csrf

        {{-- Email --}}
        <div>
          <label class="block text-xs font-medium mb-1.5" style="color:hsl(var(--foreground))">
            Email address <span style="color:hsl(var(--failed))">*</span>
          </label>
          <input type="email" name="email" required
                 placeholder="colleague@company.com"
                 value="{{ old('email') }}"
                 class="w-full rounded-xl border text-sm px-3 py-2 transition-base outline-none"
                 style="background:hsl(var(--card));border-color:hsl(var(--border));color:hsl(var(--foreground))"
                 onfocus="this.style.borderColor='hsl(var(--primary))'"
                 onblur="this.style.borderColor='hsl(var(--border))'">
          @error('email')
            <p class="text-xs mt-1" style="color:hsl(var(--failed))">{{ $message }}</p>
          @enderror
        </div>

        {{-- Role radio cards --}}
        <div>
          <label class="block text-xs font-medium mb-2" style="color:hsl(var(--foreground))">
            Role <span style="color:hsl(var(--failed))">*</span>
          </label>
          <div class="grid grid-cols-1 gap-2">
            @foreach($roles as $role)
              <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-base"
                     :style="inviteRole === '{{ $role['key'] }}'
                       ? 'background:var(--gradient-brand-soft);border-color:hsl(var(--primary)/0.5)'
                       : 'border-color:hsl(var(--border))'">
                <input type="radio" name="role" value="{{ $role['key'] }}"
                       x-model="inviteRole"
                       class="sr-only"
                       {{ old('role', 'viewer') === $role['key'] ? 'checked' : '' }}>
                <div class="h-7 w-7 rounded-lg shadow-soft shrink-0 role-swatch-{{ $role['key'] }}"></div>
                <div class="flex-1 min-w-0">
                  <div class="text-xs font-semibold" style="color:hsl(var(--foreground))">{{ $role['label'] }}</div>
                  <div class="text-[10px] mt-0.5 leading-snug" style="color:hsl(var(--muted-foreground))">{{ $role['desc'] }}</div>
                </div>
                <div x-show="inviteRole === '{{ $role['key'] }}'"
                     class="h-4 w-4 rounded-full brand-gradient-bg flex items-center justify-center shrink-0">
                  <svg class="h-2.5 w-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                </div>
              </label>
            @endforeach
          </div>
          @error('role')
            <p class="text-xs mt-1" style="color:hsl(var(--failed))">{{ $message }}</p>
          @enderror
        </div>

        <div class="flex items-center justify-between pt-1">
          <button type="button" @click="inviteModal = false"
                  class="px-3 py-1.5 rounded-lg text-sm font-medium transition-base"
                  style="color:hsl(var(--muted-foreground))"
                  onmouseenter="this.style.background='hsl(var(--secondary))'"
                  onmouseleave="this.style.background=''">
            Cancel
          </button>
          <button type="submit"
                  class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-white transition-base hover:opacity-90"
                  style="background:var(--gradient-brand)">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Send invite
          </button>
        </div>
      </form>
    </div>
  </div>


  {{-- ══════════════════════════════════════════════════════════════════
       MODAL — Create new team
       (stub UI; wire up to a TeamController::store() route when
        the Team model exists)
  ═══════════════════════════════════════════════════════════════════ --}}
  <div x-show="createTeamModal" x-cloak
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 flex items-center justify-center p-4"
       style="background:hsl(220 30% 5% / 0.55);backdrop-filter:blur(4px)"
       @click.self="createTeamModal = false"
       @keydown.escape.window="createTeamModal = false">

    <div class="w-full max-w-sm animate-slide-up"
         style="background:hsl(var(--card));border-radius:calc(var(--radius)*1.5);border:1px solid hsl(var(--border)/0.7);box-shadow:var(--shadow-lg)">

      <div class="flex items-center justify-between px-6 pt-6 pb-4"
           style="border-bottom:1px solid hsl(var(--border)/0.6)">
        <div>
          <h2 class="text-sm font-bold" style="color:hsl(var(--foreground))">Create new team</h2>
          <p class="text-xs mt-0.5" style="color:hsl(var(--muted-foreground))">Give your team a name and slug.</p>
        </div>
        <button @click="createTeamModal = false"
                class="h-8 w-8 rounded-lg flex items-center justify-center transition-base"
                style="color:hsl(var(--muted-foreground))"
                onmouseenter="this.style.background='hsl(var(--secondary))'"
                onmouseleave="this.style.background=''">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="px-6 py-5 space-y-4">
        <div>
          <label class="block text-xs font-medium mb-1.5" style="color:hsl(var(--foreground))">Team name</label>
          <input type="text" x-model="newTeamName" placeholder="e.g. Platform Team"
                 class="w-full rounded-xl border text-sm px-3 py-2 transition-base outline-none"
                 style="background:hsl(var(--card));border-color:hsl(var(--border));color:hsl(var(--foreground))"
                 onfocus="this.style.borderColor='hsl(var(--primary))'"
                 onblur="this.style.borderColor='hsl(var(--border))'">
        </div>
        <div>
          <label class="block text-xs font-medium mb-1.5" style="color:hsl(var(--foreground))">Slug</label>
          <div class="flex items-center rounded-xl border overflow-hidden"
               style="border-color:hsl(var(--border))">
            <span class="px-3 py-2 text-sm border-r"
                  style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground));border-color:hsl(var(--border))">/</span>
            <input type="text" x-model="newTeamSlug"
                   :placeholder="newTeamName ? newTeamName.toLowerCase().replace(/\s+/g,'-') : 'platform-team'"
                   class="flex-1 text-sm px-3 py-2 outline-none"
                   style="background:hsl(var(--card));color:hsl(var(--foreground))">
          </div>
        </div>

        <div class="flex items-center justify-between pt-1">
          <button type="button" @click="createTeamModal = false"
                  class="px-3 py-1.5 rounded-lg text-sm font-medium transition-base"
                  style="color:hsl(var(--muted-foreground))"
                  onmouseenter="this.style.background='hsl(var(--secondary))'"
                  onmouseleave="this.style.background=''">
            Cancel
          </button>
          <button type="button"
                  class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-white transition-base hover:opacity-90"
                  style="background:var(--gradient-brand)"
                  @click="createTeamModal = false">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Create team
          </button>
        </div>
      </div>
    </div>
  </div>


  {{-- ══════════════════════════════════════════════════════════════════
       MODAL — Assign project
       (stub UI; wire up to TeamController::assignProject() when
        the team–project pivot exists)
  ═══════════════════════════════════════════════════════════════════ --}}
  <div x-show="assignProjectModal" x-cloak
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 flex items-center justify-center p-4"
       style="background:hsl(220 30% 5% / 0.55);backdrop-filter:blur(4px)"
       @click.self="assignProjectModal = false"
       @keydown.escape.window="assignProjectModal = false">

    <div class="w-full max-w-sm animate-slide-up"
         style="background:hsl(var(--card));border-radius:calc(var(--radius)*1.5);border:1px solid hsl(var(--border)/0.7);box-shadow:var(--shadow-lg)">

      <div class="flex items-center justify-between px-6 pt-6 pb-4"
           style="border-bottom:1px solid hsl(var(--border)/0.6)">
        <div>
          <h2 class="text-sm font-bold" style="color:hsl(var(--foreground))">Assign project</h2>
          <p class="text-xs mt-0.5" style="color:hsl(var(--muted-foreground))">Link a GitLab project to this team.</p>
        </div>
        <button @click="assignProjectModal = false"
                class="h-8 w-8 rounded-lg flex items-center justify-center transition-base"
                style="color:hsl(var(--muted-foreground))"
                onmouseenter="this.style.background='hsl(var(--secondary))'"
                onmouseleave="this.style.background=''">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="px-6 py-5 space-y-4">
        <p class="text-xs" style="color:hsl(var(--muted-foreground))">
          Project–team assignment will be available once the <code class="text-xs font-mono px-1 py-0.5 rounded" style="background:hsl(var(--secondary))">Team</code> model and pivot table are wired up.
          Use the <a href="{{ route('projects') }}" class="underline" style="color:hsl(var(--primary))">Projects page</a> for now.
        </p>
        <div class="flex justify-end">
          <button type="button" @click="assignProjectModal = false"
                  class="px-3 py-1.5 rounded-lg text-sm font-medium transition-base"
                  style="color:hsl(var(--muted-foreground))"
                  onmouseenter="this.style.background='hsl(var(--secondary))'"
                  onmouseleave="this.style.background=''">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>

</div>{{-- /x-data --}}
@endsection

@push('scripts')
<script>
function teamPage() {
  return {
    inviteModal:       {{ $errors->any() ? 'true' : 'false' }},
    createTeamModal:   false,
    assignProjectModal: false,
    permsOpen:         false,
    inviteRole:        '{{ old('role', 'viewer') }}',
    newTeamName:       '',
    newTeamSlug:       '',

    init() {
      // Reopen invite modal on validation error
      @if($errors->has('email') || $errors->has('role'))
        this.inviteModal = true;
      @endif

      // Session toast
      @if(session('success'))
        setTimeout(() => window.dispatchEvent(
          new CustomEvent('toast', { detail: { type: 'success', message: @json(session('success')) } })
        ), 50);
      @endif
      @if(session('error'))
        setTimeout(() => window.dispatchEvent(
          new CustomEvent('toast', { detail: { type: 'error', message: @json(session('error')) } })
        ), 50);
      @endif
    },
  };
}
</script>
@endpush