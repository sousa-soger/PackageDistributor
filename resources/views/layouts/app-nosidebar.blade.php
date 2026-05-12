<x-head-html/>
<body class="min-h-screen bg-background text-foreground antialiased">
    <div class="flex min-h-screen w-full" style="background-image: var(--gradient-surface);">

        <main class="flex-1">
            @yield('content')
        </main>

    </div>
    @stack('scripts')
</body>
</html>
