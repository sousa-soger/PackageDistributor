<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Cybix Deployer')</title>

    <script>
        (() => {
            const root = document.documentElement;
            const storageKey = 'theme';
            const disableTransitionsClass = 'theme-switching';
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            const resolveDarkMode = (theme) => theme === 'dark' || (theme === 'system' && mediaQuery.matches);

            window.applyThemePreference = (theme, persist = true) => {
                root.classList.add(disableTransitionsClass);
                root.classList.toggle('dark', resolveDarkMode(theme));

                if (persist) {
                    if (theme === 'system') {
                        localStorage.removeItem(storageKey);
                    } else {
                        localStorage.setItem(storageKey, theme);
                    }
                }

                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(() => {
                        root.classList.remove(disableTransitionsClass);
                    });
                });
            };

            root.classList.toggle('dark', resolveDarkMode(localStorage.getItem(storageKey) || 'system'));
        })();
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
