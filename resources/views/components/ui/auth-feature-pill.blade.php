@props([
    'icon' => 'bolt',
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-900/70 px-5 py-4 text-sm font-semibold text-slate-100 shadow-[0_0_0_1px_rgba(255,255,255,0.02)] backdrop-blur']) }}>
    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
        @if ($icon === 'bolt')
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M13 2L6 13h5l-1 9 7-11h-5l1-9z"/>
            </svg>
        @elseif ($icon === 'chart')
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M3 3v18h18"/>
                <path d="M7 14l4-4 3 3 5-6"/>
            </svg>
        @endif
    </span>

    <span>{{ $slot }}</span>
</div>