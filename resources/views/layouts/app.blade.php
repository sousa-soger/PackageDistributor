<x-head-html/>

    <body class="bg-[#f5f7fb] text-slate-800 min-h-screen">
        <div class="flex min-h-screen">

            <x-sidebar/> <!-- page sidebar from ui.sidebar.blade.php -->

            <main class="flex-1">
                @yield('content')
            </main>

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
