<?php

use App\Models\Category;
use App\Models\DailyPin;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Staff;
use App\Models\User;
use Livewire\Livewire;

it('redirects guests away from admin', function () {
    $this->get(route('admin.products'))->assertRedirect(route('login'));
});

it('renders admin pages for authenticated users', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.reports'))->assertOk();
    $this->get(route('admin.products'))->assertOk();
    $this->get(route('admin.categories'))->assertOk();
    $this->get(route('admin.staff'))->assertOk();
});

it('creates a product through the admin form', function () {
    $this->actingAs(User::factory()->create());
    $category = Category::factory()->create();

    Livewire::test('pages::admin.products')
        ->call('create')
        ->set('name', 'Kók 0,5l')
        ->set('price', 450)
        ->set('category_id', $category->id)
        ->set('is_featured', true)
        ->call('save')
        ->assertSet('showModal', false);

    $product = Product::first();
    expect($product->name)->toBe('Kók 0,5l')
        ->and($product->price)->toBe(450)
        ->and($product->is_featured)->toBeTrue();
});

it('validates required product fields', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.products')
        ->call('create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors('name');
});

it('toggles product active state', function () {
    $this->actingAs(User::factory()->create());
    $product = Product::factory()->create(['is_active' => true]);

    Livewire::test('pages::admin.products')
        ->call('toggleActive', $product->id);

    expect($product->fresh()->is_active)->toBeFalse();
});

it('sets the daily kiosk PIN manually', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.staff')
        ->set('pinInput', '4821')
        ->call('savePin')
        ->assertHasNoErrors();

    expect(DailyPin::forToday()?->pin)->toBe('4821');
});

it('rejects a PIN that is not 4 digits', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.staff')
        ->set('pinInput', '12')
        ->call('savePin')
        ->assertHasErrors('pinInput');

    expect(DailyPin::forToday())->toBeNull();
});

it('generates a random 4-digit daily PIN', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.staff')->call('generatePin');

    $pin = DailyPin::forToday()?->pin;
    expect($pin)->toMatch('/^\d{4}$/');
});

it('deactivates staff rather than deleting', function () {
    $this->actingAs(User::factory()->create());
    $staff = Staff::factory()->create(['is_active' => true]);

    Livewire::test('pages::admin.staff')
        ->call('toggleActive', $staff->id);

    expect($staff->fresh()->is_active)->toBeFalse()
        ->and(Staff::whereKey($staff->id)->exists())->toBeTrue();
});

it('deletes a product and keeps sale history via snapshot', function () {
    $this->actingAs(User::factory()->create());
    $product = Product::factory()->create(['name' => 'Pylsa', 'price' => 550]);
    $item = SaleItem::factory()->create([
        'product_id' => $product->id,
        'name' => 'Pylsa',
        'unit_price' => 550,
    ]);

    Livewire::test('pages::admin.products')->call('delete', $product->id);

    expect(Product::whereKey($product->id)->exists())->toBeFalse();
    $item->refresh();
    expect($item->product_id)->toBeNull()
        ->and($item->name)->toBe('Pylsa')
        ->and($item->unit_price)->toBe(550);
});

it('deletes an empty category', function () {
    $this->actingAs(User::factory()->create());
    $category = Category::factory()->create();

    Livewire::test('pages::admin.categories')->call('delete', $category->id);

    expect(Category::whereKey($category->id)->exists())->toBeFalse();
});

it('refuses to delete a category that still has products', function () {
    $this->actingAs(User::factory()->create());
    $category = Category::factory()->create();
    Product::factory()->for($category)->create();

    Livewire::test('pages::admin.categories')->call('delete', $category->id);

    expect(Category::whereKey($category->id)->exists())->toBeTrue();
});

it('creates a category through the admin form', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.categories')
        ->call('create')
        ->set('name', 'Drykkir')
        ->set('sort_order', 1)
        ->call('save')
        ->assertSet('showModal', false);

    expect(Category::where('name', 'Drykkir')->exists())->toBeTrue();
});
