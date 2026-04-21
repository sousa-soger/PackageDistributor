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
    class="fixed top-6 right-6 z-99999 flex flex-col gap-3 pointer-events-none w-[320px]"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-6"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-6"
            class="pointer-events-auto flex items-center gap-3.5 bg-white rounded-2xl px-4 py-3.5 border border-slate-200/80"
            :class="{
                'shadow-[0_2px_8px_rgba(15,23,42,0.05),0_10px_12px_-10px_rgba(16,185,129,0.55)]': toast.type === 'success',
                'shadow-[0_2px_8px_rgba(15,23,42,0.05),0_10px_12px_-10px_rgba(251,191,36,0.60)]': toast.type === 'warning',
                'shadow-[0_2px_8px_rgba(15,23,42,0.05),0_10px_12px_-10px_rgba(148,163,184,0.50)]': toast.type === 'info',
                'shadow-[0_2px_8px_rgba(15,23,42,0.05),0_10px_12px_-10px_rgba(239,68,68,0.55)]': toast.type === 'error',
            }"
        >

            {{-- Icon --}}
            <div class="shrink-0 flex items-center justify-center w-7 h-7">

                {{-- Success: green checkmark --}}
                <template x-if="toast.type === 'success'">
                    <svg class="w-[22px] h-[22px] text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </template>

                {{-- Warning: yellow ! --}}
                <template x-if="toast.type === 'warning'">
                    <svg fill="#FBBF24" width="800px" height="800px" viewBox="-8 0 19 19" xmlns="http://www.w3.org/2000/svg" class="cf-icon-svg">
                        <path d="M2.828 15.984A1.328 1.328 0 1 1 1.5 14.657a1.328 1.328 0 0 1 1.328 1.327zM1.5 13.244a1.03 1.03 0 0 1-1.03-1.03V2.668a1.03 1.03 0 0 1 2.06 0v9.548a1.03 1.03 0 0 1-1.03 1.029z"/>
                    </svg>
                </template>

                {{-- Info: grey circle-i --}}
                <template x-if="toast.type === 'info'">
                    <div class="w-[22px] h-[22px] rounded-full border-2 border-slate-400 flex items-center justify-center">
                        <span class="text-slate-400 font-bold text-xs leading-none">i</span>
                    </div>
                </template>

                {{-- Error: red triangle --}}
                <template x-if="toast.type === 'error'">
                    <svg class="w-[22px] h-[22px] text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </template>
            </div>

            {{-- Message --}}
            <p class="flex-1 text-xs font-semibold text-slate-800 leading-snug" x-text="toast.message"></p>

            {{-- Close button --}}
            <button
                @click="removeToast(toast.id)"
                class="shrink-0 self-center flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors ml-1 focus:outline-none text-xl leading-none font-light"
                aria-label="Dismiss"
            >&times;</button>
        </div>
    </template>
</div>
