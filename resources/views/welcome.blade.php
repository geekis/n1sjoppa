<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
        {{-- N1 header --}}
        <header class="bg-n1-dark text-white">
            <div class="mx-auto flex max-w-5xl items-center gap-3 px-4 py-3">
                <img src="/images/n1-logo.svg" alt="N1" class="h-7 w-auto shrink-0" />
                <span class="text-lg font-extrabold tracking-tight">Sjoppan</span>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4">
            {{-- Actions --}}
            <div class="mx-auto mt-10 grid max-w-xl gap-3 sm:grid-cols-2">
                <a href="{{ route('kiosk') }}"
                   class="flex items-center justify-center rounded-2xl bg-n1-red py-5 text-lg font-bold text-white shadow-md transition active:scale-[0.99] active:bg-n1-red-dark">
                    Opna kassann
                </a>
                <a href="{{ auth()->check() ? url('/admin') : route('login') }}"
                   class="flex items-center justify-center rounded-2xl bg-n1-dark py-5 text-lg font-bold text-white shadow-md transition hover:bg-black active:scale-[0.99]">
                    Stjórnborð
                </a>
            </div>

            <p class="mt-6 pb-10 text-center text-sm text-zinc-400">N1 Sjoppan &middot; kassakerfi</p>
        </main>
    </body>
</html>
