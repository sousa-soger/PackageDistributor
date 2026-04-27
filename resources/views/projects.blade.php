@extends('layouts.app')

@section('title', 'Repositories')
@section('subtitle', 'GitHub, GitLab, company servers and local repositories.')

@section('topbar_actions')
    <div x-data="{ showConnectModal: false }">
        <button @click="showConnectModal = true" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft hover:shadow-glow hover:brightness-[1.03] active:brightness-95 transition-base h-9 rounded-md px-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package-plus h-4 w-4">
                <path d="M16 16h6"></path>
                <path d="M19 13v6"></path>
                <path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"></path>
                <path d="m7.5 4.27 9 5.15"></path>
                <polyline points="3.29 7 12 12 20.71 7"></polyline>
                <line x1="12" x2="12" y1="22" y2="12"></line>
            </svg>
            Connect Repository
        </button>

        <template x-teleport="body">
            <div x-show="showConnectModal" x-cloak class="relative z-50">
                <!-- Backdrop -->
                <div x-show="showConnectModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-background/80 backdrop-blur-sm"></div>

                <!-- Modal Dialog Container -->
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
                    <div x-show="showConnectModal"
                        @click.away="showConnectModal = false"
                        @keydown.escape.window="showConnectModal = false"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="w-full max-w-xl grid gap-4 border border-border bg-background shadow-lg sm:rounded-lg p-0 overflow-hidden relative"
                        role="dialog"
                        aria-modal="true"
                    >
                        <div class="brand-soft-bg px-6 py-5 border-b border-border/60">
                            <div class="flex flex-col space-y-1.5 text-center sm:text-left">
                                <h2 class="font-semibold tracking-tight text-xl">Connect Repository</h2>
                                <p class="text-sm text-muted-foreground">Cybix Deployer reads code only when you build a package. Credentials are stored encrypted.</p>
                            </div>
                            <div class="mt-4 flex items-center gap-2">
                                <div class="flex items-center gap-2 flex-1"><div class="h-1.5 flex-1 rounded-full transition-colors brand-gradient-bg"></div></div>
                                <div class="flex items-center gap-2 flex-1"><div class="h-1.5 flex-1 rounded-full transition-colors bg-border"></div></div>
                                <div class="flex items-center gap-2 flex-1"><div class="h-1.5 flex-1 rounded-full transition-colors bg-border"></div></div>
                                <div class="flex items-center gap-2 flex-1"><div class="h-1.5 flex-1 rounded-full transition-colors bg-border"></div></div>
                            </div>
                        </div>
                        <div class="px-6 py-5 max-h-[60vh] overflow-y-auto">
                            <div class="space-y-2">
                                <p class="text-sm text-muted-foreground mb-3">Pick a source for your repository.</p>
                                <button class="w-full text-left flex items-center gap-3 rounded-xl border border-border/70 bg-card p-3 hover:shadow-soft hover:border-primary/40 transition-base group">
                                    <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary shrink-0 transition-base group-hover:-translate-y-0.5"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-github h-5 w-5"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"></path><path d="M9 18c-4.51 2-5-2-7-2"></path></svg></div>
                                    <div class="min-w-0 flex-1"><div class="text-sm font-semibold">GitHub</div><div class="text-xs text-muted-foreground truncate">Connect a public or private GitHub repository via OAuth.</div></div>
                                    <div class="text-[10px] font-medium text-muted-foreground uppercase tracking-wider">oauth</div>
                                </button>
                                <button class="w-full text-left flex items-center gap-3 rounded-xl border border-border/70 bg-card p-3 hover:shadow-soft hover:border-primary/40 transition-base group">
                                    <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary shrink-0 transition-base group-hover:-translate-y-0.5"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gitlab h-5 w-5"><path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"></path></svg></div>
                                    <div class="min-w-0 flex-1"><div class="text-sm font-semibold">GitLab</div><div class="text-xs text-muted-foreground truncate">Connect a GitLab.com or self-hosted GitLab repository.</div></div>
                                    <div class="text-[10px] font-medium text-muted-foreground uppercase tracking-wider">token</div>
                                </button>
                                <button class="w-full text-left flex items-center gap-3 rounded-xl border border-border/70 bg-card p-3 hover:shadow-soft hover:border-primary/40 transition-base group">
                                    <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary shrink-0 transition-base group-hover:-translate-y-0.5"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-server h-5 w-5"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect><rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect><line x1="6" x2="6.01" y1="6" y2="6"></line><line x1="6" x2="6.01" y1="18" y2="18"></line></svg></div>
                                    <div class="min-w-0 flex-1"><div class="text-sm font-semibold">Company Server</div><div class="text-xs text-muted-foreground truncate">Connect a self-hosted Git server over SSH.</div></div>
                                    <div class="text-[10px] font-medium text-muted-foreground uppercase tracking-wider">ssh</div>
                                </button>
                                <button class="w-full text-left flex items-center gap-3 rounded-xl border border-border/70 bg-card p-3 hover:shadow-soft hover:border-primary/40 transition-base group">
                                    <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary shrink-0 transition-base group-hover:-translate-y-0.5"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hard-drive h-5 w-5"><line x1="22" x2="2" y1="12" y2="12"></line><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path><line x1="6" x2="6.01" y1="16" y2="16"></line><line x1="10" x2="10.01" y1="16" y2="16"></line></svg></div>
                                    <div class="min-w-0 flex-1"><div class="text-sm font-semibold">Local PC</div><div class="text-xs text-muted-foreground truncate">Index a local repository folder via the Cybix agent.</div></div>
                                    <div class="text-[10px] font-medium text-muted-foreground uppercase tracking-wider">path</div>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-col-reverse sm:flex-row sm:space-x-2 px-6 py-4 border-t border-border/60 bg-muted/30 sm:justify-between gap-2">
                            <span></span>
                            <div class="flex items-center gap-2">
                                <button @click="showConnectModal = false" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">Cancel</button>
                            </div>
                        </div>
                        <button @click="showConnectModal = false" type="button" class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-colors hover:bg-muted hover:text-foreground hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none p-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x h-4 w-4"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
                            <span class="sr-only">Close</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection

@section('content')
<main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 lg:py-8 animate-fade-in">
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

    <!-- Section 1: Atlas Web - web-storefront -->
    <div class="section-card p-5">
      <div class="flex items-start justify-between gap-3 mb-3">
        <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-github h-4 w-4">
            <path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"></path>
            <path d="M9 18c-4.51 2-5-2-7-2"></path>
          </svg>
        </div>
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border bg-success/10 text-success border-success/30">Connected</span>
      </div>
      <div class="text-sm font-semibold truncate">atlas/web-storefront</div>
      <div class="text-xs text-muted-foreground">Atlas Web</div>
      <div class="mt-3 flex flex-wrap gap-1.5">
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">4 branches</span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">5 tags</span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">default · main</span>
      </div>
    </div>

    <!-- Section 2: Atlas Web - marketing-site -->
    <div class="section-card p-5">
      <div class="flex items-start justify-between gap-3 mb-3">
        <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gitlab h-4 w-4">
            <path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"></path>
          </svg>
        </div>
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border bg-success/10 text-success border-success/30">Connected</span>
      </div>
      <div class="text-sm font-semibold truncate">atlas/marketing-site</div>
      <div class="text-xs text-muted-foreground">Atlas Web</div>
      <div class="mt-3 flex flex-wrap gap-1.5">
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">2 branches</span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">2 tags</span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">default · main</span>
      </div>
    </div>

    <!-- Section 3: Helios API - core-api -->
    <div class="section-card p-5">
      <div class="flex items-start justify-between gap-3 mb-3">
        <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-github h-4 w-4">
            <path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"></path>
            <path d="M9 18c-4.51 2-5-2-7-2"></path>
          </svg>
        </div>
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border bg-success/10 text-success border-success/30">Connected</span>
      </div>
      <div class="text-sm font-semibold truncate">helios/core-api</div>
      <div class="text-xs text-muted-foreground">Helios API</div>
      <div class="mt-3 flex flex-wrap gap-1.5">
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">3 branches</span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">3 tags</span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">default · main</span>
      </div>
    </div>

    <!-- Section 4: Helios API - graph-gateway -->
    <div class="section-card p-5">
      <div class="flex items-start justify-between gap-3 mb-3">
        <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-server h-4 w-4">
            <rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect>
            <rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect>
            <line x1="6" x2="6.01" y1="6" y2="6"></line>
            <line x1="6" x2="6.01" y1="18" y2="18"></line>
          </svg>
        </div>
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border bg-success/10 text-success border-success/30">Connected</span>
      </div>
      <div class="text-sm font-semibold truncate">helios/graph-gateway</div>
      <div class="text-xs text-muted-foreground">Helios API</div>
      <div class="mt-3 flex flex-wrap gap-1.5">
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">2 branches</span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">2 tags</span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">default · main</span>
      </div>
    </div>

    <!-- Section 5: Nimbus Mobile - mobile-client -->
    <div class="section-card p-5">
      <div class="flex items-start justify-between gap-3 mb-3">
        <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gitlab h-4 w-4">
            <path d="m22 13.29-3.33-10a.42.42 0 0 0-.14-.18.38.38 0 0 0-.22-.11.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18l-2.26 6.67H8.32L6.1 3.26a.42.42 0 0 0-.1-.18.38.38 0 0 0-.26-.08.39.39 0 0 0-.23.07.42.42 0 0 0-.14.18L2 13.29a.74.74 0 0 0 .27.83L12 21l9.69-6.88a.71.71 0 0 0 .31-.83Z"></path>
          </svg>
        </div>
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border bg-failed/10 text-failed border-failed/30">Needs auth</span>
      </div>
      <div class="text-sm font-semibold truncate">nimbus/mobile-client</div>
      <div class="text-xs text-muted-foreground">Nimbus Mobile</div>
      <div class="mt-3 flex flex-wrap gap-1.5">
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">3 branches</span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">2 tags</span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">default · main</span>
      </div>
    </div>

    <!-- Section 6: Orion Internal - admin-dashboard -->
    <div class="section-card p-5">
      <div class="flex items-start justify-between gap-3 mb-3">
        <div class="h-10 w-10 rounded-lg brand-soft-bg flex items-center justify-center text-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hard-drive h-4 w-4">
            <line x1="22" x2="2" y1="12" y2="12"></line>
            <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
            <line x1="6" x2="6.01" y1="16" y2="16"></line>
            <line x1="10" x2="10.01" y1="16" y2="16"></line>
          </svg>
        </div>
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border bg-queued/10 text-queued border-queued/30">Expired</span>
      </div>
      <div class="text-sm font-semibold truncate">orion/admin-dashboard</div>
      <div class="text-xs text-muted-foreground">Orion Internal</div>
      <div class="mt-3 flex flex-wrap gap-1.5">
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">1 branches</span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">1 tags</span>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-secondary text-muted-foreground">default · main</span>
      </div>
    </div>

  </div>
</main>
@endsection

@push('scripts')
<script>
</script>
@endpush
