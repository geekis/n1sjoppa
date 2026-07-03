<?php

use App\Models\Category;
use App\Models\Product;
use App\Support\Isk;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] #[Title('Vörur')] class extends Component {
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|integer|min:0')]
    public int $price = 0;

    #[Validate('required|exists:categories,id')]
    public ?int $category_id = null;

    public bool $is_featured = false;

    public bool $is_active = true;

    #[Validate('required|integer|min:0')]
    public int $sort_order = 0;

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * @return LengthAwarePaginator<Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::with('category')
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'price', 'category_id', 'is_featured', 'is_active', 'sort_order']);
        $this->is_active = true;
        $this->category_id = $this->categories->first()?->id;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $product = Product::findOrFail($id);

        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->price = $product->price;
        $this->category_id = $product->category_id;
        $this->is_featured = $product->is_featured;
        $this->is_active = $product->is_active;
        $this->sort_order = $product->sort_order;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['is_featured'] = $this->is_featured;
        $data['is_active'] = $this->is_active;

        if ($this->editingId) {
            Product::findOrFail($this->editingId)->update($data);
            Flux::toast('Vara uppfærð.', variant: 'success');
        } else {
            Product::create($data);
            Flux::toast('Vöru bætt við.', variant: 'success');
        }

        $this->showModal = false;
    }

    public function toggleActive(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);
    }

    public function toggleFeatured(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_featured' => ! $product->is_featured]);
    }

    public function delete(int $id): void
    {
        // sale_items keep their snapshot (product_id is set null on delete),
        // so historical sales are preserved.
        Product::findOrFail($id)->delete();

        Flux::toast('Vöru eytt.', variant: 'success');
    }
}; ?>

<div class="mx-auto w-full max-w-5xl">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Vörur</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Ný vara</flux:button>
    </div>

    <flux:table :paginate="$this->products">
        <flux:table.columns>
            <flux:table.column>Nafn</flux:table.column>
            <flux:table.column>Flokkur</flux:table.column>
            <flux:table.column>Verð</flux:table.column>
            <flux:table.column>Áberandi</flux:table.column>
            <flux:table.column>Virk</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->products as $product)
                <flux:table.row :key="$product->id">
                    <flux:table.cell variant="strong">{{ $product->name }}</flux:table.cell>
                    <flux:table.cell>{{ $product->category?->name }}</flux:table.cell>
                    <flux:table.cell>{{ Isk::format($product->price) }}</flux:table.cell>
                    <flux:table.cell>
                        <button type="button" wire:click="toggleFeatured({{ $product->id }})">
                            <flux:badge :color="$product->is_featured ? 'amber' : 'zinc'" size="sm">
                                {{ $product->is_featured ? 'Já' : 'Nei' }}
                            </flux:badge>
                        </button>
                    </flux:table.cell>
                    <flux:table.cell>
                        <button type="button" wire:click="toggleActive({{ $product->id }})">
                            <flux:badge :color="$product->is_active ? 'green' : 'red'" size="sm">
                                {{ $product->is_active ? 'Virk' : 'Óvirk' }}
                            </flux:badge>
                        </button>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-1">
                            <flux:button wire:click="edit({{ $product->id }})" size="sm" variant="ghost" icon="pencil-square" />
                            <flux:button
                                wire:click="delete({{ $product->id }})"
                                wire:confirm="Eyða vörunni '{{ $product->name }}'? Sölusaga helst."
                                size="sm" variant="ghost" icon="trash"
                                class="text-red-500"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model.self="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">{{ $editingId ? 'Breyta vöru' : 'Ný vara' }}</flux:heading>

            <flux:input wire:model="name" label="Nafn" />
            <flux:input wire:model="price" type="number" label="Verð (kr.)" />

            <flux:select wire:model="category_id" label="Flokkur">
                @foreach ($this->categories as $category)
                    <flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="sort_order" type="number" label="Röðun" />

            <flux:switch wire:model="is_featured" label="Áberandi (birt efst í kassa)" />
            <flux:switch wire:model="is_active" label="Virk" />

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showModal', false)" variant="ghost">Hætta við</flux:button>
                <flux:button type="submit" variant="primary">Vista</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
