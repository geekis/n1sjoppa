<?php

use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\Isk;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin')] #[Title('Skýrslur')] class extends Component {
    public string $from = '';

    public string $to = '';

    public string $preset = 'today';

    public function mount(): void
    {
        $this->applyPreset('today');
    }

    public function applyPreset(string $preset): void
    {
        $this->preset = $preset;

        $this->from = match ($preset) {
            'week' => now()->startOfWeek()->toDateString(),
            'month' => now()->startOfMonth()->toDateString(),
            default => now()->toDateString(),
        };
        $this->to = now()->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['from', 'to'], true)) {
            $this->preset = 'custom';
        }
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    private function range(): array
    {
        return [
            'start' => Carbon::parse($this->from)->startOfDay(),
            'end' => Carbon::parse($this->to)->endOfDay(),
        ];
    }

    /**
     * @return array{sales: int, revenue: int}
     */
    #[Computed]
    public function totals(): array
    {
        ['start' => $start, 'end' => $end] = $this->range();

        $query = Sale::whereBetween('completed_at', [$start, $end]);

        return [
            'sales' => (clone $query)->count(),
            'revenue' => (int) (clone $query)->sum('total'),
        ];
    }

    /**
     * @return Collection<int, object{name: string, quantity: int, revenue: int}>
     */
    #[Computed]
    public function byItem(): Collection
    {
        ['start' => $start, 'end' => $end] = $this->range();

        return SaleItem::query()
            ->selectRaw('sale_items.name, SUM(sale_items.quantity) as quantity, SUM(sale_items.line_total) as revenue')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.completed_at', [$start, $end])
            ->groupBy('sale_items.name')
            ->orderByDesc('revenue')
            ->get();
    }

    /**
     * @return Collection<int, object{name: string, sales_count: int, revenue: int}>
     */
    #[Computed]
    public function byStaff(): Collection
    {
        ['start' => $start, 'end' => $end] = $this->range();

        return Sale::query()
            ->selectRaw('staff.name, COUNT(sales.id) as sales_count, SUM(sales.total) as revenue')
            ->join('staff', 'staff.id', '=', 'sales.staff_id')
            ->whereBetween('sales.completed_at', [$start, $end])
            ->groupBy('staff.name')
            ->orderByDesc('revenue')
            ->get();
    }
}; ?>

<div class="mx-auto w-full max-w-5xl">
    <flux:heading size="xl" class="mb-6">Skýrslur</flux:heading>

    {{-- Date range filter --}}
    <div class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex gap-1.5">
            @foreach (['today' => 'Í dag', 'week' => 'Þessi vika', 'month' => 'Þessi mánuður'] as $key => $label)
                <flux:button
                    wire:click="applyPreset('{{ $key }}')"
                    size="sm"
                    :variant="$preset === $key ? 'primary' : 'filled'"
                >{{ $label }}</flux:button>
            @endforeach
        </div>
        <flux:input wire:model.live="from" type="date" label="Frá" size="sm" />
        <flux:input wire:model.live="to" type="date" label="Til" size="sm" />
    </div>

    {{-- Overall totals --}}
    <div class="mb-6 grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Fjöldi sölufærslna</p>
            <p class="mt-1 text-3xl font-extrabold">{{ number_format($this->totals['sales'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Heildarvelta</p>
            <p class="mt-1 text-3xl font-extrabold text-n1-red">{{ Isk::format($this->totals['revenue']) }}</p>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-2">
        {{-- Sales by item --}}
        <div>
            <flux:heading size="lg" class="mb-3">Sala eftir vöru</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Vara</flux:table.column>
                    <flux:table.column align="end">Fjöldi</flux:table.column>
                    <flux:table.column align="end">Velta</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->byItem as $row)
                        <flux:table.row :key="$loop->index">
                            <flux:table.cell variant="strong">{{ $row->name }}</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($row->quantity, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell align="end">{{ Isk::format((int) $row->revenue) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-center text-zinc-400">Engin sala á tímabilinu.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        {{-- Sales by staff --}}
        <div>
            <flux:heading size="lg" class="mb-3">Sala eftir starfsfólki</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Starfsmaður</flux:table.column>
                    <flux:table.column align="end">Sölur</flux:table.column>
                    <flux:table.column align="end">Velta</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->byStaff as $row)
                        <flux:table.row :key="$loop->index">
                            <flux:table.cell variant="strong">{{ $row->name }}</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($row->sales_count, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell align="end">{{ Isk::format((int) $row->revenue) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-center text-zinc-400">Engin sala á tímabilinu.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
