{{--
  Global "Connect Repository" modal.
  Usage: @include('_partials.create-repository-modal')

  To open from anywhere, dispatch the custom event:
    window.dispatchEvent(new CustomEvent('open-repo-modal'))

--}}
<div x-data="connectRepoModal({
    oauthConnections: @js($oauthConnections ?? []),
    oauthProvider: @js(request('oauth_provider')),
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
                     :style="( ['provider', 'auth', 'details', 'done'].indexOf(progressStep) >= i ) ? 'background:var(--gradient-brand)' : 'background:hsl(var(--border))'">
                </div>
              </div>
            </template>
          </div>
        </div>

        {{-- Body --}}  
        <div class="px-6 py-5 max-h-[60vh] overflow-y-auto scrollbar-thin">
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

          {{-- STEP: local options --}}
          <template x-if="step === 'local-options'">
            <div class="space-y-4">
              <p class="text-sm" style="color:hsl(var(--muted-foreground))">Choose how Cybix should connect to this local repository.</p>
              <div class="grid gap-3 md:grid-cols-3">
                <template x-for="option in localOptions" :key="option.id">
                  <button @click="pickLocalOption(option.id)"
                          class="text-left rounded-xl border p-4 transition-base hover:shadow-soft"
                          style="background:hsl(var(--card));border-color:hsl(var(--border)/0.7)"
                          onmouseenter="this.style.borderColor='hsl(var(--primary)/0.4)'"
                          onmouseleave="this.style.borderColor='hsl(var(--border)/0.7)'">
                    <div class="h-11 w-11 rounded-lg brand-soft-bg flex items-center justify-center mb-4"
                         style="color:hsl(var(--primary))" x-html="option.icon"></div>
                    <div class="text-sm font-semibold mb-1" style="color:hsl(var(--foreground))" x-text="option.title"></div>
                    <div class="text-xs leading-5" style="color:hsl(var(--muted-foreground))" x-text="option.description"></div>
                  </button>
                </template>
              </div>
            </div>
          </template>

          {{-- STEP: Cybix Agent placeholder --}}
          <template x-if="step === 'local-agent'">
            <div class="space-y-5">
              <button @click="step = 'local-options'"
                      class="inline-flex items-center gap-2 rounded-md text-sm font-medium transition-base h-9 px-2"
                      style="color:hsl(var(--foreground))"
                      onmouseenter="this.style.background='hsl(var(--accent))'"
                      onmouseleave="this.style.background='transparent'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                Back
              </button>
              <div class="py-10 flex flex-col items-center justify-center gap-4 text-center">
                <div class="text-base font-semibold" style="color:hsl(var(--foreground))">Cybix Agent coming soon. Stay tuned.</div>
                <button disabled class="brand-gradient-bg inline-flex items-center justify-center rounded-md text-sm font-medium h-9 px-4 opacity-50 cursor-not-allowed">Continue</button>
              </div>
            </div>
          </template>

          {{-- STEP: SSH Access --}}
          <template x-if="step === 'local-ssh'">
            <div class="space-y-4">
              <button @click="step = 'local-options'"
                      class="inline-flex items-center gap-2 rounded-md text-sm font-medium transition-base h-9 px-2"
                      style="color:hsl(var(--foreground))"
                      onmouseenter="this.style.background='hsl(var(--accent))'"
                      onmouseleave="this.style.background='transparent'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                Back
              </button>

              <div class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                  <label class="text-sm font-medium leading-none" style="color:hsl(var(--foreground))">Step 1 — Add this key to your machine</label>
                  <button @click="copySshKey()" type="button"
                          class="inline-flex items-center justify-center rounded-md border text-xs font-medium h-8 px-3"
                          style="background:hsl(var(--background));border-color:hsl(var(--border));color:hsl(var(--foreground))"
                          x-text="sshCopyLabel"></button>
                </div>
                <textarea readonly rows="4" x-model="sshPublicKey"
                          :placeholder="sshKeyLoading ? 'Loading public key...' : 'Server public key unavailable'"
                          class="w-full rounded-md border text-xs font-mono shadow-sm"
                          style="background:hsl(var(--secondary)/0.35);border-color:hsl(var(--input, var(--border)));padding:0.75rem;color:hsl(var(--foreground))"></textarea>
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium leading-none" style="color:hsl(var(--foreground))">Step 2 — Enter your machine's IP address</label>
                <input type="text" x-model="sshIp" placeholder="e.g. 192.168.1.50" class="flex h-9 w-full rounded-md border text-sm shadow-sm"
                       style="background:transparent;border-color:hsl(var(--input, var(--border)));padding-left:0.75rem;padding-right:0.75rem;color:hsl(var(--foreground))">
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium leading-none" style="color:hsl(var(--foreground))">Step 3 — Enter the full path to your repository</label>
                <input type="text" x-model="sshPath" placeholder="e.g. /home/john/projects/myrepo or C:/Users/john/myrepo" class="flex h-9 w-full rounded-md border text-sm shadow-sm"
                       style="background:transparent;border-color:hsl(var(--input, var(--border)));padding-left:0.75rem;padding-right:0.75rem;color:hsl(var(--foreground))">
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium leading-none" style="color:hsl(var(--foreground))">Repository name</label>
                <input type="text" x-model="sshName" placeholder="e.g. my-project" class="flex h-9 w-full rounded-md border text-sm shadow-sm"
                       style="background:transparent;border-color:hsl(var(--input, var(--border)));padding-left:0.75rem;padding-right:0.75rem;color:hsl(var(--foreground))">
              </div>

              <button @click="connectSsh()" :disabled="sshLoading || !canSubmitSsh"
                      class="brand-gradient-bg shadow-soft inline-flex w-full items-center justify-center gap-2 rounded-md text-sm font-medium transition-base h-10 px-3 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg x-show="sshLoading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                <span x-text="sshLoading ? 'Testing connection...' : 'Test Connection & Connect'"></span>
              </button>

              <div x-show="sshError" x-cloak class="rounded-lg border px-3 py-2 text-sm"
                   style="border-color:hsl(var(--failed)/0.30);color:hsl(var(--failed));background:hsl(var(--failed)/0.05)" x-text="sshError"></div>
            </div>
          </template>

          {{-- STEP: Upload Repository --}}
          <template x-if="step === 'local-upload'">
            <div class="space-y-4">
              <button @click="step = 'local-options'"
                      class="inline-flex items-center gap-2 rounded-md text-sm font-medium transition-base h-9 px-2"
                      style="color:hsl(var(--foreground))"
                      onmouseenter="this.style.background='hsl(var(--accent))'"
                      onmouseleave="this.style.background='transparent'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                Back
              </button>

              <div class="grid gap-3 sm:grid-cols-2">
                <label class="rounded-xl border p-4 cursor-pointer transition-base hover:shadow-soft" style="border-color:hsl(var(--border)/0.7)">
                  <input type="file" webkitdirectory multiple class="hidden" @change="handleFolderSelection($event)">
                  <div class="text-sm font-semibold mb-1" style="color:hsl(var(--foreground))">Browse Folder</div>
                  <div class="text-xs leading-5" style="color:hsl(var(--muted-foreground))">Zip a selected folder in your browser before uploading.</div>
                </label>
                <label class="rounded-xl border p-4 cursor-pointer transition-base hover:shadow-soft" style="border-color:hsl(var(--border)/0.7)">
                  <input type="file" accept=".zip,.bundle" class="hidden" @change="handleArchiveSelection($event)">
                  <div class="text-sm font-semibold mb-1" style="color:hsl(var(--foreground))">Upload ZIP or Bundle</div>
                  <div class="text-xs leading-5" style="color:hsl(var(--muted-foreground))">Use a prepared archive or Git bundle file.</div>
                </label>
              </div>

              <div class="rounded-lg border border-dashed p-3 text-xs" style="border-color:hsl(var(--border));background:hsl(var(--secondary)/0.35);color:hsl(var(--muted-foreground))">
                Note: some browsers may exclude hidden folders like .git when browsing. For full git history, use Upload ZIP instead.
              </div>

              <div x-show="zipLoading" x-cloak class="text-sm" style="color:hsl(var(--muted-foreground))" x-text="zipProgress"></div>

              <div class="space-y-2">
                <label class="text-sm font-medium leading-none" style="color:hsl(var(--foreground))">Repository name</label>
                <input type="text" x-model="uploadName" placeholder="e.g. my-project" class="flex h-9 w-full rounded-md border text-sm shadow-sm"
                       style="background:transparent;border-color:hsl(var(--input, var(--border)));padding-left:0.75rem;padding-right:0.75rem;color:hsl(var(--foreground))">
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium leading-none" style="color:hsl(var(--foreground))">Owner's Local Path</label>
                <input type="text" x-model="uploadLocalPath" placeholder="e.g. H:\xampp\htdocs\test-1 or /home/john/projects/test-1" class="flex h-9 w-full ro              <div x-show="uploadFile" x-cloak class="space-y-2">
                <div class="flex items-center justify-between text-xs" style="color:hsl(var(--muted-foreground))">
                  <span x-text="uploadFile?.name"></span>
                  <span x-text="`${uploadProgress}%`"></span>
                </div>
                <div class="h-2 rounded-full overflow-hidden" style="background:hsl(var(--border))">
                  <div class="h-full transition-all" :style="`width:${uploadProgress}%;background:var(--gradient-brand)`"></div>
                </div>
              </div>

              <button @click="uploadRepository()" :disabled="uploadLoading || !canSubmitUpload"
                      class="brand-gradient-bg shadow-soft inline-flex w-full items-center justify-center rounded-md text-sm font-medium transition-base h-10 px-3 disabled:opacity-50 disabled:cursor-not-allowed"
                      x-text="uploadLoading ? 'Uploading...' : 'Connect Uploaded Repository'"></button>

              <div x-show="uploadError" x-cloak class="rounded-lg border px-3 py-2 text-sm"
                   style="border-color:hsl(var(--failed)/0.30);color:hsl(var(--failed));background:hsl(var(--failed)/0.05)" x-text="uploadError"></div>
            </div>
          </template>

          {{-- STEP: details --}}
          <template x-if="step === 'details' && provider">
            <div class="space-y-4">
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

            <template x-if="showFooterBack">
                <button @click="goBack()"
                        class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium transition-base h-9 px-3"
                        style="color:hsl(var(--foreground))"
                        onmouseenter="this.style.background='hsl(var(--accent))'"
                        onmouseleave="this.style.background='transparent'">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m15 18-6-6 6-6"/></svg>
                  Back
                </button>
            </template>

            <template x-if="!showFooterBack">
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" defer></script>
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

    authMethod: 'oauth',
    token: '',
    host: '',
    localPath: '',
    repoUrl: '',
    sshPublicKey: '',
    sshKeyLoading: false,
    sshCopyLabel: 'Copy',
    sshIp: '',
    sshPath: '',
    sshName: '',
    sshError: '',
    sshLoading: false,
    uploadFile: null,
    uploadName: '',
    uploadError: '',
    uploadLoading: false,
    uploadProgress: 0,
    zipLoading: false,
    zipProgress: '',

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

    localOptions: [
      {
        id: 'agent',
        title: 'Cybix Agent',
        description: 'Install our lightweight background app for automatic syncing',
        icon: `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8V4m-4 8H4m16 0h-4m-8 4v4m8-9a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6"/></svg>`,
      },
      {
        id: 'ssh',
        title: 'SSH Access',
        description: 'Give the server SSH access to pull directly from your machine',
        icon: `<svg class="h-5 w-5" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="currentColor" stroke="currentColor"><g transform="scale(0.3287671233)"><g id="databases-and-servers/servers/ssh" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="terminal" transform="translate(11.000000, 17.000000)" fill="currentColor" fill-rule="nonzero"><path d="M49.2407485,0.0215730994 L1.65983626,0.0215730994 C0.743128655,0.0215730994 0,0.76480117 0,1.68140936 L0,9.94480117 L50.9005848,9.94480117 L50.9005848,1.68140936 C50.9005848,0.76480117 50.1574561,0.0215730994 49.2407485,0.0215730994 Z M32.0399298,7.19626901 C30.8196082,7.19626901 29.826848,6.20350877 29.826848,4.98318713 C29.826848,3.7628655 30.8196082,2.77010526 32.0399298,2.77010526 C33.2602515,2.77010526 34.2530117,3.7628655 34.2530117,4.98318713 C34.2530117,6.20350877 33.2602515,7.19626901 32.0399298,7.19626901 Z M38.0686667,7.19626901 C36.848345,7.19626901 35.8555848,6.20350877 35.8555848,4.98318713 C35.8555848,3.7628655 36.848345,2.77010526 38.0686667,2.77010526 C39.2889883,2.77010526 40.2817485,3.7628655 40.2817485,4.98318713 C40.2817485,6.20350877 39.2889883,7.19626901 38.0686667,7.19626901 Z M44.0974035,7.19626901 C42.8770819,7.19626901 41.8843216,6.20350877 41.8843216,4.98318713 C41.8843216,3.7628655 42.8770819,2.77010526 44.0974035,2.77010526 C45.3177251,2.77010526 46.3104854,3.7628655 46.3104854,4.98318713 C46.3104854,6.20350877 45.3177251,7.19626901 44.0974035,7.19626901 Z"></path><path d="M0,13.2643743 L0,37.0905205 C0,38.0071287 0.743128655,38.7503567 1.65983626,38.7503567 L49.240848,38.7503567 C50.1574561,38.7503567 50.9006842,38.0072281 50.9006842,37.0905205 L50.9006842,13.2643743 L0,13.2643743 Z M23.4073099,27.2714795 L17.9739708,31.8946842 C17.2758772,32.4887895 16.2283392,32.404386 15.6342339,31.706193 C15.0401287,31.0080994 15.1245322,29.9605614 15.8227251,29.3664561 L19.7704035,26.0073158 L15.8227251,22.6481754 C15.1245322,22.0540702 15.0402281,21.0066316 15.6342339,20.3084386 C16.2282398,19.6101462 17.2758772,19.5259415 17.9739708,20.1199474 L23.4073099,24.743152 C24.185731,25.4055556 24.185731,26.6091754 23.4073099,27.2714795 Z M34.0021871,32.2903567 L27.4453567,32.2903567 C26.5287485,32.2903567 25.7855205,31.5472281 25.7855205,30.6305205 C25.7855205,29.7138129 26.5286491,28.9706842 27.4453567,28.9706842 L34.0021871,28.9706842 C34.9187953,28.9706842 35.6620234,29.7138129 35.6620234,30.6305205 C35.6620234,31.5472281 34.9187953,32.2903567 34.0021871,32.2903567 Z"></path></g></g></g></svg>`,
      },
      {
        id: 'upload',
        title: 'Upload Repository',
        description: 'Upload a ZIP archive or Git bundle of your local repository',
        icon: `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0 4 4m-4-4-4 4"/><path stroke-linecap="round" stroke-linejoin="round" d="M20 16v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2"/></svg>`,
      },
    ],

    get progressStep() {
      if (this.step === 'local-options') return 'auth';
      if (['local-agent', 'local-ssh', 'local-upload'].includes(this.step)) return 'details';
      return this.step === 'verifying' ? 'details' : this.step;
    },

    get showFooterBack() {
      return ['auth', 'details', 'local-options'].includes(this.step);
    },

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

    get canSubmitSsh() {
      return this.sshIp.trim() !== '' && this.sshPath.trim() !== '' && this.sshName.trim() !== '';
    },

    get canSubmitUpload() {
      return this.uploadFile && this.uploadName.trim() !== '';
    },

    init() {
      window.addEventListener('open-repo-modal', () => {
        this.openModal();
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
      this.repoUrl = '';
      this.token = '';
      this.host = '';
      this.localPath = '';
      this.authMethod = 'oauth';
      this.error = '';
      this.loading = false;
      this.sshPublicKey = '';
      this.sshKeyLoading = false;
      this.sshCopyLabel = 'Copy';
      this.sshIp = '';
      this.sshPath = '';
      this.sshName = '';
      this.sshError = '';
      this.sshLoading = false;
      this.uploadFile = null;
      this.uploadName = '';
      this.uploadError = '';
      this.uploadLoading = false;
      this.uploadProgress = 0;
      this.zipLoading = false;
      this.zipProgress = '';
    },
    pickProvider(provider) {
      this.provider = provider;
      this.authMethod = 'oauth';
      this.token = '';
      this.error = '';
      this.step = provider.id === 'local-pc' ? 'local-options' : 'auth';
    },
    goBack() {
      if (this.step === 'details') {
        this.step = 'auth';
        return;
      }

      this.step = 'provider';
    },
    pickLocalOption(option) {
      this.error = '';
      if (option === 'agent') {
        this.step = 'local-agent';
        return;
      }

      if (option === 'ssh') {
        this.step = 'local-ssh';
        this.loadSshPublicKey();
        return;
      }

      this.step = 'local-upload';
    },
    async loadSshPublicKey() {
      if (this.sshPublicKey || this.sshKeyLoading) return;

      this.sshKeyLoading = true;
      this.sshError = '';

      try {
        const response = await fetch('/api/repositories/ssh-public-key', {
          headers: { 'Accept': 'application/json' },
        });
        const payload = await this.parseJson(response);

        if (!response.ok) {
          throw new Error(this.extractErrorMessage(payload, 'Could not load the server SSH key.'));
        }

        this.sshPublicKey = payload?.public_key || '';
      } catch (error) {
        this.sshError = error.message || 'Could not load the server SSH key.';
      } finally {
        this.sshKeyLoading = false;
      }
    },
    async copySshKey() {
      if (!this.sshPublicKey) return;

      await navigator.clipboard.writeText(this.sshPublicKey);
      this.sshCopyLabel = 'Copied!';
      setTimeout(() => this.sshCopyLabel = 'Copy', 1500);
    },
    async connectSsh() {
      if (!this.canSubmitSsh || this.sshLoading) return;

      this.sshLoading = true;
      this.sshError = '';

      try {
        const response = await fetch('/api/repositories/connect-ssh', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
          },
          body: JSON.stringify({
            ip: this.sshIp.trim(),
            name: this.sshName.trim(),
            path: this.sshPath.trim(),
          }),
        });
        const payload = await this.parseJson(response);

        if (!response.ok) {
          throw new Error(this.extractErrorMessage(payload, 'SSH connection failed.'));
        }

        this.closeModal(false);
        sessionStorage.setItem('flash_toast_msg', 'Repository connected successfully.');
        sessionStorage.setItem('flash_toast_type', 'success');
        window.location.reload();
      } catch (error) {
        this.sshError = error.message || 'SSH connection failed.';
      } finally {
        this.sshLoading = false;
      }
    },
    async handleFolderSelection(event) {
      const files = Array.from(event.target.files || []);
      if (!files.length) return;

      if (!window.JSZip) {
        this.uploadError = 'The ZIP helper could not load. Try Upload ZIP instead.';
        return;
      }

      this.zipLoading = true;
      this.uploadError = '';
      this.uploadProgress = 0;

      try {
        const zip = new JSZip();
        files.forEach((file) => {
          zip.file(file.webkitRelativePath || file.name, file);
        });

        const rootName = (files[0].webkitRelativePath || '').split('/')[0] || 'repository';
        this.uploadName = this.uploadName || rootName;
        const blob = await zip.generateAsync({ type: 'blob' }, (metadata) => {
          this.zipProgress = `Zipping ${files.length} files... ${Math.round(metadata.percent)}%`;
        });

        this.uploadFile = new File([blob], `${this.uploadName || rootName}.zip`, { type: 'application/zip' });
      } catch (error) {
        this.uploadError = 'Could not zip the selected folder.';
      } finally {
        this.zipLoading = false;
      }
    },
    handleArchiveSelection(event) {
      const file = event.target.files?.[0];
      if (!file) return;

      this.uploadFile = file;
      this.uploadError = '';
      this.uploadProgress = 0;
      this.uploadName = this.uploadName || file.name.replace(/\.(zip|bundle)$/i, '');
    },
    uploadRepository() {
      if (!this.canSubmitUpload || this.uploadLoading) return;

      this.uploadLoading = true;
      this.uploadError = '';
      this.uploadProgress = 0;

      const formData = new FormData();
      formData.append('file', this.uploadFile);
nd(formData);
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
    safeParseJson(value) {
      try {
        return JSON.parse(value);
      } catch (error) {
        return null;
      }
    },
  };
}
</script>
