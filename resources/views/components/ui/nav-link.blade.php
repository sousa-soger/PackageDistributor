@props([
    'href' => '#',
    'active' => false,
])

<a href="{{ $href }}"
   class="flex items-center px- py-3 rounded text-[12px] font-medium transition
   {{ $active
        ? 'bg-blue-50 text-blue-600 {{-- dark:bg-blue-500/15 dark:text-blue-400 --}}'
        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 {{-- dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white --}}' }}">
    {{ $slot }}
</a>