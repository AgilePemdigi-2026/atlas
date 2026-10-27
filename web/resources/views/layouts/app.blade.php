<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth scroll-pt-24">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ATLAS – Turn User Stories into Automated Test Scripts')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-x-hidden bg-white font-sans text-body antialiased dark:bg-abyss dark:text-slate-200">
    @hasSection('bare')
        @yield('content')
    @else
        @include('partials.header')

        <div class="relative mx-auto max-w-[1440px]">
            @yield('content')
        </div>

        @include('partials.footer')
    @endif

    <button type="button" id="theme-toggle" aria-label="Ganti mode terang atau gelap"
        class="fixed bottom-4 left-4 z-30 grid size-10 place-items-center rounded-full border border-line bg-white text-body shadow-md hover:bg-surface dark:border-slate-600 dark:bg-night dark:text-slate-200 dark:hover:bg-slate-800">
        <svg class="size-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
        <svg class="hidden size-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
    </button>

    <script>
        document.getElementById('theme-toggle').addEventListener('click', function () {
            var isDark = document.documentElement.classList.toggle('dark');
            try { localStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch (e) {}
        });
    </script>
</body>
</html>