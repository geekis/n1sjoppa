<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Staff;

it('relates a product to its category', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    expect($product->category->id)->toBe($category->id)
        ->and($category->products)->toHaveCount(1);
});

it('relates a sale to staff and items', function () {
    $staff = Staff::factory()->create();
    $sale = Sale::factory()->for($staff)->create();
    SaleItem::factory()->count(2)->for($sale)->create();

    expect($sale->staff->id)->toBe($staff->id)
        ->and($sale->items)->toHaveCount(2)
        ->and($staff->sales)->toHaveCount(1);
});

it('nulls sale_item product_id when the product is deleted but keeps snapshot', function () {
    $product = Product::factory()->create(['name' => 'Pylsa', 'price' => 550]);
    $item = SaleItem::factory()->create([
        'product_id' => $product->id,
        'name' => 'Pylsa',
        'unit_price' => 550,
    ]);

    $product->delete();

    $item->refresh();
    expect($item->product_id)->toBeNull()
        ->and($item->name)->toBe('Pylsa')
        ->and($item->unit_price)->toBe(550);
});
