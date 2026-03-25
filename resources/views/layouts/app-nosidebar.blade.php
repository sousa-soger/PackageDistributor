<x-head-html/>
<body class="bg-[#f5f7fb] text-slate-800 min-h-screen">
    <div class="flex min-h-screen">

        <main class="flex-1">
            @yield('content')
        </main>

    </div>
    @stack('scripts')
</body>
</html>