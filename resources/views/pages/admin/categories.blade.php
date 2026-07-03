<?php

use App\Models\Category;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('components.layouts.admin')] #[Title('Flokkar')] class extends Component {
    public bool $showModal = false;

    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|integer|min:0')]
    public int $sort_order = 0;

    public bool $is_active = true;

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'sort_order', 'is_active']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->sort_order = $category->sort_order;
        $this->is_active = $category->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['is_active'] = $this->is_active;

        if ($this->editingId) {
            Category::findOrFail($this->editingId)->update($data);
            Flux::toast('Flokkur uppfærður.', variant: 'success');
        } else {
            Category::create($data);
            Flux::toast('Flokki bætt við.', variant: 'success');
        }

        $this->showModal = false;
    }

    public function toggleActive(int $id): void
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function delete(int $id): void
    {
        $category = Category::withCount('products')->findOrFail($id);

        if ($category->products_count > 0) {
            Flux::toast(
                'Ekki hægt að eyða flokki með vörum. Færðu eða eyddu vörunum fyrst.',
                variant: 'warning',
            );

            return;
        }

        $category->delete();

        Flux::toast('Flokki eytt.', variant: 'success');
    }
}; ?>

<div class="mx-auto w-full max-w-3xl">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Flokkar</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nýr flokkur</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Nafn</flux:table.column>
            <flux:table.column>Röðun</flux:table.column>
            <flux:table.column>Vörur</flux:table.column>
            <flux:table.column>Virkur</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->categories as $category)
                <flux:table.row :key="$category->id">
                    <flux:table.cell variant="strong">{{ $category->name }}</flux:table.cell>
                    <flux:table.cell>{{ $category->sort_order }}</flux:table.cell>
                    <flux:table.cell>{{ $category->products_count }}</flux:table.cell>
                    <flux:table.cell>
                        <button type="button" wire:click="toggleActive({{ $category->id }})">
                            <flux:badge :color="$category->is_active ? 'green' : 'red'" size="sm">
                                {{ $category->is_active ? 'Virkur' : 'Óvirkur' }}
                            </flux:badge>
                        </button>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-1">
                            <flux:button wire:click="edit({{ $category->id }})" size="sm" variant="ghost" icon="pencil-square" />
                            <flux:button
                                wire:click="delete({{ $category->id }})"
                                wire:confirm="Eyða flokknum '{{ $category->name }}'?"
                                size="sm" variant="ghost" icon="trash"
                                class="text-red-500"
                                :disabled="$category->products_count > 0"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model.self="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">{{ $editingId ? 'Breyta flokki' : 'Nýr flokkur' }}</flux:heading>

            <flux:input wire:model="name" label="Nafn" />
            <flux:input wire:model="sort_order" type="number" label="Röðun" />
            <flux:switch wire:model="is_active" label="Virkur" />

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showModal', false)" variant="ghost">Hætta við</flux:button>
                <flux:button type="submit" variant="primary">Vista</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
