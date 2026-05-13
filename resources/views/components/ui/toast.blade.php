@props(['flashes' => []])

<div x-data="{
        toasts: [],
        addToast(toast) {
            toast.id = Date.now() + Math.random().toString(36).substring(2);
            this.toasts.push(toast);
            setTimeout(() => {
                this.removeToast(toast.id);
            }, 4000);
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @toast.window="addToast($event.detail)"
    x-init="
        @foreach($flashes as $flash)
            addToast({ type: '{{ $flash['type'] }}', message: '{{ addslashes($flash['message']) }}' });
        @endforeach
    "
    class="fixed bottom-6 right-6 z-99999 flex flex-col-reverse gap-2.5 pointer-events-none w-[320px]"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="pointer-events-auto flex items-start gap-3.5 rounded-xl px-4 py-3.5 border backdrop-blur-sm"
            :class="{
                'bg-card/95 border-border/70 shadow-[var(--shadow-md),0_8px_16px_-8px_hsl(var(--success)/0.35)]': toast.type === 'success',
                'bg-card/95 border-border/70 shadow-[var(--shadow-md),0_8px_16px_-8px_hsl(var(--queued)/0.40)]':  toast.type === 'warning',
                'bg-card/95 border-border/70 shadow-[var(--shadow-md),0_8px_16px_-8px_hsl(var(--primary)/0.30)]': toast.type === 'info',
                'bg-card/95 border-border/70 shadow-[var(--shadow-md),0_8px_16px_-8px_hsl(var(--failed)/0.35)]':  toast.type === 'error',
            }"
        >
            {{-- Icon --}}
            <div class="shrink-0 mt-0.5 flex items-center justify-center w-6 h-6 rounded-md"
                :class="{
                    'bg-[hsl(var(--success)/0.12)] text-[hsl(var(--success))]': toast.type === 'success',
                    'bg-[hsl(var(--queued)/0.14)] text-[hsl(var(--queued))]':   toast.type === 'warning',
                    'bg-[hsl(var(--primary)/0.12)] text-[hsl(var(--primary))]': toast.type === 'info',
                    'bg-[hsl(var(--failed)/0.12)] text-[hsl(var(--failed))]':   toast.type === 'error',
                }">

                {{-- Success --}}
                <template x-if="toast.type === 'success'">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </template>

                {{-- Warning --}}
                <template x-if="toast.type === 'warning'">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </template>

                {{-- Info --}}
                <template x-if="toast.type === 'info'">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>

                {{-- Error --}}
                <template x-if="toast.type === 'error'">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </template>
            </div>

            {{-- Message --}}
            <p class="flex-1 text-sm font-medium text-foreground leading-snug" x-text="toast.message"></p>

            {{-- Close --}}
            <button
                @click="removeToast(toast.id)"
                class="shrink-0 self-start mt-0.5 flex items-center justify-center w-5 h-5 rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition-colors focus:outline-none"
                aria-label="Dismiss"
            >
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
