@props(['title' => null, 'staffName' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
        {{-- Sticky N1 header --}}
        @php($kioskStaff = \App\Models\Staff::find(session('kiosk_staff_id')))
        <header class="sticky top-0 z-30 bg-n1-dark bg-cover bg-center text-white shadow-md"
                style="background-image: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.45)), url('/images/bg-header.png');">
            <div class="mx-auto flex max-w-3xl items-center gap-3 px-4 py-3">
                <a href="{{ route('kiosk') }}" class="flex items-center gap-2" wire:navigate>
                    <img src="/images/n1-logo.svg" alt="N1" class="h-7 w-auto shrink-0" />
                    <span class="text-lg font-extrabold tracking-tight">Sjoppan</span>
                </a>
                @if ($kioskStaff)
                    <a href="{{ route('kiosk.staff') }}"
                       class="ml-auto flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-sm font-semibold active:bg-white/20"
                       wire:navigate>
                        <flux:icon.user variant="micro" />
                        {{ $kioskStaff->name }}
                    </a>
                @endif
            </div>
        </header>

        <main class="mx-auto max-w-3xl">
            {{ $slot }}
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
