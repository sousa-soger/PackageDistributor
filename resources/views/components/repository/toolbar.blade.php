<div class="flex flex-wrap items-center gap-3 mb-5">
    <div class="relative flex-1 min-w-[220px] max-w-md">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-search absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.3-4.3"></path>
        </svg>
        <input type="search" x-model.debounce.200ms="searchQuery" placeholder="Search repositories…"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm pl-9">
    </div>
    <div class="ml-auto inline-flex items-center rounded-lg border border-border/70 bg-card p-1 shadow-sm">
        <button @click="setViewMode('cards')"
            :class="viewMode === 'cards' ? 'brand-soft-bg text-foreground shadow-soft' : 'text-muted-foreground hover:text-foreground'"
            class="px-2.5 py-1.5 rounded-md text-xs font-semibold inline-flex items-center gap-1.5 transition-base"
            aria-label="Card view">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-layout-grid h-3.5 w-3.5">
                <rect width="7" height="7" x="3" y="3" rx="1"></rect>
                <rect width="7" height="7" x="14" y="3" rx="1"></rect>
                <rect width="7" height="7" x="14" y="14" rx="1"></rect>
                <rect width="7" height="7" x="3" y="14" rx="1"></rect>
            </svg>
            Cards
        </button>
        <button @click="setViewMode('list')"
            :class="viewMode === 'list' ? 'brand-soft-bg text-foreground shadow-soft' : 'text-muted-foreground hover:text-foreground'"
            class="px-2.5 py-1.5 rounded-md text-xs font-semibold inline-flex items-center gap-1.5 transition-base"
            aria-label="List view">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-list h-3.5 w-3.5">
                <path d="M3 12h.01"></path>
                <path d="M3 18h.01"></path>
                <path d="M3 6h.01"></path>
                <path d="M8 12h13"></path>
                <path d="M8 18h13"></path>
                <path d="M8 6h13"></path>
            </svg>
            List
        </button>
    </div>
</div>
