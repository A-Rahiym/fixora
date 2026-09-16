<?php

namespace Database\Factories;

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'type' => InventoryMovementType::AdjustmentIn->value,
            'quantity_change' => fake()->numberBetween(1, 20),
            'reference_type' => 'manual',
            'reference_id' => null,
            'note' => null,
            'created_by' => User::factory(),
        ];
    }
}
