<div class="section-card p-12 text-center">
    <div class="mx-auto mb-5 h-16 w-16 rounded-2xl brand-soft-bg flex items-center justify-center">
        <svg class="h-8 w-8" style="color:hsl(var(--primary))" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
        </svg>
    </div>
    <p class="text-sm font-semibold" style="color:hsl(var(--foreground))">No repositories yet</p>
    <p class="text-xs mt-1 max-w-sm mx-auto leading-relaxed" style="color:hsl(var(--muted-foreground))">
        Connect your first repository to start generating deployment packages.
        Supports GitHub, GitLab, company servers, SSH mirrors, and uploads.
    </p>
    <button @click="window.dispatchEvent(new CustomEvent('open-repo-modal'))"
        class="inline-flex items-center gap-1.5 mt-5 px-4 py-2 rounded-lg text-sm font-semibold transition-base hover:opacity-90"
        style="background:var(--gradient-brand)">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Connect Repository
    </button>
</div>
