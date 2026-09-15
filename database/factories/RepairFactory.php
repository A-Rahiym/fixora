<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Device;
use App\Models\Repair;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repair>
 */
class RepairFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // job_number is assigned by RepairJobNumberGenerator on creating;
            // the placeholder keeps factory-only (non-persisted) models valid.
            'job_number' => 'REP-TMP-'.fake()->unique()->randomNumber(6),
            'customer_id' => Customer::factory(),
            'device_id' => Device::factory(),
            'assigned_technician_id' => null,
            'status' => 'received',
            'priority' => 'normal',
            'reported_problem' => fake()->sentence(),
            'intake_condition' => null,
            'estimate_amount' => null,
            'final_cost' => null,
            'expected_completion_at' => null,
            'approved_at' => null,
            'collected_at' => null,
            'warranty_days' => 0,
            'warranty_expires_at' => null,
            'created_by' => User::factory(),
        ];
    }
}
