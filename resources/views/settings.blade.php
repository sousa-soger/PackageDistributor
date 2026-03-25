@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <div class="max-w-6xl mx-auto pt-4 space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Settings</h1>
            <p class="text-sm text-slate-500 mt-1">Manage your settings here.</p>
        </div>

        <x-ui.card class="p-6">
            
            <div>
                <label for="theme-select" class="block text-sm font-semibold text-slate-500 mb-2">
                    Theme
                </label>

                <select
                    id="theme-select"
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500 {{-- dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 --}}">
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                    <option value="system">System</option>
                </select>

                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Choose how the interface should appear.
                </p>
            </div>

            <div class="pt-2">
                <x-ui.primary-button type="button" id="save-theme-btn">
                    Save Theme
                </x-ui.primary-button>
            </div>
        </x-ui.card>
    </div>
@endsection

@push('scripts')
<script>
    function applyTheme(theme) {
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (theme === 'dark' || (theme === 'system' && systemPrefersDark)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('theme-select');
        const saveBtn = document.getElementById('save-theme-btn');
        const message = document.getElementById('theme-message');

        const savedTheme = localStorage.getItem('theme') || 'system';
        select.value = savedTheme;

        saveBtn.addEventListener('click', () => {
            const selectedTheme = select.value;

            localStorage.setItem('theme', selectedTheme);
            applyTheme(selectedTheme);

            message.classList.remove('hidden');

            setTimeout(() => {
                message.classList.add('hidden');
            }, 2000);
        });

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            const currentTheme = localStorage.getItem('theme') || 'system';

            if (currentTheme === 'system') {
                applyTheme('system');
            }
        });
    });
</script>
@endpush