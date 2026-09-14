<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Devices\DeviceStoreRequest;
use App\Http\Requests\Api\V1\Devices\DeviceUpdateRequest;
use App\Http\Resources\Api\V1\DeviceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Device::with('customer');

        $search = $request->query('search');

        if (is_string($search) && $search !== '') {
            $query->where(fn ($q) => $q->where('brand', 'like', "%{$search}%")->orWhere('model', 'like', "%{$search}%")->orWhere('serial_number', 'like', "%{$search}%")->orWhere('imei', 'like', "%{$search}%"));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        if (is_string($request->query('category')) && $request->query('category') !== '') {
            $query->where('category', $request->query('category'));
        }

        $devices = $query->latest('id')
            ->paginate(15)
            ->through(fn (Device $device) => DeviceResource::make($device)->resolve($request));

        return ApiResponse::ok($devices);
    }

    public function store(DeviceStoreRequest $request): JsonResponse
    {
        $device = Device::create($request->validated());
        $device->load('customer');

        return ApiResponse::created([
            'device' => new DeviceResource($device),
        ], 'Device created.');
    }

    public function show(int $device): JsonResponse
    {
        $record = Device::with('customer')->findOrFail($device);

        return ApiResponse::ok([
            'device' => new DeviceResource($record),
        ]);
    }

    public function update(DeviceUpdateRequest $request, int $device): JsonResponse
    {
        $record = Device::findOrFail($device);
        $record->update($request->validated());
        $record->load('customer');

        return ApiResponse::ok([
            'device' => new DeviceResource($record),
        ], 'Device updated.');
    }

    /**
     * Soft-delete; repair-link guard lands with Phase 3 repairs.
     */
    public function destroy(int $device): JsonResponse
    {
        $record = Device::findOrFail($device);
        $record->delete();

        return ApiResponse::ok(null, 'Device deleted.');
    }

    /**
     * Scoped repairs view — stubbed until Phase 3.
     */
    public function repairs(int $device): JsonResponse
    {
        Device::findOrFail($device);

        return ApiResponse::ok([]);
    }
}
