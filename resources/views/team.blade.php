@extends('layouts.app')

@push('styles')
<style>
  :root {
    --background:           220 30% 97%;
    --foreground:           222 35% 12%;
    --card:                 0 0% 100%;
    --primary:              232 32% 67%;
    --primary-foreground:   0 0% 100%;
    --brand-rose:           343 28% 71%;
    --brand-teal:           195 39% 64%;
    --brand-iris:           232 32% 67%;
    --secondary:            220 25% 94%;
    --muted-foreground:     220 12% 46%;
    --accent:               232 40% 96%;
    --success:              152 55% 42%;
    --queued:               38 92% 52%;
    --failed:               0 75% 58%;
    --border:               220 20% 89%;
    --radius:               0.85rem;
    --gradient-brand:       linear-gradient(135deg, hsl(343 28% 71%), hsl(195 39% 64%), hsl(232 32% 67%));
    --gradient-brand-soft:  linear-gradient(135deg, hsl(343 28% 71% / 0.18), hsl(195 39% 64% / 0.18), hsl(232 32% 67% / 0.22));
    --shadow-sm:            0 1px 2px hsl(222 35% 12% / 0.04);
    --shadow-md:            0 4px 14px -4px hsl(232 32% 40% / 0.10), 0 2px 6px -2px hsl(222 35% 12% / 0.05);
    --shadow-lg:            0 18px 40px -18px hsl(232 32% 40% / 0.25), 0 6px 16px -8px hsl(222 35% 12% / 0.08);
    --transition-base:      200ms cubic-bezier(0.4, 0, 0.2, 1);
  }

  .brand-gradient-bg  { background: var(--gradient-brand); }
  .brand-soft-bg      { background: var(--gradient-brand-soft); }
  .brand-gradient-text {
    background: var(--gradient-brand);
    -webkit-background-clip: text; background-clip: text; color: transparent;
  }
  .section-card {
    background: hsl(var(--card));
    border-radius: var(--radius);
    border: 1px solid hsl(var(--border) / 0.7);
    box-shadow: var(--shadow-sm);
    transition: box-shadow var(--transition-base);
  }
  .section-card:hover { box-shadow: var(--shadow-md); }
  .shadow-soft     { box-shadow: var(--shadow-md); }
  .transition-base { transition: all var(--transition-base); }

  /* Role gradient swatches — matching cybix-craft cn() combos */
  .role-swatch-owner      { background: linear-gradient(135deg, hsl(var(--brand-rose)), hsl(var(--brand-iris))); }
  .role-swatch-maintainer { background: linear-gradient(135deg, hsl(var(--brand-iris)), hsl(var(--brand-teal))); }
  .role-swatch-creator    { background: linear-gradient(135deg, hsl(var(--brand-teal)), hsl(var(--brand-iris))); }
  .role-swatch-deployer   { background: linear-gradient(135deg, hsl(var(--brand-rose)), hsl(var(--brand-teal))); }
  .role-swatch-viewer     { background: linear-gradient(135deg, hsl(var(--brand-iris)), hsl(var(--brand-rose))); }

  @keyframes fade-in {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .animate-fade-in { animation: fade-in 220ms ease-out both; }

  @keyframes slide-up {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
  }
  .animate-slide-up { animation: slide-up 320ms cubic-bezier(0.22, 1, 0.36, 1) both; }

  [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')

<div class="animate-fade-in px-4 sm:px-6 lg:px-8 py-6 lg:py-8 space-y-6"
     x-data="teamPage()" x-init="init()">

  {{-- ── Page header ─────────────────────────────────────────────────── --}}
  <div class="flex items-center justify-between gap-3">
    <div>
      <h1 class="text-base font-semibold tracking-tight" style="color:hsl(var(--foreground))">
        Team &amp; Roles
      </h1>
      <p class="text-xs mt-0.5" style="color:hsl(var(--muted-foreground))">
        Configure who can create, deploy, and one-click ship.
      </p>
    </div>
    <button @click="inviteModal = true"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold text-white shadow-soft transition-base hover:opacity-90"
            style="background:var(--gradient-brand)">
      {{-- UserPlus icon --}}
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
      </svg>
      Invite member
    </button>
  </div>

  {{-- ── Roles section ───────────────────────────────────────────────── --}}
  <section>
    <h3 class="text-sm font-semibold mb-3 flex items-center gap-2"
        style="color:hsl(var(--foreground))">
      {{-- ShieldCheck icon --}}
      <svg class="h-4 w-4" style="color:hsl(var(--primary))"
           fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
      </svg>
      Roles
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      @php
        $roles = [
          ['key' => 'owner',      'label' => 'Owner',          'desc' => 'Full control, billing, role configuration'],
          ['key' => 'maintainer', 'label' => 'Maintainer',     'desc' => 'Configure projects, repositories and policies'],
          ['key' => 'creator',    'label' => 'Package Creator','desc' => 'Create packages, cannot deploy to PROD'],
          ['key' => 'deployer',   'label' => 'Deployer',       'desc' => 'Deploy approved packages to permitted environments'],
          ['key' => 'viewer',     'label' => 'Viewer',         'desc' => 'Read-only access to packages and deployments'],
        ];
      @endphp

      @foreach($roles as $role)
        <div class="section-card p-4">
          <div class="h-8 w-8 rounded-lg shadow-soft mb-2 role-swatch-{{ $role['key'] }}"></div>
          <div class="text-sm font-semibold" style="color:hsl(var(--foreground))">
            {{ $role['label'] }}
          </div>
          <div class="text-[11px] mt-1 leading-snug" style="color:hsl(var(--muted-foreground))">
            {{ $role['desc'] }}
          </div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- ── Members section ─────────────────────────────────────────────── --}}
  <section>
    <h3 class="text-sm font-semibold mb-3" style="color:hsl(var(--foreground))">Members</h3>

    <div class="section-card p-0 overflow-hidden">

      {{-- Flash messages --}}
      @if(session('success'))
        <div class="px-5 py-3 text-xs font-medium"
             style="background:hsl(var(--success)/0.08);border-bottom:1px solid hsl(var(--success)/0.20);color:hsl(var(--success))">
          {{ session('success') }}
        </div>
      @endif
      @if(session('error'))
        <div class="px-5 py-3 text-xs font-medium"
             style="background:hsl(var(--failed)/0.08);border-bottom:1px solid hsl(var(--failed)/0.20);color:hsl(var(--failed))">
          {{ session('error') }}
        </div>
      @endif

      <ul style="divide-y:1px solid hsl(var(--border)/0.60)">

        @forelse($members as $member)
          <li class="flex items-center gap-4 px-5 py-4 transition-base group"
              style="border-bottom:1px solid hsl(var(--border)/0.50)"
              x-data="{ confirmRemove: false }"
              onmouseenter="this.style.background='hsl(var(--secondary)/0.4)'"
              onmouseleave="this.style.background=''">

            {{-- Avatar --}}
            <div class="h-10 w-10 rounded-full brand-gradient-bg flex items-center justify-center shrink-0">
              <span class="text-xs font-semibold text-white">
                {{ strtoupper(substr($member->name, 0, 1)) }}{{ strtoupper(substr(strstr($member->name, ' '), 1, 1)) }}
              </span>
            </div>

            {{-- Name + email --}}
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium flex items-center gap-2" style="color:hsl(var(--foreground))">
                {{ $member->name }}
                @if($member->id === auth()->id())
                  <span class="text-[10px] font-mono px-1.5 py-0.5 rounded"
                        style="background:hsl(var(--secondary));color:hsl(var(--muted-foreground))">You</span>
                @endif
                @if($member->pivot->status === 'pending')
                  <span class="text-[10px] font-medium px-2 py-0.5 rounded-md border"
                        style="background:hsl(var(--queued)/0.10);color:hsl(var(--queued));border-color:hsl(var(--queued)/0.30)">
                    Pending
                  </span>
                @endif
              </div>
              <div class="text-xs" style="color:hsl(var(--muted-foreground))">{{ $member->email }}</div>
            </div>

            {{-- Role badge / selector --}}
            @if($member->id === auth()->id() || !auth()->user()->isTeamOwnerOrAdmin($currentTeam))
              {{-- Read-only role badge for self or non-admins --}}
              <span class="text-xs font-medium px-2.5 py-1 rounded-md brand-soft-bg"
                    style="border:1px solid hsl(var(--border)/0.60)">
                {{ ucfirst($member->pivot->role) }}
              </span>
            @else
              {{-- Editable role dropdown for admins --}}
              <form method="POST" action="{{ route('team.members.update-role', $member->id) }}">
                @csrf @method('PATCH')
                <select name="role" onchange="this.form.submit()"
                        class="text-xs font-medium px-2.5 py-1 rounded-md border transition-base outline-none cursor-pointer"
                        style="background:var(--gradient-brand-soft);border-color:hsl(var(--border)/0.60);color:hsl(var(--foreground))">
                  @foreach(['owner','maintainer','creator','deployer','viewer'] as $r)
                    <option value="{{ $r }}" {{ $member->pivot->role === $r ? 'selected' : '' }}>
                      {{ ucfirst($r === 'creator' ? 'Pkg Creator' : $r) }}
                    </option>
                  @endforeach
                </select>
              </form>
            @endif

            {{-- Remove button — only for admins, not for self, not for owner row --}}
            @if($member->id !== auth()->id() && auth()->user()->isTeamOwnerOrAdmin($currentTeam) && $member->pivot->role !== 'owner')
              <div x-show="!confirmRemove">
                <button @click.prevent="confirmRemove = true"
                        class="opacity-0 group-hover:opacity-100 transition-base h-8 w-8 flex items-center justify-center rounded-lg"
                        style="color:hsl(var(--failed))"
                        title="Remove member"
                        onmouseenter="this.style.background='hsl(var(--failed)/0.08)'"
                        onmouseleave="this.style.background=''">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>

              {{-- Inline confirm --}}
              <div x-show="confirmRemove" x-cloak class="flex items-center gap-2 animate-fade-in">
                <span class="text-xs" style="color:hsl(var(--muted-foreground))">Remove?</span>
                <form method="POST" action="{{ route('team.members.remove', $member->id) }}" class="inline">
                  @csrf @method('DELETE')
                  <button type="submit"
                          class="text-xs font-semibold px-2 py-1 rounded-md transition-base"
                          style="background:hsl(var(--failed)/0.12);color:hsl(var(--failed))">
                    Confirm
                  </button>
                </form>
                <button @click="confirmRemove = false"
                        class="text-xs px-2 py-1 rounded-md transition-base"
                        style="color:hsl(var(--muted-foreground))"
                        onmouseenter="this.style.background='hsl(var(--secondary))'"
                        onmouseleave="this.style.background=''">
                  Cancel
                </button>
              </div>
            @endif

          </li>
        @empty
          <li class="px-5 py-12 text-center">
            <div class="mx-auto mb-4 h-12 w-12 rounded-2xl brand-soft-bg flex items-center justify-center">
              <svg class="h-6 w-6" style="color:hsl(var(--primary))"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </div>
            <p class="text-sm font-medium" style="color:hsl(var(--foreground))">No members yet</p>
            <p class="text-xs mt-1" style="color:hsl(var(--muted-foreground))">
              Invite your first team member to get started.
            </p>
            <button @click="inviteModal = true"
                    class="inline-flex items-center gap-1.5 mt-4 px-3 py-1.5 rounded-lg text-sm font-semibold text-white transition-base hover:opacity-90"
                    style="background:var(--gradient-brand)">
              Invite member
            </button>
          </li>
        @endforelse

      </ul>
    </div>
  </section>

  {{-- ══════════════════════════════════════════════════════════════════
       INVITE MEMBER MODAL
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
       @click.self="inviteModal = false">

    <div class="w-full max-w-md animate-slide-up"
         style="background:hsl(var(--card));border-radius:calc(var(--radius)*1.5);border:1px solid hsl(var(--border)/0.7);box-shadow:var(--shadow-lg)">

      {{-- Modal header --}}
      <div class="flex items-center justify-between px-6 pt-6 pb-4"
           style="border-bottom:1px solid hsl(var(--border)/0.6)">
        <div>
          <h2 class="text-sm font-bold" style="color:hsl(var(--foreground))">Invite team member</h2>
          <p class="text-xs mt-0.5" style="color:hsl(var(--muted-foreground))">
            They'll receive an email to join your team.
          </p>
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

      {{-- Form --}}
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

        {{-- Role selector --}}
        <div>
          <label class="block text-xs font-medium mb-2" style="color:hsl(var(--foreground))">
            Role <span style="color:hsl(var(--failed))">*</span>
          </label>
          <div class="grid grid-cols-1 gap-2">
            @foreach($roles as $role)
              <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-base"
                     style="border-color:hsl(var(--border))"
                     x-bind:style="inviteRole === '{{ $role['key'] }}'
                       ? 'background:var(--gradient-brand-soft);border-color:hsl(var(--primary)/0.5)'
                       : ''"
                     onmouseenter="if(this.querySelector('input').value !== '{{ old('role') ?? '' }}') this.style.background='hsl(var(--secondary)/0.5)'"
                     onmouseleave="if(this.querySelector('input').value !== (document.querySelector('[name=role]:checked')?.value)) this.style.background=''">
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

        {{-- Actions --}}
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

</div>

@endsection

@push('scripts')
<script>
function teamPage() {
  return {
    inviteModal: {{ $errors->any() ? 'true' : 'false' }},
    inviteRole:  '{{ old('role', 'viewer') }}',

    init() {
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') this.inviteModal = false;
      });

      // Show session toast if present
      @if(session('success'))
        setTimeout(() => window.dispatchEvent(
          new CustomEvent('toast', { detail: { type: 'success', message: '{{ session('success') }}' } })
        ), 50);
      @endif
    },
  };
}
</script>
@endpush
