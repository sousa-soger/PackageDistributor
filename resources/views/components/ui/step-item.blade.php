@props([
    'number' => 1,
    'label' => '',
    'active' => false,
    'completed' => false,
])

<div class="flex items-center gap-3">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
            {{ $active ? 'bg-blue-600 text-white' : ($completed ? 'bg-green-400 text-slate-200' : 'bg-slate-200 text-slate-500') }}">
            {{ $number }}
        </div>

        <span class="text-sm font-medium {{ $active ? 'text-slate-900' : 'text-slate-400' }}">
            {{ $label }}
        </span>
    </div>

    @if (! $attributes->get('last'))
        <div class="w-16 h-px bg-slate-200"></div>
    @endif
</div>