@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <div class="max-w-6xl mx-auto pt-4 space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Home</h1>
            <p class="text-sm text-slate-500 mt-1">Welcome to Package Distribution.</p>
        </div>

        <x-ui.card class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">Overview</h2>
            <p class="text-sm text-slate-500 mt-2">
                This is your dashboard home page. You can place your recent packages, stats, or quick actions here.
            </p>

            <div class="mt-6">
                <a href="{{ route('new-package') }}">
                    <x-ui.primary-button>Create New Package</x-ui.primary-button>
                </a>
            </div>
        </x-ui.card>
    </div>
@endsection