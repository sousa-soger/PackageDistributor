@props([
    'label' => null,
    'placeholder' => '',
])

<div class="space-y-2">
    @if($label)
        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $label }}</label>
    @endif

    <input
        {{ $attributes->merge([
            'class' => 'w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500'
        ]) }}
        placeholder="{{ $placeholder }}"
    >
</div>