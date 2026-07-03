<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Staff;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.kiosk')] #[Title('Sjoppan')] class extends Component {
    public ?int $categoryId = null;

    /**
     * Cart lines keyed by product id. Each line snapshots name + unit price at add time.
     *
     * @var array<int, array{product_id: int, name: string, unit_price: int, quantity: int}>
     */
    public array $cart = [];

    public function mount(): void
    {
        if (! $this->staff) {
            $this->redirectRoute('kiosk.staff', navigate: true);

            return;
        }

        $this->categoryId = $this->categories->first()?->id;
    }

    #[Computed]
    public function staff(): ?Staff
    {
        $id = session('kiosk_staff_id');

        return $id ? Staff::where('is_active', true)->find($id) : null;
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function featured(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        if (! $this->categoryId) {
            return collect();
        }

        return Product::query()
            ->where('is_active', true)
            ->where('category_id', $this->categoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function selectCategory(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function addProduct(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;

            return;
        }

        $product = Product::where('is_active', true)->find($productId);

        if (! $product) {
            return;
        }

        $this->cart[$productId] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 1,
        ];
    }

    public function increment(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        }
    }

    public function decrement(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $this->cart[$productId]['quantity']--;

        if ($this->cart[$productId]['quantity'] < 1) {
            unset($this->cart[$productId]);
        }
    }

    public function removeLine(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    #[Computed]
    public function total(): int
    {
        return collect($this->cart)->sum(fn (array $line): int => $line['unit_price'] * $line['quantity']);
    }

    #[Computed]
    public function itemCount(): int
    {
        return collect($this->cart)->sum(fn (array $line): int => $line['quantity']);
    }

    public function finish(): void
    {
        if (! $this->staff || $this->cart === []) {
            return;
        }

        $total = $this->total;
        $itemCount = $this->itemCount;

        DB::transaction(function () use ($total, $itemCount): void {
            $sale = Sale::create([
                'staff_id' => $this->staff->id,
                'total' => $total,
                'item_count' => $itemCount,
                'completed_at' => now(),
            ]);

            foreach ($this->cart as $line) {
                $sale->items()->create([
                    'product_id' => $line['product_id'],
                    'name' => $line['name'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['unit_price'] * $line['quantity'],
                ]);
            }
        });

        $this->cart = [];

        Flux::toast(
            heading: 'Skráð!',
            text: 'Samtals '.\App\Support\Isk::format($total),
            variant: 'success',
        );
    }
}; ?>

<div
    x-data="{ pad: 176 }"
    x-init="
        const bar = $refs.cartbar;
        const sync = () => { pad = bar.offsetHeight + 16; };
        new ResizeObserver(sync).observe(bar);
        sync();
    "
    :style="`padding-bottom: ${pad}px`"
>
    {{-- Featured quick-access row --}}
    @if ($this->featured->isNotEmpty())
        <div class="border-b border-zinc-200 bg-white px-3 py-3">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach ($this->featured as $product)
                    <button
                        type="button"
                        wire:key="feat-{{ $product->id }}"
                        wire:click="addProduct({{ $product->id }})"
                        class="flex min-h-[68px] flex-col items-center justify-center gap-0.5 rounded-xl bg-n1-red px-2 py-2 text-white shadow-sm transition active:scale-95 active:bg-n1-red-dark"
                    >
                        <span class="text-sm font-bold leading-tight">{{ $product->name }}</span>
                        <span class="text-xs font-semibold text-white/90"><x-isk :amount="$product->price" /></span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Category pills --}}
    <div class="sticky top-[57px] z-20 border-b border-zinc-200 bg-zinc-50/95 backdrop-blur">
        <div class="flex gap-2 overflow-x-auto px-3 py-2.5">
            @foreach ($this->categories as $category)
                <button
                    type="button"
                    wire:key="cat-{{ $category->id }}"
                    wire:click="selectCategory({{ $category->id }})"
                    @class([
                        'shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition',
                        'bg-n1-dark text-white' => $this->categoryId === $category->id,
                        'bg-white text-zinc-700 ring-1 ring-zinc-200' => $this->categoryId !== $category->id,
                    ])
                >
                    {{ $category->name }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Item grid --}}
    <div class="grid grid-cols-2 gap-2.5 p-3 sm:grid-cols-3">
        @forelse ($this->products as $product)
            <button
                type="button"
                wire:key="prod-{{ $product->id }}"
                wire:click="addProduct({{ $product->id }})"
                class="relative flex min-h-[92px] flex-col justify-between rounded-xl border border-zinc-200 bg-white p-3 text-left shadow-sm transition active:scale-[0.98] active:bg-zinc-50"
            >
                <span class="text-sm font-semibold leading-tight text-zinc-900">{{ $product->name }}</span>
                <span class="mt-1 text-base font-bold text-n1-red"><x-isk :amount="$product->price" /></span>
                @if (isset($cart[$product->id]))
                    <span class="absolute right-2 top-2 flex h-6 min-w-6 items-center justify-center rounded-full bg-n1-red px-1.5 text-xs font-bold text-white">
                        {{ $cart[$product->id]['quantity'] }}
                    </span>
                @endif
            </button>
        @empty
            <p class="col-span-full py-10 text-center text-sm text-zinc-400">Engar vörur í þessum flokki.</p>
        @endforelse
    </div>

    {{-- Sticky cart summary --}}
    <div x-ref="cartbar" class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
        <div class="mx-auto max-w-3xl">
            @if ($cart !== [])
                <div class="max-h-52 overflow-y-auto px-3 pt-2">
                    @foreach ($cart as $productId => $line)
                        <div wire:key="line-{{ $productId }}" class="flex items-center gap-2 border-b border-zinc-100 py-2 last:border-0">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-zinc-900">{{ $line['name'] }}</p>
                                <p class="text-xs text-zinc-500">
                                    <x-isk :amount="$line['unit_price']" /> &middot;
                                    <span class="font-semibold text-zinc-700"><x-isk :amount="$line['unit_price'] * $line['quantity']" /></span>
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" wire:click="decrement({{ $productId }})"
                                    class="flex h-11 w-11 items-center justify-center rounded-lg bg-zinc-100 text-xl font-bold text-zinc-700 active:bg-zinc-200">–</button>
                                <span class="w-7 text-center text-base font-bold">{{ $line['quantity'] }}</span>
                                <button type="button" wire:click="increment({{ $productId }})"
                                    class="flex h-11 w-11 items-center justify-center rounded-lg bg-zinc-100 text-xl font-bold text-zinc-700 active:bg-zinc-200">+</button>
                                <button type="button" wire:click="removeLine({{ $productId }})"
                                    class="flex h-11 w-11 items-center justify-center rounded-lg text-zinc-400 active:text-n1-red">
                                    <flux:icon.trash variant="micro" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center justify-between px-4 py-2.5">
                <span class="text-sm font-medium text-zinc-500">
                    {{ $this->itemCount }} {{ $this->itemCount === 1 ? 'hlutur' : 'hlutir' }}
                </span>
                <span class="text-3xl font-extrabold tracking-tight text-zinc-900" data-test="cart-total">
                    <x-isk :amount="$this->total" />
                </span>
            </div>

            <div class="px-3 pb-3">
                <button
                    type="button"
                    wire:click="finish"
                    wire:loading.attr="disabled"
                    @disabled($cart === [])
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-n1-red py-4 text-lg font-bold text-white shadow-md transition active:scale-[0.99] active:bg-n1-red-dark disabled:cursor-not-allowed disabled:bg-zinc-300"
                >
                    <span wire:loading.remove wire:target="finish">Ljúka &middot; Finish</span>
                    <span wire:loading wire:target="finish">Skrái…</span>
                </button>
            </div>
        </div>
    </div>
</div>
