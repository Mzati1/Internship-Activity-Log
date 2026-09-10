<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CSIT Internship Activity Log')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-ink antialiased">
    <header class="border-b border-line bg-surface">
        <div class="shell flex min-h-14 items-center justify-between gap-4 py-3">
            <a href="{{ route('dashboard') }}" class="text-base font-semibold tracking-tight text-ink">
                CSIT Internship Activity Log
            </a>

            <button
                type="button"
                id="nav-toggle"
                class="btn-secondary sm:hidden"
                aria-expanded="false"
                aria-controls="primary-nav"
            >
                Menu
            </button>

            <nav id="primary-nav" class="hidden w-full flex-col gap-1 sm:flex sm:w-auto sm:flex-row sm:items-center sm:gap-6">
                <a
                    href="{{ route('dashboard') }}"
                    class="rounded-md px-2 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-accent-soft text-accent' : 'text-ink-muted hover:text-ink' }}"
                >
                    Weeks
                </a>
                <a
                    href="{{ route('weekly-log.create') }}"
                    class="rounded-md px-2 py-2 text-sm font-medium {{ request()->routeIs('weekly-log.create') ? 'bg-accent-soft text-accent' : 'text-ink-muted hover:text-ink' }}"
                >
                    Current week
                </a>
            </nav>
        </div>
    </header>

    <main class="shell py-8 sm:py-10">
        @yield('content')
    </main>

    <script>
        (function () {
            const toggle = document.getElementById('nav-toggle');
            const nav = document.getElementById('primary-nav');
            if (!toggle || !nav) return;
            toggle.addEventListener('click', function () {
                const open = nav.classList.toggle('hidden') === false;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) {
                    nav.classList.add('flex');
                }
            });
        })();
    </script>
</body>
</html>
