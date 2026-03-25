@props([
    'type' => 'button'
])

<button type="{{ $type }}"
    {{ $attributes->merge([
        'class' => 'rounded-2xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50'
    ]) }}>
    {{ $slot }}
</button>