<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'total' => 0,
            'item_count' => 0,
            'completed_at' => now(),
        ];
    }
}
