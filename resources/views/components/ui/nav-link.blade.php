@props([
    'href' => '#',
    'active' => false,
])

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => 'nav-item-modern ' . ($active ? 'nav-item-active' : '')]) }}
   :class="collapsed ? 'nav-item-compact' : ''"
>
    {{ $slot }}
</a>