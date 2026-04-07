{{--
    Reusable stage progress bar.
    Props:
        $field  – Alpine.js state property name (string, e.g. 'fileDownloadProgress')
        $label  – Human-readable stage label
        $weight – Weight string shown as a hint, e.g. '10%'
--}}
<div>
    <div class="mb-1.5 flex items-center justify-between gap-2">
        <span class="text-sm font-semibold leading-snug"
            :class="{{ $field }} === 100 ? 'text-green-600' : 'text-slate-700'">
            {{ $label }}
            <span x-show="{{ $field }} === 100" class="text-green-600">✓</span>
        </span>
        <span class="shrink-0 text-xs font-medium text-slate-400"
            x-show="{{ $field }} !== 100"
            x-text="{{ $field }} + '%'"></span>
    </div>

    <div x-show="{{ $field }} !== 100"
        class="h-2.5 overflow-hidden rounded-full bg-slate-200">
        <div class="h-full rounded-full bg-blue-500 transition-all duration-500"
            :style="`width: ${ {{ $field }} }%`"></div>
    </div>
</div>
