<?php

use App\Models\Customer;
use App\Models\Device;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function apiToken(string $role = 'owner'): string
{
    $roleModel = Role::where('name', $role)->firstOrFail();
    $user = User::factory()->create(['role_id' => $roleModel->id]);

    return $user->createToken('api')->plainTextToken;
}

function repairPayload(Customer $customer, Device $device): array
{
    return [
        'customer_id' => $customer->id,
        'device_id' => $device->id,
        'priority' => 'high',
        'reported_problem' => 'Screen flickers on boot',
        'estimate_amount' => '120.50',
        'warranty_days' => 30,
    ];
}
