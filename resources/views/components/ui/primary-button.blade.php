@props([
    'type' => 'button'
])

<button type="{{ $type }}"
    {{ $attributes->merge([
        'class' => 'rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700'
    ]) }}>
    {{ $slot }}
</button>