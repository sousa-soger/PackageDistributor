@extends('layouts.app')

@section('title', 'Register Repository')

@section('content')
    <div class="max-w-6xl mx-auto pt-4 space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Repository Registration</h1>
            <p class="text-sm text-slate-500 mt-1">Register a new repository here.</p>
        </div>

        <x-ui.card class="p-6">
            
            <div>
                <label for="theme-select" class="block text-sm font-semibold text-slate-500 mb-2">
                    Testing
                </label>
            </div>

            <div class="pt-2">
                <x-ui.primary-button type="button" id="save-theme-btn">
                    Testing 1 2 3 ...
                </x-ui.primary-button>
            </div>
        </x-ui.card>
    </div>
@endsection

@push('scripts')
<script>
</script>
@endpush