<?php

use App\Models\DailyPin;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('components.layouts.kiosk')] #[Title('Hver ert þú?')] class extends Component {
    public bool $addingNew = false;

    #[Validate('required|string|max:255|unique:staff,name')]
    public string $newName = '';

    /** Staff member picked, awaiting PIN. */
    public ?int $selectedStaffId = null;

    public string $pin = '';

    public string $error = '';

    /**
     * @return Collection<int, Staff>
     */
    #[Computed]
    public function staff(): Collection
    {
        return Staff::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedStaff(): ?Staff
    {
        return $this->selectedStaffId ? Staff::find($this->selectedStaffId) : null;
    }

    public function selectStaff(int $staffId): void
    {
        $staff = Staff::where('is_active', true)->findOrFail($staffId);

        $this->selectedStaffId = $staff->id;
        $this->pin = '';
        $this->error = '';
    }

    public function addStaff(): void
    {
        $this->validate();

        $staff = Staff::create([
            'name' => trim($this->newName),
            'is_active' => true,
        ]);

        $this->addingNew = false;
        $this->newName = '';
        $this->selectStaff($staff->id);
    }

    public function cancelPin(): void
    {
        $this->selectedStaffId = null;
        $this->pin = '';
        $this->error = '';
    }

    public function pressDigit(string $digit): void
    {
        if (strlen($this->pin) >= 4) {
            return;
        }

        $this->pin .= $digit;
        $this->error = '';

        if (strlen($this->pin) === 4) {
            $this->submitPin();
        }
    }

    public function backspace(): void
    {
        $this->pin = substr($this->pin, 0, -1);
        $this->error = '';
    }

    public function submitPin(): void
    {
        if (! $this->selectedStaff) {
            $this->cancelPin();

            return;
        }

        if (! DailyPin::matchesToday($this->pin)) {
            $this->pin = '';
            $this->error = 'Rangt PIN. Reyndu aftur.';

            return;
        }

        session(['kiosk_staff_id' => $this->selectedStaff->id]);

        $this->redirectRoute('kiosk', navigate: true);
    }
}; ?>

<div class="px-4 py-8">
    @if ($this->selectedStaff)
        {{-- PIN entry step --}}
        <div class="mx-auto max-w-xs">
            <button type="button" wire:click="cancelPin" class="mb-4 flex items-center gap-1 text-sm font-semibold text-zinc-500">
                <flux:icon.chevron-left variant="micro" /> Til baka
            </button>

            <h1 class="text-center text-2xl font-bold text-zinc-900">{{ $this->selectedStaff->name }}</h1>
            <p class="mb-6 text-center text-sm text-zinc-500">Sláðu inn PIN dagsins</p>

            {{-- PIN dots --}}
            <div class="mb-4 flex justify-center gap-3">
                @for ($i = 0; $i < 4; $i++)
                    <span @class([
                        'h-4 w-4 rounded-full',
                        'bg-n1-red' => $i < strlen($pin),
                        'bg-zinc-300' => $i >= strlen($pin),
                    ])></span>
                @endfor
            </div>

            @if ($error)
                <p class="mb-4 text-center text-sm font-semibold text-n1-red">{{ $error }}</p>
            @endif

            {{-- Numeric keypad --}}
            <div class="grid grid-cols-3 gap-3">
                @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $d)
                    <button type="button" wire:click="pressDigit('{{ $d }}')"
                        class="flex h-16 items-center justify-center rounded-xl bg-white text-2xl font-bold text-zinc-900 shadow-sm ring-1 ring-zinc-200 active:bg-zinc-100">
                        {{ $d }}
                    </button>
                @endforeach
                <div></div>
                <button type="button" wire:click="pressDigit('0')"
                    class="flex h-16 items-center justify-center rounded-xl bg-white text-2xl font-bold text-zinc-900 shadow-sm ring-1 ring-zinc-200 active:bg-zinc-100">
                    0
                </button>
                <button type="button" wire:click="backspace"
                    class="flex h-16 items-center justify-center rounded-xl text-zinc-500 active:bg-zinc-100">
                    <flux:icon.backspace variant="outline" />
                </button>
            </div>
        </div>
    @else
        {{-- Name select step --}}
        <h1 class="mb-6 text-center text-2xl font-bold text-zinc-900">
            Hver ert þú? <span class="block text-base font-normal text-zinc-500">Who are you?</span>
        </h1>

        <div class="grid grid-cols-2 gap-3">
            @foreach ($this->staff as $member)
                <flux:button
                    wire:key="staff-{{ $member->id }}"
                    wire:click="selectStaff({{ $member->id }})"
                    variant="filled"
                    class="!h-20 !text-lg !font-semibold"
                >
                    {{ $member->name }}
                </flux:button>
            @endforeach

            <button
                type="button"
                wire:click="$set('addingNew', true)"
                class="flex h-20 items-center justify-center gap-2 rounded-lg border-2 border-dashed border-zinc-300 text-lg font-semibold text-zinc-500 active:bg-zinc-100"
            >
                <flux:icon.plus variant="micro" /> Nýtt nafn
            </button>
        </div>

        @if ($addingNew)
            <div class="mt-6 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                <form wire:submit="addStaff" class="flex flex-col gap-3">
                    <flux:input
                        wire:model="newName"
                        label="Nýtt nafn / New name"
                        placeholder="Nafn"
                        autofocus
                    />
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary" class="flex-1">Áfram</flux:button>
                        <flux:button type="button" wire:click="$set('addingNew', false)" variant="ghost">Hætta við</flux:button>
                    </div>
                </form>
            </div>
        @endif
    @endif
</div>
