{{-- ── Upload New Version Modal ──────────────────────────────────────────── --}}
<div x-show="uploadVersionModal" x-cloak x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="background:hsl(220 30% 5% / 0.55);backdrop-filter:blur(4px)"
    @click.self="closeUploadVersionModal()">
    <div class="w-full max-w-lg animate-slide-up overflow-hidden"
        style="background:hsl(var(--card));border-radius:calc(var(--radius) * 1.5);border:1px solid hsl(var(--border)/0.7);box-shadow:var(--shadow-lg)"
        role="dialog" aria-modal="true">

        {{-- Modal header --}}
        <div class="brand-soft-bg px-6 py-5 border-b border-border/60">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="font-semibold tracking-tight text-xl" style="color:hsl(var(--foreground))">Upload New
                        Version
                    </h2>
                    <p class="mt-1 text-sm" style="color:hsl(var(--muted-foreground))">
                        <span>Replace</span>
                        <span class="font-medium" style="color:hsl(var(--foreground))"
                            x-text="uploadVersionRepository?.label"></span>
                        <span>with a full project upload.</span>
                    </p>
                </div>
                <button type="button" @click="closeUploadVersionModal()"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md transition-colors hover:bg-accent"
                    aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Modal body --}}
        <div class="px-6 py-5 max-h-[60vh] overflow-y-auto scrollbar-thin">
            <div class="space-y-4">
                {{-- Drag & drop zone --}}
                <label for="upload-version-archive"
                    class="group relative flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-border/70 bg-secondary/30 px-6 py-9 text-center cursor-pointer transition-all hover:border-primary/60 hover:bg-secondary/50"
                    :class="uploadVersionDropActive ? 'border-primary/60 bg-secondary/50' : ''"
                    @dragenter.prevent="uploadVersionDropActive = true"
                    @dragover.prevent="uploadVersionDropActive = true"
                    @dragleave.self.prevent="uploadVersionDropActive = false"
                    @drop.prevent="handleUploadVersionDrop($event)">
                    <div
                        class="h-14 w-14 rounded-2xl brand-soft-bg flex items-center justify-center text-primary transition-transform group-hover:scale-105">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="17 8 12 3 7 8" />
                            <line x1="12" x2="12" y1="3" y2="15" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <div class="text-base font-semibold">Drag &amp; Drop the full project</div>
                        <p class="text-xs text-muted-foreground max-w-sm">Upload the complete folder, ZIP, or Git
                            bundle again. Missing files in this upload will be treated as removed.</p>
                        <p class="text-xs text-muted-foreground">Click to browse for a ZIP or bundle file.</p>
                    </div>
                    <input id="upload-version-archive" type="file" accept=".zip,.bundle" class="hidden"
                        @change="handleUploadVersionArchiveSelection($event)">
                </label>

                {{-- Browse folder fallback --}}
                <div class="flex items-center justify-between gap-3 rounded-lg border border-dashed p-3 text-xs"
                    style="border-color:hsl(var(--border));background:hsl(var(--secondary)/0.35);color:hsl(var(--muted-foreground))">
                    <span>Need to browse a folder instead of an archive?</span>
                    <button type="button" @click="$refs.uploadVersionFolderInput.click()"
                        class="inline-flex h-8 shrink-0 items-center justify-center rounded-md border border-border bg-background px-3 font-medium transition-colors hover:bg-accent"
                        style="color:hsl(var(--foreground))">
                        Browse Folder
                    </button>
                    <input x-ref="uploadVersionFolderInput" type="file" webkitdirectory multiple class="hidden"
                        @change="handleUploadVersionFolderSelection($event)">
                </div>

                {{-- Zip progress --}}
                <div x-show="uploadVersionZipLoading" x-cloak class="text-sm"
                    style="color:hsl(var(--muted-foreground))" x-text="uploadVersionZipProgress"></div>

                {{-- Upload progress bar --}}
                <div x-show="uploadVersionFile" x-cloak class="space-y-2">
                    <div class="flex items-center justify-between gap-3 text-xs"
                        style="color:hsl(var(--muted-foreground))">
                        <span class="truncate" x-text="uploadVersionFile?.name"></span>
                        <span x-text="`${uploadVersionProgress}%`"></span>
                    </div>
                    <div class="h-2 rounded-full overflow-hidden" style="background:hsl(var(--border))">
                        <div class="h-full transition-all"
                            :style="`width:${uploadVersionProgress}%;background:var(--gradient-brand)`"></div>
                    </div>
                </div>

                {{-- Error --}}
                <div x-show="uploadVersionError" x-cloak class="rounded-lg border px-3 py-2 text-sm"
                    style="border-color:hsl(var(--failed)/0.30);color:hsl(var(--failed));background:hsl(var(--failed)/0.05)"
                    x-text="uploadVersionError"></div>
            </div>
        </div>

        {{-- Modal footer --}}
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 px-6 py-4 border-t"
            style="border-color:hsl(var(--border)/0.6);background:hsl(var(--secondary)/0.5)">
            <button type="button" @click="closeUploadVersionModal()"
                class="inline-flex h-9 items-center justify-center rounded-md border border-border bg-background px-3 text-sm font-medium transition-colors hover:bg-accent">
                Cancel
            </button>
            <button type="button" @click="uploadRepositoryVersion()"
                :disabled="uploadVersionLoading || !uploadVersionFile"
                class="brand-gradient-bg shadow-soft inline-flex h-9 items-center justify-center rounded-md px-3 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-text="uploadVersionLoading ? 'Uploading...' : 'Upload New Version'"></span>
            </button>
        </div>
    </div>
</div>
