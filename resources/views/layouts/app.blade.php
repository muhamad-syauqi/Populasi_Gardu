<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · INTRACS</title>
    <link rel="icon" type="image/png" href="{{ asset('intracs.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased">
<div class="min-h-screen lg:flex">
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy-900 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
        <div class="px-4 py-6">
            <a href="{{ route('dashboard') }}" class="block">
                <img src="{{ asset('intracs-kuning.png') }}" alt="Intracs" class="h-10 w-auto">
            </a>
        </div>

        <nav class="flex-1 space-y-1 px-4">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">
                <x-icon name="home" class="h-5 w-5" /> Dashboard
            </a>
            <a href="{{ route('gerbang.index') }}" class="nav-link {{ request()->routeIs('gerbang.*') ? 'nav-link-active' : '' }}">
                <x-icon name="building" class="h-5 w-5" /> Data Gerbang
            </a>
            <a href="{{ route('gardu.index') }}" class="nav-link {{ request()->routeIs('gardu.*') ? 'nav-link-active' : '' }}">
                <x-icon name="grid" class="h-5 w-5" /> Data Gardu
            </a>
            <a href="{{ route('laporan.index') }}" class="nav-link {{ request()->routeIs('laporan.*') ? 'nav-link-active' : '' }}">
                <x-icon name="document" class="h-5 w-5" /> Laporan
            </a>
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="px-4 pb-6">
            @csrf
            <button type="submit" class="nav-link w-full">
                <x-icon name="logout" class="h-5 w-5" /> Logout
            </button>
        </form>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
            <button type="button" data-sidebar-toggle class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Menu">
                <x-icon name="menu" />
            </button>
            <div class="hidden lg:block"></div>
            <div class="flex items-center gap-4">
                <span class="text-slate-500"><x-icon name="bell" /></span>
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-navy-800 text-sm font-semibold uppercase text-white">
                        {{ mb_substr(auth()->user()->username ?? auth()->user()->name, 0, 1) }}
                    </span>
                    <div class="hidden leading-tight sm:block">
                        <div class="text-sm font-semibold">{{ auth()->user()->username ?? auth()->user()->name }}</div>
                        <div class="text-xs text-slate-500">Administrator</div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            <x-flash />
            @yield('content')
        </main>

        <footer class="px-6 pb-4 text-xs text-slate-400">&copy; {{ date('Y') }} PT Module Intracs Yasatama</footer>
    </div>
</div>
@stack('scripts')
</body>
</html>