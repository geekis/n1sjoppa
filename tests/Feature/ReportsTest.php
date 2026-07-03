<?php

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function saleWithItems(Staff $staff, array $items, ?string $completedAt = null): Sale
{
    $total = collect($items)->sum(fn ($i) => $i['unit_price'] * $i['quantity']);
    $count = collect($items)->sum('quantity');

    $sale = Sale::factory()->for($staff)->create([
        'total' => $total,
        'item_count' => $count,
        'completed_at' => $completedAt ? Carbon::parse($completedAt) : now(),
    ]);

    foreach ($items as $item) {
        SaleItem::factory()->for($sale)->create([
            'name' => $item['name'],
            'unit_price' => $item['unit_price'],
            'quantity' => $item['quantity'],
            'line_total' => $item['unit_price'] * $item['quantity'],
        ]);
    }

    return $sale;
}

it('reports overall totals for today', function () {
    $this->actingAs(User::factory()->create());
    $staff = Staff::factory()->create();

    saleWithItems($staff, [['name' => 'Pylsa', 'unit_price' => 550, 'quantity' => 2]]);
    saleWithItems($staff, [['name' => 'Kók', 'unit_price' => 450, 'quantity' => 1]]);

    Livewire::test('pages::admin.reports')
        ->assertSet('totals.sales', 2)
        ->assertSet('totals.revenue', 1550);
});

it('aggregates sales by item ordered by revenue', function () {
    $this->actingAs(User::factory()->create());
    $staff = Staff::factory()->create();

    saleWithItems($staff, [['name' => 'Pylsa', 'unit_price' => 550, 'quantity' => 2]]);
    saleWithItems($staff, [['name' => 'Pylsa', 'unit_price' => 550, 'quantity' => 1]]);
    saleWithItems($staff, [['name' => 'Kók', 'unit_price' => 450, 'quantity' => 1]]);

    $byItem = Livewire::test('pages::admin.reports')->instance()->byItem;

    expect($byItem)->toHaveCount(2)
        ->and($byItem->first()->name)->toBe('Pylsa')
        ->and((int) $byItem->first()->quantity)->toBe(3)
        ->and((int) $byItem->first()->revenue)->toBe(1650);
});

it('aggregates sales by staff', function () {
    $this->actingAs(User::factory()->create());
    $anna = Staff::factory()->create(['name' => 'Anna']);
    $bjarni = Staff::factory()->create(['name' => 'Bjarni']);

    saleWithItems($anna, [['name' => 'Pylsa', 'unit_price' => 550, 'quantity' => 2]]);
    saleWithItems($bjarni, [['name' => 'Kók', 'unit_price' => 450, 'quantity' => 1]]);

    $byStaff = Livewire::test('pages::admin.reports')->instance()->byStaff;

    expect($byStaff)->toHaveCount(2)
        ->and($byStaff->first()->name)->toBe('Anna')
        ->and((int) $byStaff->first()->revenue)->toBe(1100);
});

it('excludes sales outside the selected range', function () {
    $this->actingAs(User::factory()->create());
    $staff = Staff::factory()->create();

    saleWithItems($staff, [['name' => 'Pylsa', 'unit_price' => 550, 'quantity' => 1]], now()->subMonth()->toDateString());

    Livewire::test('pages::admin.reports')
        ->call('applyPreset', 'today')
        ->assertSet('totals.sales', 0)
        ->assertSet('totals.revenue', 0);
});
