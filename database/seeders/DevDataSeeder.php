<?php

namespace Database\Seeders;

use App\Enums\RepairStatus;
use App\Models\Customer;
use App\Models\Device;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Repair;
use App\Models\RepairDiagnosis;
use App\Models\RepairStatusHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\RepairJobNumberGenerator;
use Illuminate\Database\Seeder;

/**
 * Local development data only — never wired into DatabaseSeeder.
 *
 * Run explicitly: php artisan db:seed --class='Database\Seeders\DevDataSeeder'
 *
 * Sections top up to their target counts so reruns stay bounded; the
 * repairs section only runs once (it models a 14-day history window).
 */
class DevDataSeeder extends Seeder
{
    /**
     * Seed demo customers, devices, technicians, inventory and repairs.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $this->seedCustomersAndDevices();
        $technicians = $this->seedTechnicians();
        $this->seedInventory();
        $this->seedRepairs($technicians);
    }

    protected function seedCustomersAndDevices(): void
    {
        $missing = max(0, 20 - Customer::count());

        Customer::factory()
            ->count($missing)
            ->create()
            ->each(function (Customer $customer): void {
                Device::factory()
                    ->count(fake()->numberBetween(1, 2))
                    ->for($customer)
                    ->create();
            });
    }

    /**
     * @return list<User>
     */
    protected function seedTechnicians(): array
    {
        $role = Role::where('name', 'technician')->firstOrFail();

        $missing = max(0, 3 - User::where('role_id', $role->id)->count());

        User::factory()->count($missing)->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        return User::where('role_id', $role->id)->orderBy('id')->take(3)->get()->all();
    }

    protected function seedInventory(): void
    {
        $missing = max(0, 15 - InventoryItem::count());

        // The category factory only knows 6 unique names, so cycle the
        // existing rows instead of exhausting its unique pool.
        $categoryIds = InventoryCategory::pluck('id')->all();

        if ($categoryIds === []) {
            $categoryIds = InventoryCategory::factory()->count(6)->create()->pluck('id')->all();
        }

        for ($i = 0; $i < $missing; $i++) {
            $low = $i % 3 === 0;

            InventoryItem::factory()->create([
                'category_id' => $categoryIds[$i % count($categoryIds)],
                'quantity' => $low ? fake()->numberBetween(0, 3) : fake()->numberBetween(10, 60),
                'low_stock_threshold' => 5,
            ]);
        }
    }

    /**
     * @param  list<User>  $technicians
     */
    protected function seedRepairs(array $technicians): void
    {
        if (Repair::count() > 0 || $technicians === []) {
            return;
        }

        $jobs = app(RepairJobNumberGenerator::class);
        $creator = User::first() ?? $technicians[0];
        $devices = Device::with('customer')->get();

        if ($devices->isEmpty()) {
            return;
        }

        $pickDevice = fn () => $devices->random();

        // Active repairs spread over the last 14 days, mixed categories.
        $activeStatuses = [RepairStatus::Received, RepairStatus::Diagnosing, RepairStatus::InRepair, RepairStatus::Approved];

        for ($i = 0; $i < 10; $i++) {
            $device = $pickDevice();
            $createdAt = fake()->dateTimeBetween('-14 days', 'now');

            $repair = Repair::create([
                'job_number' => $jobs->next(),
                'customer_id' => $device->customer_id,
                'device_id' => $device->id,
                'assigned_technician_id' => $technicians[$i % count($technicians)]->id,
                'status' => $activeStatuses[$i % count($activeStatuses)]->value,
                'priority' => fake()->randomElement(['low', 'normal', 'high']),
                'reported_problem' => fake()->sentence(),
                'estimate_amount' => fake()->randomFloat(2, 100, 5000),
                'warranty_days' => 30,
                'created_by' => $creator->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            RepairStatusHistory::create([
                'repair_id' => $repair->id,
                'status' => RepairStatus::Received->value,
                'changed_by' => $creator->id,
                'created_at' => $createdAt,
            ]);
        }

        // Collected repairs: 6 in the last 7 days, 6 in the 7 before —
        // drives revenue totals, trends, turnaround and efficiency.
        for ($i = 0; $i < 12; $i++) {
            $device = $pickDevice();
            $collectedAt = fake()->dateTimeBetween($i < 6 ? '-6 days' : '-13 days', $i < 6 ? 'now' : '-7 days');
            $createdAt = (clone $collectedAt)->modify('-'.fake()->numberBetween(1, 3).' days');
            $technician = $technicians[$i % count($technicians)];

            $repair = Repair::create([
                'job_number' => $jobs->next(),
                'customer_id' => $device->customer_id,
                'device_id' => $device->id,
                'assigned_technician_id' => $technician->id,
                'status' => RepairStatus::Collected->value,
                'priority' => fake()->randomElement(['low', 'normal', 'high']),
                'reported_problem' => fake()->sentence(),
                'estimate_amount' => fake()->randomFloat(2, 100, 5000),
                'final_cost' => fake()->randomFloat(2, 500, 8000),
                'collected_at' => $collectedAt,
                'warranty_days' => 30,
                'created_by' => $creator->id,
                'created_at' => $createdAt,
                'updated_at' => $collectedAt,
            ]);

            // Two of the twelve went on hold with extra diagnoses, so the
            // efficiency metric reads realistically below 100%.
            $troubled = $i < 2;

            foreach ([RepairStatus::Received, RepairStatus::InRepair, RepairStatus::Collected] as $status) {
                RepairStatusHistory::create([
                    'repair_id' => $repair->id,
                    'status' => $status->value,
                    'changed_by' => $technician->id,
                    'created_at' => $createdAt,
                ]);
            }

            if ($troubled) {
                RepairStatusHistory::create([
                    'repair_id' => $repair->id,
                    'status' => RepairStatus::OnHold->value,
                    'changed_by' => $technician->id,
                    'note' => 'Waiting for parts',
                    'created_at' => $createdAt,
                ]);

                RepairDiagnosis::create([
                    'repair_id' => $repair->id,
                    'diagnosed_by' => $technician->id,
                    'findings' => 'Initial fault confirmed',
                    'recommended_action' => 'Replace component',
                ]);
                RepairDiagnosis::create([
                    'repair_id' => $repair->id,
                    'diagnosed_by' => $technician->id,
                    'findings' => 'Secondary fault found on retest',
                    'recommended_action' => 'Replace secondary component',
                ]);
            } else {
                RepairDiagnosis::create([
                    'repair_id' => $repair->id,
                    'diagnosed_by' => $technician->id,
                    'findings' => 'Fault confirmed',
                    'recommended_action' => 'Replace component',
                ]);
            }
        }

        // Terminal repairs prove exclusion from active counts.
        foreach ([RepairStatus::Unrepairable, RepairStatus::Cancelled] as $status) {
            $device = $pickDevice();

            $repair = Repair::create([
                'job_number' => $jobs->next(),
                'customer_id' => $device->customer_id,
                'device_id' => $device->id,
                'status' => $status->value,
                'priority' => 'normal',
                'reported_problem' => fake()->sentence(),
                'warranty_days' => 0,
                'created_by' => $creator->id,
            ]);

            RepairStatusHistory::create([
                'repair_id' => $repair->id,
                'status' => $status->value,
                'changed_by' => $creator->id,
            ]);
        }
    }
}
