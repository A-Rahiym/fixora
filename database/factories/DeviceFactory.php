<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'category' => fake()->randomElement(['phone', 'laptop', 'console', 'tv', 'other']),
            'brand' => fake()->randomElement(['Apple', 'Samsung', 'Dell', 'Sony', 'HP']),
            'model' => fake()->word().' '.fake()->randomNumber(4),
            'serial_number' => fake()->optional()->bothify('SN-########'),
            'imei' => fake()->optional()->numerify('################'),
            'color' => fake()->optional()->colorName(),
            'notes' => null,
        ];
    }
}
