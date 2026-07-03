<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('components.layouts.admin')] #[Title('Notendur')] class extends Component {
    // Edit modal
    public bool $showEdit = false;

    public ?int $editingUserId = null;

    public bool $isAdmin = false;

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    // Create modal
    public bool $showCreate = false;

    #[Validate('required|string|max:255')]
    public string $newName = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $newEmail = '';

    #[Validate('required|string|min:6')]
    public string $newPassword = '';

    public bool $newIsAdmin = false;

    /** @var array<int, string> */
    public array $newPermissions = [];

    /**
     * @return array<int, string>
     */
    public function permissionOptions(): array
    {
        return RolesAndPermissionsSeeder::PERMISSIONS;
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function users(): Collection
    {
        return User::with('roles', 'permissions')
            ->orderByRaw('approved_at is null desc')
            ->orderBy('name')
            ->get();
    }

    // --- Approval ---------------------------------------------------------

    public function approve(int $id): void
    {
        User::findOrFail($id)->approve();
        Flux::toast('Notandi samþykktur.', variant: 'success');
    }

    public function revoke(int $id): void
    {
        if ($id === auth()->id()) {
            Flux::toast('Þú getur ekki afturkallað þinn eigin aðgang.', variant: 'warning');

            return;
        }

        User::findOrFail($id)->revokeApproval();
        Flux::toast('Aðgangur afturkallaður.', variant: 'success');
    }

    // --- Edit roles/permissions ------------------------------------------

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->editingUserId = $user->id;
        $this->isAdmin = $user->hasRole('admin');
        $this->selectedPermissions = $user->getDirectPermissions()->pluck('name')->all();
        $this->showEdit = true;
    }

    public function saveUser(): void
    {
        $user = User::findOrFail($this->editingUserId);

        $keepsUserMgmt = $this->isAdmin || in_array('manage users', $this->selectedPermissions, true);

        if ($user->id === auth()->id() && ! $keepsUserMgmt) {
            Flux::toast('Þú getur ekki tekið af þér notendaumsjón.', variant: 'warning');

            return;
        }

        $user->syncRoles($this->isAdmin ? ['admin'] : []);
        // When admin, the role already grants everything, so clear direct perms.
        $user->syncPermissions($this->isAdmin ? [] : $this->selectedPermissions);

        // Make sure they can actually get in.
        $user->approve();

        Flux::toast('Réttindi vistuð.', variant: 'success');
        $this->showEdit = false;
    }

    // --- Create ----------------------------------------------------------

    public function create(): void
    {
        $this->reset(['newName', 'newEmail', 'newPassword', 'newIsAdmin', 'newPermissions']);
        $this->showCreate = true;
    }

    public function createUser(): void
    {
        $this->validate();

        $user = User::create([
            'name' => $this->newName,
            'email' => $this->newEmail,
            'password' => $this->newPassword,
        ]);

        // approved_at is not mass-assignable, so approve explicitly.
        $user->approve();

        if ($this->newIsAdmin) {
            $user->assignRole('admin');
        } else {
            $user->syncPermissions($this->newPermissions);
        }

        Flux::toast('Notandi búinn til.', variant: 'success');
        $this->showCreate = false;
    }
}; ?>

<div class="mx-auto w-full max-w-5xl">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Notendur</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nýr notandi</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Nafn</flux:table.column>
            <flux:table.column>Netfang</flux:table.column>
            <flux:table.column>Staða</flux:table.column>
            <flux:table.column>Réttindi</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell variant="strong">
                        {{ $user->name }}
                        @if ($user->id === auth()->id())
                            <span class="ml-1 text-xs font-normal text-zinc-400">(þú)</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($user->isApproved())
                            <flux:badge color="green" size="sm">Samþykktur</flux:badge>
                        @else
                            <flux:badge color="amber" size="sm">Bíður</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($user->hasRole('admin'))
                            <flux:badge color="red" size="sm">Stjórnandi</flux:badge>
                        @elseif ($user->permissions->isNotEmpty())
                            <span class="text-sm text-zinc-500">
                                {{ $user->permissions->map(fn ($p) => \App\Support\AdminNavigation::labelForPermission($p->name))->join(', ') }}
                            </span>
                        @else
                            <span class="text-sm text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-1">
                            @if (! $user->isApproved())
                                <flux:button wire:click="approve({{ $user->id }})" size="sm" variant="primary">Samþykkja</flux:button>
                            @endif
                            <flux:button wire:click="edit({{ $user->id }})" size="sm" variant="ghost" icon="key">Réttindi</flux:button>
                            @if ($user->isApproved() && $user->id !== auth()->id())
                                <flux:button
                                    wire:click="revoke({{ $user->id }})"
                                    wire:confirm="Afturkalla aðgang {{ $user->name }}?"
                                    size="sm" variant="ghost" icon="no-symbol" class="text-red-500"
                                />
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{-- Edit permissions modal --}}
    <flux:modal wire:model.self="showEdit" class="md:w-96">
        <form wire:submit="saveUser" class="space-y-5">
            <flux:heading size="lg">Réttindi notanda</flux:heading>

            <flux:switch wire:model.live="isAdmin" label="Stjórnandi (öll réttindi)" />

            @unless ($isAdmin)
                <flux:checkbox.group label="Réttindi">
                    @foreach ($this->permissionOptions() as $permission)
                        <flux:checkbox
                            wire:model="selectedPermissions"
                            :value="$permission"
                            :label="\App\Support\AdminNavigation::labelForPermission($permission)"
                        />
                    @endforeach
                </flux:checkbox.group>
            @endunless

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showEdit', false)" variant="ghost">Hætta við</flux:button>
                <flux:button type="submit" variant="primary">Vista</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Create user modal --}}
    <flux:modal wire:model.self="showCreate" class="md:w-96">
        <form wire:submit="createUser" class="space-y-5">
            <flux:heading size="lg">Nýr notandi</flux:heading>

            <flux:input wire:model="newName" label="Nafn" />
            <flux:input wire:model="newEmail" type="email" label="Netfang" />
            <flux:input wire:model="newPassword" type="password" label="Lykilorð" description="Að minnsta kosti 6 stafir" viewable />

            <flux:switch wire:model.live="newIsAdmin" label="Stjórnandi (öll réttindi)" />

            @unless ($newIsAdmin)
                <flux:checkbox.group label="Réttindi">
                    @foreach ($this->permissionOptions() as $permission)
                        <flux:checkbox
                            wire:model="newPermissions"
                            :value="$permission"
                            :label="\App\Support\AdminNavigation::labelForPermission($permission)"
                        />
                    @endforeach
                </flux:checkbox.group>
            @endunless

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showCreate', false)" variant="ghost">Hætta við</flux:button>
                <flux:button type="submit" variant="primary">Búa til</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
