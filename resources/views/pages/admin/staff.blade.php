<?php

use App\Models\DailyPin;
use App\Models\Staff;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('components.layouts.admin')] #[Title('Starfsfólk')] class extends Component {
    public bool $showModal = false;

    #[Validate('required|string|max:255|unique:staff,name')]
    public string $name = '';

    public string $pinInput = '';

    /**
     * @return Collection<int, Staff>
     */
    #[Computed]
    public function staff(): Collection
    {
        return Staff::withCount('sales')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    public function todayPin(): ?DailyPin
    {
        return DailyPin::forToday();
    }

    public function savePin(): void
    {
        $this->validate(['pinInput' => 'required|digits:4'], [
            'pinInput.digits' => 'PIN verður að vera 4 tölustafir.',
            'pinInput.required' => 'Sláðu inn 4 tölustafa PIN.',
        ]);

        DailyPin::setToday($this->pinInput);
        Flux::toast('PIN dagsins vistað.', variant: 'success');
    }

    public function generatePin(): void
    {
        $this->pinInput = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        DailyPin::setToday($this->pinInput);
        Flux::toast('Nýtt PIN búið til.', variant: 'success');
    }

    public function create(): void
    {
        $this->reset('name');
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        Staff::create(['name' => trim($this->name), 'is_active' => true]);
        Flux::toast('Starfsmanni bætt við.', variant: 'success');

        $this->showModal = false;
    }

    public function toggleActive(int $id): void
    {
        $staff = Staff::findOrFail($id);
        $staff->update(['is_active' => ! $staff->is_active]);
    }
}; ?>

<div class="mx-auto w-full max-w-2xl">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Starfsfólk</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nýr starfsmaður</flux:button>
    </div>

    {{-- Daily kiosk PIN --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-zinc-50 p-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-zinc-500">PIN dagsins (kassi)</p>
                @if ($this->todayPin())
                    <p class="font-mono text-4xl font-extrabold tracking-widest text-n1-red" data-test="today-pin">{{ $this->todayPin()->pin }}</p>
                @else
                    <p class="text-2xl font-semibold text-zinc-400">Ekkert PIN sett í dag</p>
                @endif
            </div>
            <div class="flex items-end gap-2">
                <flux:input
                    wire:model="pinInput"
                    label="Setja PIN"
                    inputmode="numeric"
                    maxlength="4"
                    placeholder="1234"
                    class="w-28"
                />
                <flux:button wire:click="savePin" variant="primary">Vista</flux:button>
                <flux:button wire:click="generatePin" variant="filled" icon="sparkles">Búa til</flux:button>
            </div>
        </div>
        <flux:error name="pinInput" class="mt-2" />
        <p class="mt-2 text-xs text-zinc-500">Starfsfólk slær inn þetta PIN í kassanum til að byrja. Nýtt PIN þarf hvern dag.</p>
    </div>

    <flux:text class="mb-4">Starfsfólk er gert óvirkt frekar en eytt, svo sölusaga haldist.</flux:text>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Nafn</flux:table.column>
            <flux:table.column>Sölur</flux:table.column>
            <flux:table.column>Staða</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->staff as $member)
                <flux:table.row :key="$member->id">
                    <flux:table.cell variant="strong">{{ $member->name }}</flux:table.cell>
                    <flux:table.cell>{{ $member->sales_count }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$member->is_active ? 'green' : 'zinc'" size="sm">
                            {{ $member->is_active ? 'Virkur' : 'Óvirkur' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button wire:click="toggleActive({{ $member->id }})" size="sm" variant="ghost">
                            {{ $member->is_active ? 'Gera óvirkan' : 'Virkja' }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model.self="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">Nýr starfsmaður</flux:heading>
            <flux:input wire:model="name" label="Nafn" />
            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showModal', false)" variant="ghost">Hætta við</flux:button>
                <flux:button type="submit" variant="primary">Vista</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
