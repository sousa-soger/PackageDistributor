<x-head-html/>

    <body class="min-h-screen antialiased" style="background-color: hsl(var(--background)); color: hsl(var(--foreground));">
        <div class="flex min-h-screen w-full" style="background-image: var(--gradient-surface);">

            <x-sidebar />

            <div class="flex-1 min-w-0 flex flex-col">
                <x-topbar />

                <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
                    @yield('content')
                </main>
            </div>

        </div>

        <x-ui.toast :flashes="array_filter([
            session('success') ? ['type'=>'success','message'=>session('success')] : null,
            session('error')   ? ['type'=>'error',  'message'=>session('error')]   : null,
            session('warning') ? ['type'=>'warning','message'=>session('warning')] : null,
            session('info')    ? ['type'=>'info',   'message'=>session('info')]    : null,
        ])" />

        @stack('scripts')
        
    </body>

</html>
