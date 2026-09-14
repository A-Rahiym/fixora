<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customers\CustomerStoreRequest;
use App\Http\Requests\Api\V1\Customers\CustomerUpdateRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Http\Resources\Api\V1\DeviceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Services\CustomerDedupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::withCount('devices');

        $search = $request->query('search');

        if (is_string($search) && $search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        $customers = $query->latest('id')
            ->paginate(15)
            ->through(fn (Customer $customer) => CustomerResource::make($customer)->resolve($request));

        return ApiResponse::ok($customers);
    }

    public function store(CustomerStoreRequest $request, CustomerDedupService $dedup): JsonResponse
    {
        $duplicate = $dedup->findDuplicate($request->validated()['phone']);

        if ($duplicate !== null) {
            return ApiResponse::error(
                'A customer with this phone number may already exist.',
                409,
                ['customer' => new CustomerResource($duplicate)],
            );
        }

        $customer = Customer::create($request->validated());
        $customer->loadCount('devices');

        return ApiResponse::created([
            'customer' => new CustomerResource($customer),
        ], 'Customer created.');
    }

    public function show(int $customer): JsonResponse
    {
        $record = Customer::with('devices')->withCount('devices')->findOrFail($customer);

        return ApiResponse::ok([
            'customer' => new CustomerResource($record),
        ]);
    }

    public function update(CustomerUpdateRequest $request, int $customer, CustomerDedupService $dedup): JsonResponse
    {
        $record = Customer::findOrFail($customer);
        $validated = $request->validated();

        if (array_key_exists('phone', $validated)) {
            $duplicate = $dedup->findDuplicate($validated['phone'], $record->id);

            if ($duplicate !== null) {
                return ApiResponse::error(
                    'A customer with this phone number may already exist.',
                    409,
                    ['customer' => new CustomerResource($duplicate)],
                );
            }
        }

        $record->update($validated);
        $record->loadCount('devices');

        return ApiResponse::ok([
            'customer' => new CustomerResource($record),
        ], 'Customer updated.');
    }

    /**
     * Soft-delete; rejected when devices still reference the customer.
     */
    public function destroy(int $customer): JsonResponse
    {
        $record = Customer::findOrFail($customer);

        if ($record->devices()->exists()) {
            return ApiResponse::error('Customer cannot be deleted while devices exist.', 422);
        }

        $record->delete();

        return ApiResponse::ok(null, 'Customer deleted.');
    }

    public function devices(int $customer, Request $request): JsonResponse
    {
        $record = Customer::findOrFail($customer);

        $devices = $record->devices()->with('customer')->latest('id')
            ->paginate(15)
            ->through(fn ($device) => DeviceResource::make($device)->resolve($request));

        return ApiResponse::ok($devices);
    }
}
