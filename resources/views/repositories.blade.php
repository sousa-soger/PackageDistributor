@extends('layouts.app')

@section('title', 'Repositories')

@section('content')
    <div x-data="{
        gitlabOauth: true,  
    }">
        <div x-show="!gitlabOauth" x-cloak class="min-h-[calc(100vh-4rem)] flex items-center justify-center flex-col space-y-2">
            <p class="text-xl text-slate-600 font-medium tracking-wide pb-6">Sign in to GitLab</p>
            <p class="text-sm text-slate-400 font-medium tracking-wide pb-2">Sign in to GitLab to view repositories</p>
            <button class="group flex items-center gap-4 py-1 px-6 rounded-[24px] bg-white border border-slate-200 shadow-xl shadow-slate-200/50 hover:shadow-2xl hover:shadow-orange-500/10 transition-all duration-300 hover:scale-[1.02]">
                <div class="flex items-center justify-center w-12 h-12 rounded-2xl text-orange-600 transition-transform duration-300 group-hover:scale-110"> 
                    <span aria-hidden="true" data-testid="brand-header-default-logo">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 28 26" height="26" width="28" class="tanuki-logo" role="img" aria-hidden="true">
                            <path fill="#E24329" d="m24.507 9.5-.034-.09L21.082.562a.896.896 0 0 0-1.694.091l-2.29 7.01H7.825L5.535.653a.898.898 0 0 0-1.694-.09L.451 9.411.416 9.5a6.297 6.297 0 0 0 2.09 7.278l.012.01.03.022 5.16 3.867 2.56 1.935 1.554 1.176a1.051 1.051 0 0 0 1.268 0l1.555-1.176 2.56-1.935 5.197-3.89.014-.01A6.297 6.297 0 0 0 24.507 9.5Z" class="tanuki-shape tanuki"></path>
                            <path fill="#FC6D26" d="m24.507 9.5-.034-.09a11.44 11.44 0 0 0-4.56 2.051l-7.447 5.632 4.742 3.584 5.197-3.89.014-.01A6.297 6.297 0 0 0 24.507 9.5Z" class="tanuki-shape right-cheek"></path>
                            <path fill="#FCA326" d="m7.707 20.677 2.56 1.935 1.555 1.176a1.051 1.051 0 0 0 1.268 0l1.555-1.176 2.56-1.935-4.743-3.584-4.755 3.584Z" class="tanuki-shape chin"></path>
                            <path fill="#FC6D26" d="M5.01 11.461a11.43 11.43 0 0 0-4.56-2.05L.416 9.5a6.297 6.297 0 0 0 2.09 7.278l.012.01.03.022 5.16 3.867 4.745-3.584-7.444-5.632Z" class="tanuki-shape left-cheek"></path>
                        </svg>
                    </span>
                </div>
                <div class="text-left leading-tight">
                    <span class="block text-lg text-slate-900">Connect to GitLab</span>
                </div>
            </button>
            <p class="text-xs text-slate-400 font-medium tracking-wide uppercase pb-6">Authenticate with OAuth</p>
        </div>

        <div class="max-w-6xl mx-auto pt-4 space-y-8" x-show="gitlabOauth" x-cloak>
            <!----------------------------- New UI Here ---------------------------->
            <x-ui.card class="p-6">
                <div class="space-y-6">
                </div>
            </x-ui.card>
            <!----------------------------- New UI Here ---------------------------->
        </div>

    </div>

    
@endsection

@push('scripts')
<script>
</script>
@endpush