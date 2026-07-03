<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Wipe sales + catalog, then reseed products and categories.
     */
    public function run(): void
    {
        SaleItem::query()->delete();
        Sale::query()->delete();
        Product::query()->delete();
        Category::query()->delete();

        foreach (self::catalog() as $categoryIndex => $categoryData) {
            $category = Category::create([
                'name' => $categoryData['name'],
                'sort_order' => $categoryIndex,
                'is_active' => true,
            ]);

            foreach ($categoryData['products'] as $productIndex => $productData) {
                Product::create([
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'price' => $productData['price'],
                    'is_featured' => $productData['featured'] ?? false,
                    'is_active' => true,
                    'sort_order' => $productIndex,
                ]);
            }
        }
    }

    /**
     * @return array<int, array{name: string, products: array<int, array{name: string, price: int, featured?: bool}>}>
     */
    public static function catalog(): array
    {
        return [
            [
                'name' => 'Drykkir',
                'products' => [
                    ['name' => 'Kaffibolli', 'price' => 500, 'featured' => true],
                    ['name' => 'Kaffikort - 10 bollar', 'price' => 4500],
                    ['name' => 'Kakó með rjóma', 'price' => 550],
                    ['name' => 'Bonaqua Gulur 0,5ltr', 'price' => 400],
                    ['name' => 'Bonaqua Blár 0,5ltr', 'price' => 400],
                    ['name' => 'Coke Plast 0,5ltr', 'price' => 400],
                    ['name' => 'Coke Zero Plast 0,5ltr', 'price' => 400],
                    ['name' => 'Coke Dós', 'price' => 350, 'featured' => true],
                    ['name' => 'Coke Zero Dós', 'price' => 350],
                    ['name' => 'Powerade Blár', 'price' => 500],
                    ['name' => 'Powerade Gulur', 'price' => 500],
                    ['name' => 'Powerade Hvítur', 'price' => 500],
                    ['name' => 'Powerade Rauður', 'price' => 500],
                    ['name' => 'Nocco Blár', 'price' => 500],
                    ['name' => 'Nocco Bleikur', 'price' => 500],
                    ['name' => 'Monster Bleikur', 'price' => 600],
                    ['name' => 'Monster Hvítur', 'price' => 600],
                    ['name' => 'Eplasafi', 'price' => 350],
                    ['name' => 'Appelsínusafi', 'price' => 350],
                    ['name' => 'Milkshake Jarðaberja', 'price' => 650],
                    ['name' => 'Milkshake Súkkulaði', 'price' => 650],
                    ['name' => 'Froosh Mango/Appelsínu', 'price' => 700],
                    ['name' => 'AB Skvísa - Ferskju', 'price' => 400],
                    ['name' => 'AB Skvísa - Melónu', 'price' => 400],
                    ['name' => 'Hleðsla Rauð', 'price' => 500],
                ],
            ],
            [
                'name' => 'Sælgæti',
                'products' => [
                    ['name' => 'Mars', 'price' => 350],
                    ['name' => 'Twix', 'price' => 350],
                    ['name' => 'Nóa kropp stykki', 'price' => 550],
                    ['name' => 'Eitt set', 'price' => 450],
                    ['name' => 'Nóa Tromp', 'price' => 450],
                    ['name' => 'Kaffisúkkulaði', 'price' => 450],
                    ['name' => 'Stjörnurúlla', 'price' => 200],
                    ['name' => 'Mentos Fruit', 'price' => 300],
                    ['name' => 'Nömm', 'price' => 400],
                    ['name' => 'Knatter', 'price' => 400],
                    ['name' => 'Nóa TRÍTLAR poki', 'price' => 650],
                    ['name' => 'Prótein stykki Caramel choco', 'price' => 550],
                    ['name' => 'Prótein stykki Banana dream', 'price' => 550],
                    ['name' => 'Hafrastykki súkkulaði', 'price' => 550],
                    ['name' => 'Hafrastykki banana', 'price' => 550],
                    ['name' => 'Hafrastykki karamellu', 'price' => 550],
                    ['name' => 'Pringles', 'price' => 500],
                ],
            ],
            [
                'name' => 'Brauð og ávextir',
                'products' => [
                    ['name' => 'Grilluð samloka', 'price' => 700, 'featured' => true],
                    ['name' => 'Kleina', 'price' => 300, 'featured' => true],
                    ['name' => 'Skúffukaka', 'price' => 450],
                    ['name' => 'Ostaslaufa', 'price' => 600],
                    ['name' => 'Soðið brauð með hangikjöti', 'price' => 750],
                    ['name' => 'Smurt rúnstykki', 'price' => 650],
                    ['name' => 'Pizzasneið', 'price' => 600],
                    ['name' => 'Vaffla með rjóma og sultu', 'price' => 650],
                    ['name' => 'Banani', 'price' => 250],
                ],
            ],
        ];
    }
}
