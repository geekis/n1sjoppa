@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-950">
            <flux:sidebar.header>
                <a href="{{ route('admin.reports') }}" class="flex items-center gap-2 px-1 py-2" wire:navigate>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-n1-red text-sm font-black text-white">N1</span>
                    <span class="font-bold">Sjoppan admin</span>
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="chart-bar" :href="route('admin.reports')" :current="request()->routeIs('admin.reports')" wire:navigate>
                    Skýrslur
                </flux:sidebar.item>
                <flux:sidebar.item icon="shopping-bag" :href="route('admin.products')" :current="request()->routeIs('admin.products')" wire:navigate>
                    Vörur
                </flux:sidebar.item>
                <flux:sidebar.item icon="tag" :href="route('admin.categories')" :current="request()->routeIs('admin.categories')" wire:navigate>
                    Flokkar
                </flux:sidebar.item>
                <flux:sidebar.item icon="users" :href="route('admin.staff')" :current="request()->routeIs('admin.staff')" wire:navigate>
                    Starfsfólk
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="device-phone-mobile" :href="route('kiosk')" target="_blank">
                    Opna kassa
                </flux:sidebar.item>
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:sidebar.item icon="arrow-right-start-on-rectangle" as="button" type="submit" class="w-full">
                        Útskrá
                    </flux:sidebar.item>
                </form>
            </flux:sidebar.nav>
        </flux:sidebar>

        <flux:main>
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
