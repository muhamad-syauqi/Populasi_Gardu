<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · INTRACS</title>
    <link rel="icon" type="image/png" href="{{ asset('intracs.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
<div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-navy-950 p-4 lg:justify-between lg:px-20">
    {{-- Background foto gerbang tol + overlay navy agar teks tetap terbaca --}}
    <img src="{{ asset('download.webp') }}" alt="" aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full object-cover">
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-navy-950/95 via-navy-900/85 to-blue-900/70"></div>
    <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-blue-500/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-40 right-0 h-[28rem] w-[28rem] rounded-full bg-sky-400/10 blur-3xl"></div>

    <div class="relative z-10 hidden max-w-md text-white lg:block">
        <div class="mb-10 flex items-center gap-4">
            <img src="{{ asset('intracs-kuning.png') }}" alt="Intracs" class="h-14 w-auto drop-shadow-lg">
            <div class="text-xs leading-tight text-slate-300">PT Module Intracs<br>Yasatama</div>
        </div>
        <h1 class="text-4xl font-bold leading-tight">Populasi Gardu</h1>
        <p class="mt-2 text-lg text-slate-300">Ruas Tol Purbaleunyi</p>
    </div>

    <div class="card relative z-10 w-full max-w-md p-8">
        <img src="{{ asset('intracs-kuning.png') }}" alt="Intracs" class="mb-5 h-12 w-auto lg:hidden">
        <h2 class="text-2xl font-bold text-slate-900">Login</h2>
        <p class="mt-1 text-sm text-slate-500">Masuk untuk mengakses sistem<br>Populasi Intracs</p>

        <form method="POST" action="{{ route('login.attempt') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="username" class="label">Username</label>
                <div class="relative">
                    <x-icon name="user" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                           autocomplete="username" placeholder="Masukkan username" class="input pl-10">
                </div>
                @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">Password</label>
                <div class="relative">
                    <x-icon name="lock" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           placeholder="Masukkan password" class="input pl-10 pr-10">
                    <button type="button" data-toggle-password="#password" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" aria-label="Tampilkan password">
                        <x-icon name="eye" class="h-5 w-5" />
                    </button>
                </div>
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-blue-600"> Ingat saya
            </label>

            <button type="submit" class="btn btn-primary w-full py-2.5">Login</button>
        </form>
    </div>
</div>
</body>
</html>