{{-- ── Repository detail bottom-sheet ───────────────────────────────────── --}}
<template x-if="selectedRepository">
    <div>
        <template x-teleport="body">
            <div class="fixed inset-0 z-40" @keydown.escape.window="selectedId = null"
                @click="selectedId = null">
                <div class="absolute inset-0 bg-black/65 backdrop-blur-[2px]" @click="selectedId = null"></div>

                <div
                    class="absolute inset-x-0 bottom-0 flex max-h-screen items-end justify-center px-3 pt-8 sm:px-6 sm:pt-12">
                    <article @click.stop
                        class="section-card text-left relative overflow-x-hidden overflow-y-auto ring-primary shadow-soft p-0 z-10 w-full max-w-7xl h-[92vh] max-h-[calc(100vh-1rem)] rounded-t-2xl sm:rounded-2xl flex flex-col"
                        role="dialog" aria-modal="true">
                        <div
                            class="absolute -top-12 -right-12 h-32 w-32 rounded-full bg-primary/10 blur-2xl pointer-events-none">
                        </div>

                        {{-- ── Sheet header ──────────────────────────────────────── --}}
                        <div class="relative p-5 border-b border-border/60 brand-soft-bg">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div
                                    class="rounded-lg brand-soft-bg shadow-soft flex items-center justify-center shrink-0 h-14 w-14 text-primary"
                                    x-html="providerIcon(selectedRepository.provider)">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h2 class="text-2xl font-semibold truncate"
                                            x-text="selectedRepository.label"></h2>
                                        <span
                                            class="inline-flex items-center gap-1.5 text-[10px] font-semibold px-2 py-0.5 rounded-md border"
                                            :class="statusBadgeClass(selectedRepository.status)">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                            <span x-text="selectedRepository.statusLabel"></span>
                                        </span>
                                    </div>

                                    <a x-show="selectedRepository.url" :href="selectedRepository.url"
                                        target="_blank" rel="noreferrer"
                                        class="text-sm text-muted-foreground hover:text-primary inline-flex items-center gap-1 mt-1 break-all"
                                        x-text="selectedRepository.url">
                                    </a>

                                    <div class="mt-3 flex flex-wrap items-center gap-1.5 ">
                                        {{-- -
                                        <span x-show="selectedRepository.ownerName"
                                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold pl-1 pr-2 py-0.5 rounded-full border border-primary/30 bg-primary/5 text-foreground">
                                            <span class="relative flex shrink-0 overflow-hidden rounded-full h-4 w-4">
                                                <span
                                                    class="flex h-full w-full items-center justify-center rounded-full bg-muted brand-gradient-bg text-[hsl(var(--on-brand))] text-[8px] font-semibold"
                                                    x-text="selectedRepository.ownerInitials"></span>
                                            </span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="lucide lucide-crown h-2.5 w-2.5 text-primary">
                                                <path
                                                    d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z">
                                                </path>
                                                <path d="M5 21h14"></path>
                                            </svg>
                                            <span x-text="`Owner - ${selectedRepository.ownerName}`"></span>
                                        </span>
                                        --}}
                                        <span
                                            class="text-[11px] font-medium px-2 py-0.5 rounded bg-secondary/70 text-muted-foreground inline-flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="lucide lucide-git-branch h-3 w-3">
                                                <line x1="6" x2="6" y1="3" y2="15"></line>
                                                <circle cx="18" cy="6" r="3"></circle>
                                                <circle cx="6" cy="18" r="3"></circle>
                                                <path d="M18 9a9 9 0 0 1-9 9"></path>
                                            </svg>
                                            <span
                                                x-text="selectedRepository.branchCount + ' branches'"></span>
                                        </span>
                                        <span
                                            class="text-[11px] font-medium px-2 py-0.5 rounded bg-secondary/70 text-muted-foreground inline-flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="lucide lucide-tag h-3 w-3">
                                                <path
                                                    d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z">
                                                </path>
                                                <circle cx="7.5" cy="7.5" r=".5" fill="currentColor">
                                                </circle>
                                            </svg>
                                            <span x-text="selectedRepository.tagCount + ' tags'"></span>
                                        </span>
                                        <span
                                            class="text-[11px] font-medium px-2 py-0.5 rounded bg-secondary/70 text-muted-foreground inline-flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="lucide lucide-users h-3 w-3">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2">
                                                </path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                            </svg>
                                            <span x-text="memberLabel(selectedRepository)"></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1" @click.stop>
                                    <a x-show="selectedRepository.canCreatePackage"
                                        :href="createPackageUrl(selectedRepository)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-base brand-gradient-bg text-[hsl(var(--on-brand))]"
                                        title="Create package">
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16 16h6M19 13v6M21 10V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l2-1.14M7.5 4.27l9 5.15">
                                            </path>
                                            <polyline stroke-linecap="round" stroke-linejoin="round"
                                                points="3.29 7 12 12 20.71 7">
                                            </polyline>
                                            <line stroke-linecap="round" stroke-linejoin="round" x1="12"
                                                x2="12" y1="22" y2="12"></line>
                                        </svg>
                                        Create package
                                    </a>
                                    <button type="button" x-show="selectedRepository.canManageRepository"
                                        @click="handleRepositoryRefresh(selectedRepository)"
                                        :disabled="syncing === selectedRepository.id"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm hover:bg-accent hover:text-accent-foreground transition-colors disabled:opacity-50"
                                        :title="repositoryRefreshTitle(selectedRepository)">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                                            :class="syncing === selectedRepository.id ? 'animate-spin' : ''"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                                            <path d="M3 3v5h5" />
                                            <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
                                            <path d="M16 16h5v5" />
                                        </svg>
                                    </button>
                                    <button type="button" x-show="selectedRepository.canManageRepository"
                                        @click="removeRepo(selectedRepository)"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm text-failed hover:bg-failed/10 transition-colors"
                                        title="Remove repository">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 6h18" />
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="selectedId = null"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm hover:bg-accent hover:text-accent-foreground transition-colors"
                                        title="Close">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 6 6 18" />
                                            <path d="m6 6 12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- ── Sheet body: connection + members ──────────────────── --}}
                        <div
                            class="grid grid-cols-1 lg:grid-cols-10 divide-y lg:divide-y-0 lg:divide-x divide-border/60 flex-1 overflow-hidden">

                            {{-- Connection panel --}}
                            <div class="lg:col-span-3 p-5 overflow-y-auto scrollbar-thin">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-semibold inline-flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4 text-primary" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                                        </svg>
                                        Connection
                                    </h4>
                                </div>

                                <dl class="space-y-3 text-xs">
                                    <div class="rounded-lg border border-border/60 p-3">
                                        <dt
                                            class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                            Connection type</dt>
                                        <dd class="mt-1 font-medium"
                                            x-text="selectedRepository.authType"></dd>
                                    </div>
                                    <div class="rounded-lg border border-border/60 p-3">
                                        <dt
                                            class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                            Synced</dt>
                                        <dd class="mt-1 font-medium"
                                            x-text="selectedRepository.lastSyncedLabel"></dd>
                                    </div>
                                    <div class="rounded-lg border border-border/60 p-3">
                                        <dt
                                            class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                            Slug</dt>
                                        <dd class="mt-1 font-mono break-all"
                                            x-text="selectedRepository.slug"></dd>
                                    </div>
                                    <div class="rounded-lg border border-border/60 p-3">
                                        <dt
                                            class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                            Default branch</dt>
                                        <dd class="mt-1 font-medium"
                                            x-text="selectedRepository.defaultBranch"></dd>
                                    </div>
                                    <div x-show="selectedRepository.serverHost"
                                        class="rounded-lg border border-border/60 p-3">
                                        <dt
                                            class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                            Host</dt>
                                        <dd class="mt-1 font-mono break-all"
                                            x-text="selectedRepository.serverHost"></dd>
                                    </div>
                                </dl>

                                <div x-show="selectedRepository.credentialsError"
                                    x-text="selectedRepository.credentialsError"
                                    class="mt-4 rounded-lg border px-3 py-2 text-xs"
                                    style="border-color:hsl(var(--failed)/0.30);color:hsl(var(--failed));background:hsl(var(--failed)/0.05)">
                                </div>

                                <div class="mt-4 space-y-2" x-show="selectedRepository.canManageRepository">
                                    <button type="button"
                                        @click="handleRepositoryRefresh(selectedRepository)"
                                        :disabled="syncing === selectedRepository.id"
                                        class="w-full inline-flex h-9 items-center justify-center gap-2 rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent disabled:opacity-50">
                                        <svg class="h-3.5 w-3.5"
                                            :class="syncing === selectedRepository.id ? 'animate-spin' : ''"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span
                                            x-text="syncing === selectedRepository.id ? repositoryRefreshLoadingLabel(selectedRepository) : repositoryRefreshLabel(selectedRepository)"></span>
                                    </button>

                                    <template
                                        x-if="selectedRepository.provider === 'github' || selectedRepository.provider === 'gitlab'">
                                        <div class="grid grid-cols-1 gap-2">
                                            <button type="button" @click="reconnectOauth(selectedRepository)"
                                                class="inline-flex h-9 items-center justify-center rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent">
                                                Reconnect OAuth
                                            </button>
                                            <button type="button"
                                                @click="startCredentialEdit(selectedRepository, 'pat')"
                                                class="inline-flex h-9 items-center justify-center rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent">
                                                Change PAT
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="selectedRepository.provider === 'company-server'">
                                        <button type="button"
                                            @click="startCredentialEdit(selectedRepository, 'ssh')"
                                            class="w-full inline-flex h-9 items-center justify-center rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent">
                                            Update SSH connection
                                        </button>
                                    </template>
                                </div>

                                {{-- PAT credential form --}}
                                <div x-show="selectedRepository.credentialMode === 'pat'"
                                    class="mt-4 rounded-lg border border-border/60 p-3 space-y-3">
                                    <label class="block text-xs font-medium">New personal access token</label>
                                    <input type="password" x-model="selectedRepository.credentialToken"
                                        class="h-9 w-full rounded-md border border-border bg-background px-3 text-xs outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20"
                                        placeholder="Paste token">
                                    <div class="flex gap-2">
                                        <button type="button"
                                            @click="saveRepositoryCredentials(selectedRepository)"
                                            :disabled="selectedRepository.credentialsSaving || !selectedRepository.credentialToken"
                                            class="inline-flex h-8 flex-1 items-center justify-center rounded-md brand-gradient-bg px-3 text-xs font-medium text-[hsl(var(--on-brand))] disabled:opacity-50">
                                            Save
                                        </button>
                                        <button type="button"
                                            @click="cancelCredentialEdit(selectedRepository)"
                                            class="inline-flex h-8 items-center justify-center rounded-md border border-border px-3 text-xs">
                                            Cancel
                                        </button>
                                    </div>
                                </div>

                                {{-- SSH credential form --}}
                                <div x-show="selectedRepository.credentialMode === 'ssh'"
                                    class="mt-4 rounded-lg border border-border/60 p-3 space-y-3">
                                    <label class="block text-xs font-medium">Host</label>
                                    <input type="text" x-model="selectedRepository.credentialHost"
                                        class="h-9 w-full rounded-md border border-border bg-background px-3 text-xs outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20"
                                        placeholder="git.company.internal">
                                    <label class="block text-xs font-medium">Repository path</label>
                                    <input type="text" x-model="selectedRepository.credentialPath"
                                        class="h-9 w-full rounded-md border border-border bg-background px-3 text-xs outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20"
                                        placeholder="group/repository.git">
                                    <div class="flex gap-2">
                                        <button type="button"
                                            @click="saveRepositoryCredentials(selectedRepository)"
                                            :disabled="selectedRepository.credentialsSaving || !selectedRepository.credentialHost || !selectedRepository.credentialPath"
                                            class="inline-flex h-8 flex-1 items-center justify-center rounded-md brand-gradient-bg px-3 text-xs font-medium text-[hsl(var(--on-brand))] disabled:opacity-50">
                                            Save
                                        </button>
                                        <button type="button"
                                            @click="cancelCredentialEdit(selectedRepository)"
                                            class="inline-flex h-8 items-center justify-center rounded-md border border-border px-3 text-xs">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Members & roles panel --}}
                            <div class="lg:col-span-7 p-5 overflow-y-auto scrollbar-thin" @click.stop>
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-semibold inline-flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4 text-primary" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                            <circle cx="9" cy="7" r="4" />
                                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                        </svg>
                                        People and Roles
                                    </h4>
                                    <span class="text-[11px] text-muted-foreground"
                                        x-text="Number(selectedRepository.memberCount ?? 0) + (selectedRepository.ownerName ? 1 : 0)"></span>
                                </div>

                                <div x-show="selectedRepository.membersError"
                                    x-text="selectedRepository.membersError"
                                    class="mb-3 rounded-lg border px-3 py-2 text-xs"
                                    style="border-color:hsl(var(--failed)/0.30);color:hsl(var(--failed));background:hsl(var(--failed)/0.05)">
                                </div>

                                <template x-if="selectedRepository.canManageMembers">
                                    <div class="mb-4 rounded-lg border border-border/60 p-3">
                                        <div
                                            class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                            Add member
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
                                            <input type="search"
                                                x-model="selectedRepository.userSearch"
                                                @input.debounce.300ms="searchRepositoryUsers(selectedRepository)"
                                                placeholder="Search LDAP users..."
                                                class="h-9 rounded-md border border-border bg-background px-3 text-xs outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20">
                                            <select x-model="selectedRepository.userRoleToAdd"
                                                class="h-9 rounded-md border border-border bg-background px-3 text-xs outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20">
                                                <template x-for="role in roleOptions"
                                                    :key="`add-repository-user-role-${role.key}`">
                                                    <option :value="role.key" x-text="role.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div x-show="selectedRepository.userSearchLoading"
                                            class="mt-2 text-[11px] text-muted-foreground">
                                            Searching...
                                        </div>
                                        <div x-show="selectedRepository.userSearchError"
                                            x-text="selectedRepository.userSearchError"
                                            class="mt-2 text-[11px] text-failed"></div>
                                        <div x-show="selectedRepository.userSuggestions.length > 0"
                                            class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-border/60">
                                            <template x-for="user in selectedRepository.userSuggestions"
                                                :key="user.username || user.email">
                                                <button type="button"
                                                    @click="!user.already_member && addRepositoryUser(selectedRepository, user)"
                                                    :disabled="user.already_member || selectedRepository.userSaving"
                                                    class="flex w-full items-center gap-3 px-3 py-2 text-left text-xs transition-colors hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60">
                                                    <template x-if="user.avatar">
                                                        <img :src="user.avatar" :alt="user.name"
                                                            class="h-7 w-7 rounded-full object-cover border border-border/70 shrink-0">
                                                    </template>
                                                    <template x-if="!user.avatar">
                                                        <div
                                                            class="h-7 w-7 rounded-full brand-gradient-bg shadow-soft flex items-center justify-center text-[10px] font-semibold on-brand shrink-0"
                                                            x-text="userInitials(user.name, user.username)"></div>
                                                    </template>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-xs font-semibold truncate"
                                                            x-text="user.name"></span>
                                                        <span
                                                            class="block text-[11px] text-muted-foreground truncate"
                                                            x-text="userSubtitle(user)"></span>
                                                    </span>
                                                    <span class="text-[10px] font-semibold px-2 py-1 rounded-md border"
                                                        :class="user.already_member ? 'border-border text-muted-foreground' : 'border-primary/30 text-primary'"
                                                        x-text="user.already_member ? 'On repository' : 'Add'"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="selectedRepository.users.length === 0">
                                    <p class="text-xs text-muted-foreground py-6 text-center">No members assigned
                                        yet.</p>
                                </template>

                                <template x-if="selectedRepository.ownerName">
                                    <div class="mb-4">
                                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                            Owner</div>
                                        <div class="flex items-center gap-3 rounded-lg border border-border/60 p-3">
                                            <div class="h-9 w-9 rounded-full brand-gradient-bg shadow-soft flex items-center justify-center text-[11px] font-semibold shrink-0 text-[hsl(var(--on-brand))]"
                                                x-text="selectedRepository.ownerName.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()">
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-xs font-semibold truncate"
                                                    x-text="selectedRepository.ownerName"></div>
                                            </div>
                                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-md border border-primary/30 text-primary">Owner</span>
                                        </div>
                                    </div>
                                </template>


                                <div x-show="selectedRepository.users.length > 0">
                                    <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        Members
                                    </div>
                                    <ul class="space-y-2">
                                        <template x-for="user in selectedRepository.users"
                                            :key="`repository-user-${user.id}`">
                                            <li
                                                class="flex items-center gap-3 rounded-lg border border-border/60 p-3 hover:shadow-soft transition-base">
                                                <template x-if="user.avatar">
                                                    <img :src="user.avatar" :alt="user.name"
                                                        class="h-9 w-9 rounded-full object-cover border border-border/70 shrink-0">
                                                </template>
                                                <template x-if="!user.avatar">
                                                    <div
                                                        class="h-9 w-9 rounded-full brand-gradient-bg shadow-soft flex items-center justify-center text-[11px] font-semibold on-brand shrink-0"
                                                        x-text="user.initials"></div>
                                                </template>
                                                <div class="min-w-0 flex-1">
                                                    <div class="text-xs font-semibold truncate"
                                                        x-text="user.name"></div>
                                                    <div class="text-[11px] text-muted-foreground truncate"
                                                        x-text="userSubtitle(user)"></div>
                                                </div>
                                                <template x-if="selectedRepository.canManageMembers">
                                                    <select x-model="user.role"
                                                        @change="updateRepositoryUserRole(selectedRepository, user, $event.target.value)"
                                                        :disabled="selectedRepository.roleSavingId === roleSavingKey(user.id)"
                                                        class="h-8 w-36 rounded-md border border-border bg-background px-2 text-[11px] outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20">
                                                        <template x-for="role in roleOptions"
                                                            :key="`repository-user-role-${user.id}-${role.key}`">
                                                            <option :value="role.key"
                                                                x-text="role.label"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="!selectedRepository.canManageMembers">
                                                    <span
                                                        class="text-[10px] font-medium px-2 py-0.5 rounded-md border border-border text-muted-foreground"
                                                        x-text="roleLabel(user.role)"></span>
                                                </template>
                                                <button type="button"
                                                    x-show="selectedRepository.canManageMembers"
                                                    @click="removeRepositoryUser(selectedRepository, user)"
                                                    :disabled="selectedRepository.removingUserId === user.id"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md text-failed hover:bg-failed/10 transition-colors disabled:opacity-50"
                                                    title="Remove user">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path d="M18 6 6 18" />
                                                        <path d="m6 6 12 12" />
                                                    </svg>
                                                </button>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </template>
    </div>
</template>
