{{--
  Global "Connect Repository" modal.
  Usage: @include('_partials.create-repository-modal')

  To open from anywhere, dispatch the custom event:
    window.dispatchEvent(new CustomEvent('open-repo-modal'))

  Optional: pre-select a project by passing a detail:
    window.dispatchEvent(new CustomEvent('open-repo-modal', { detail: { projectId: 5 } }))
--}}
<div x-data="connectRepoModal({
    oauthConnections: @js($oauthConnections ?? []),
    oauthProvider: @js(request('oauth_provider')),
    projects: @js($repositoryProjectOptions ?? $projects ?? []),
})"
x-init="init()">
  <template x-teleport="body">
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
          <div x-show="error"
               x-cloak
               class="mb-4 rounded-xl border px-3 py-2 text-sm"
               style="border-color:hsl(var(--failed)/0.30);color:hsl(var(--failed));background:hsl(var(--failed)/0.05)"
               x-text="error"></div>

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
                  <template x-for="project in projects" :key="project.id">
                    <option :value="String(project.id)" x-text="project.name"></option>
                  </template>
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
                            onmouseenter="if(!this.disabled) this.classList.add('shadow-md')"
                            onmouseleave="this.classList.remove('shadow-md')">
                        Continue
                    </button>
                </template>

                <template x-if="step === 'details'">
                    <button @click="handleVerify()"
                            :disabled="!canSubmitDetails"
                            class="brand-gradient-bg shadow-soft inline-flex items-center justify-center rounded-md text-sm font-medium transition-base h-9 px-3 disabled:opacity-50 disabled:cursor-not-allowed"
                            onmouseenter="if(!this.disabled) this.classList.add('shadow-md')"
                            onmouseleave="this.classList.remove('shadow-md')">
                        Verify &amp; connect
                    </button>
                </template>

                <template x-if="step === 'done'">
                    <button @click="handleFinish()"
                            class="brand-gradient-bg shadow-soft inline-flex items-center justify-center rounded-md text-sm font-medium transition-base h-9 px-3"
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
  </template>
</div>

<script>
function connectRepoModal(config = {}) {
  const pendingConnectionKey = 'repositories.pending-connection';

  return {
    modal: false,
    step: 'provider',
    provider: null,
    loading: false,
    error: '',
    oauthConnections: config.oauthConnections ?? {},
    oauthProvider: config.oauthProvider ?? null,
    projects: config.projects ?? [],

    authMethod: 'oauth',
    token: '',
    host: '',
    localPath: '',
    projectId: '',
    repoUrl: '',

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
        authLabel: 'OAuth (recommended) or Personal Access Token',
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

    get canSubmitAuth() {
      if (!this.provider) return false;
      if (this.provider.authMethod === 'github' || this.provider.authMethod === 'gitlab') {
        return this.authMethod === 'oauth' || this.token.trim() !== '';
      }
      return true;
    },

    get canSubmitDetails() {
      if (this.loading) return false;
      if (this.provider?.authMethod === 'path') return this.localPath.trim() !== '';
      if (this.provider?.authMethod === 'ssh') return this.host.trim() !== '' && this.repoUrl.trim() !== '';
      return this.repoUrl.trim() !== '';
    },

    init() {
      window.addEventListener('open-repo-modal', (e) => {
        this.openModal();
        if (e?.detail?.projectId) {
          this.projectId = String(e.detail.projectId);
        }
      });

      this.resumeOAuthFlowIfNeeded();

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.modal && this.step !== 'verifying') {
          this.closeModal(false);
        }
      });
    },

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
      this.token = '';
      this.host = '';
      this.localPath = '';
      this.authMethod = 'oauth';
      this.error = '';
      this.loading = false;
    },
    pickProvider(provider) {
      this.provider = provider;
      this.authMethod = 'oauth';
      this.token = '';
      this.error = '';
      this.step = 'auth';
    },
    async handleVerify() {
      if (!this.canSubmitDetails) return;

      this.error = '';
      this.loading = true;
      this.step = 'verifying';

      try {
        const response = await fetch('{{ route('repositories.store') }}', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
          },
          body: JSON.stringify(this.buildPayload()),
        });

        const payload = await this.parseJson(response);

        if (response.status === 409 && payload?.requires_oauth && payload?.redirect_url) {
          this.savePendingConnectionState();
          window.location.href = payload.redirect_url;
          return;
        }

        if (!response.ok) {
          throw new Error(this.extractErrorMessage(payload, 'Repository connection failed.'));
        }

        this.clearPendingConnectionState();
        if ((this.provider?.id === 'github' || this.provider?.id === 'gitlab') && this.authMethod === 'oauth') {
          this.oauthConnections[this.provider.id] = true;
        }

        this.step = 'done';
      } catch (error) {
        this.error = error.message || 'Repository connection failed.';
        this.step = 'details';
      } finally {
        this.loading = false;
      }
    },
    handleFinish() {
      this.closeModal(false);
      this.clearPendingConnectionState();
      sessionStorage.setItem('flash_toast_msg', 'Repository connected successfully.');
      sessionStorage.setItem('flash_toast_type', 'success');
      window.location.reload();
    },

    buildPayload() {
      const isLocal = this.provider?.id === 'local-pc';
      const isCompanyServer = this.provider?.id === 'company-server';
      const repositoryValue = isLocal ? this.localPath.trim() : this.repoUrl.trim();

      return {
        access_token: this.authMethod === 'pat' ? this.token.trim() : null,
        auth_method: this.provider?.id === 'github' || this.provider?.id === 'gitlab'
          ? this.authMethod
          : null,
        name: repositoryValue,
        project_id: this.projectId || null,
        provider: this.provider?.id,
        server_host: isCompanyServer ? this.host.trim() : null,
        server_path: isCompanyServer ? repositoryValue : null,
        server_protocol: isCompanyServer ? 'SSH' : null,
        url: isLocal ? this.localPath.trim() : this.repoUrl.trim(),
      };
    },
    savePendingConnectionState() {
      sessionStorage.setItem(pendingConnectionKey, JSON.stringify({
        host: this.host,
        localPath: this.localPath,
        projectId: this.projectId,
        providerId: this.provider?.id ?? null,
        repoUrl: this.repoUrl,
      }));
    },
    loadPendingConnectionState() {
      const state = sessionStorage.getItem(pendingConnectionKey);
      if (!state) return null;
      try {
        return JSON.parse(state);
      } catch (error) {
        this.clearPendingConnectionState();
        return null;
      }
    },
    clearPendingConnectionState() {
      sessionStorage.removeItem(pendingConnectionKey);
    },
    resumeOAuthFlowIfNeeded() {
      const pendingState = this.loadPendingConnectionState();
      const providerId = this.oauthProvider || new URLSearchParams(window.location.search).get('oauth_provider');

      if (!pendingState || !providerId) return;

      const provider = this.providers.find((item) => item.id === pendingState.providerId);
      this.clearPendingConnectionState();

      if (!provider || provider.id !== providerId) {
        this.removeOAuthProviderQuery();
        return;
      }

      this.openModal();
      this.provider = provider;
      this.authMethod = 'oauth';
      this.host = pendingState.host || '';
      this.localPath = pendingState.localPath || '';
      this.projectId = pendingState.projectId || '';
      this.repoUrl = pendingState.repoUrl || '';
      this.step = 'details';
      this.oauthConnections[provider.id] = true;
      this.removeOAuthProviderQuery();
      this.emitToast('success', `${provider.name} account connected. Finish choosing the repository to add.`);
    },
    removeOAuthProviderQuery() {
      const url = new URL(window.location.href);
      url.searchParams.delete('oauth_provider');
      window.history.replaceState({}, document.title, `${url.pathname}${url.search}`);
    },
    emitToast(type, message) {
      window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
    },
    extractErrorMessage(payload, fallback) {
      if (!payload) return fallback;
      if (payload.message) return payload.message;
      if (payload.errors && typeof payload.errors === 'object') {
        const firstError = Object.values(payload.errors).flat()[0];
        if (firstError) return firstError;
      }
      return fallback;
    },
    async parseJson(response) {
      const contentType = response.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) return null;
      return response.json();
    },
  };
}
</script>
