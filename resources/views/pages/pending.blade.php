<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin')] #[Title('Beðið eftir samþykki')] class extends Component {
    //
}; ?>

<div class="mx-auto flex min-h-[60vh] max-w-md flex-col items-center justify-center text-center">
    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600">
        <flux:icon.clock class="h-8 w-8" />
    </div>
    <flux:heading size="xl" class="mt-6">Beðið eftir samþykki</flux:heading>
    <flux:text class="mt-2">
        Aðgangurinn þinn er búinn til en bíður samþykkis stjórnanda.
        Þú færð aðgang um leið og stjórnandi hefur samþykkt þig og gefið réttindi.
    </flux:text>

    <form method="POST" action="{{ route('logout') }}" class="mt-8">
        @csrf
        <flux:button type="submit" variant="filled" icon="arrow-right-start-on-rectangle">Útskrá</flux:button>
    </form>
</div>
