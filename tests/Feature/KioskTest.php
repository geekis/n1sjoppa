<?php

use App\Models\Category;
use App\Models\DailyPin;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Staff;
use Livewire\Livewire;

function makeProduct(int $price = 450, array $overrides = []): Product
{
    $category = Category::factory()->create();

    return Product::factory()->for($category)->create(array_merge([
        'price' => $price,
        'is_active' => true,
    ], $overrides));
}

it('redirects to staff select when no staff in session', function () {
    $this->get(route('kiosk'))->assertRedirect(route('kiosk.staff'));
});

it('requires the daily PIN before starting a session', function () {
    $staff = Staff::factory()->create();
    DailyPin::setToday('1234');

    // Picking a name moves to the PIN step, does NOT start a session yet.
    $component = Livewire::test('pages::kiosk.staff')
        ->call('selectStaff', $staff->id)
        ->assertSet('selectedStaffId', $staff->id);

    expect(session('kiosk_staff_id'))->toBeNull();

    // Correct PIN starts the session.
    $component
        ->call('pressDigit', '1')
        ->call('pressDigit', '2')
        ->call('pressDigit', '3')
        ->call('pressDigit', '4')
        ->assertRedirect(route('kiosk'));

    expect(session('kiosk_staff_id'))->toBe($staff->id);
});

it('rejects a wrong PIN and clears it', function () {
    $staff = Staff::factory()->create();
    DailyPin::setToday('1234');

    Livewire::test('pages::kiosk.staff')
        ->call('selectStaff', $staff->id)
        ->call('pressDigit', '9')
        ->call('pressDigit', '9')
        ->call('pressDigit', '9')
        ->call('pressDigit', '9')
        ->assertSet('pin', '')
        ->assertSet('error', 'Rangt PIN. Reyndu aftur.')
        ->assertNoRedirect();

    expect(session('kiosk_staff_id'))->toBeNull();
});

it('blocks login when no PIN is set for the day', function () {
    $staff = Staff::factory()->create();

    Livewire::test('pages::kiosk.staff')
        ->call('selectStaff', $staff->id)
        ->call('pressDigit', '0')
        ->call('pressDigit', '0')
        ->call('pressDigit', '0')
        ->call('pressDigit', '0')
        ->assertNoRedirect();

    expect(session('kiosk_staff_id'))->toBeNull();
});

it('creates a new staff member then requires the PIN', function () {
    Livewire::test('pages::kiosk.staff')
        ->set('newName', 'Ragnheiður')
        ->call('addStaff')
        ->assertNoRedirect()
        ->assertSet('selectedStaffId', fn ($id) => $id !== null);

    expect(Staff::where('name', 'Ragnheiður')->exists())->toBeTrue()
        ->and(session('kiosk_staff_id'))->toBeNull();
});

it('adds products to the cart and increments on repeat tap', function () {
    $staff = Staff::factory()->create();
    session(['kiosk_staff_id' => $staff->id]);
    $product = makeProduct(450);

    Livewire::test('pages::kiosk.index')
        ->call('addProduct', $product->id)
        ->call('addProduct', $product->id)
        ->assertSet('cart.'.$product->id.'.quantity', 2)
        ->assertSet('total', 900);
});

it('decrements and removes cart lines', function () {
    $staff = Staff::factory()->create();
    session(['kiosk_staff_id' => $staff->id]);
    $product = makeProduct(450);

    Livewire::test('pages::kiosk.index')
        ->call('addProduct', $product->id)
        ->call('decrement', $product->id)
        ->assertSet('cart', []);
});

it('persists a sale with snapshotted name and price on finish', function () {
    $staff = Staff::factory()->create();
    session(['kiosk_staff_id' => $staff->id]);
    $product = makeProduct(550, ['name' => 'Pylsa']);

    Livewire::test('pages::kiosk.index')
        ->call('addProduct', $product->id)
        ->call('addProduct', $product->id)
        ->call('finish')
        ->assertSet('cart', []);

    $sale = Sale::first();
    expect($sale)->not->toBeNull()
        ->and($sale->staff_id)->toBe($staff->id)
        ->and($sale->total)->toBe(1100)
        ->and($sale->item_count)->toBe(2)
        ->and($sale->completed_at)->not->toBeNull()
        ->and($sale->items)->toHaveCount(1);

    $item = $sale->items->first();
    expect($item->name)->toBe('Pylsa')
        ->and($item->unit_price)->toBe(550)
        ->and($item->quantity)->toBe(2)
        ->and($item->line_total)->toBe(1100);
});

it('keeps the charged price even if the product price changes after adding', function () {
    $staff = Staff::factory()->create();
    session(['kiosk_staff_id' => $staff->id]);
    $product = makeProduct(550, ['name' => 'Pylsa']);

    $component = Livewire::test('pages::kiosk.index')
        ->call('addProduct', $product->id);

    // Price changes in admin after item is in the cart.
    $product->update(['price' => 900]);

    $component->call('finish');

    $item = Sale::first()->items->first();
    expect($item->unit_price)->toBe(550)
        ->and($item->line_total)->toBe(550);
});

it('does not create a sale when the cart is empty', function () {
    $staff = Staff::factory()->create();
    session(['kiosk_staff_id' => $staff->id]);

    Livewire::test('pages::kiosk.index')->call('finish');

    expect(Sale::count())->toBe(0);
});
