<?php

namespace Database\Factories;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => InventoryCategory::factory(),
            'sku' => fake()->unique()->bothify('SKU-########'),
            'name' => fake()->words(3, true),
            'description' => null,
            'unit_cost' => fake()->randomFloat(2, 1, 200),
            'unit_price' => fake()->randomFloat(2, 1, 400),
            'quantity' => fake()->numberBetween(0, 50),
            'low_stock_threshold' => 5,
            'preferred_supplier_id' => null,
        ];
    }
}
