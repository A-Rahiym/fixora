<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RepairStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Repairs\RepairAssignRequest;
use App\Http\Requests\Api\V1\Repairs\RepairDiagnosisRequest;
use App\Http\Requests\Api\V1\Repairs\RepairNoteRequest;
use App\Http\Requests\Api\V1\Repairs\RepairStatusRequest;
use App\Http\Requests\Api\V1\Repairs\RepairStoreRequest;
use App\Http\Requests\Api\V1\Repairs\RepairUpdateRequest;
use App\Http\Resources\Api\V1\RepairResource;
use App\Http\Responses\ApiResponse;
use App\Models\Device;
use App\Models\Repair;
use App\Services\RepairJobNumberGenerator;
use App\Services\RepairStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Repair::with(['customer', 'device', 'technician']);

        $search = $request->query('search');

        if (is_string($search) && $search !== '') {
            $query->where(fn ($q) => $q->where('job_number', 'like', "%{$search}%")->orWhere('reported_problem', 'like', "%{$search}%"));
        }

        if (is_string($request->query('status')) && $request->query('status') !== '') {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('assigned_technician_id')) {
            $query->where('assigned_technician_id', $request->integer('assigned_technician_id'));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        if ($request->has('device_id')) {
            $query->where('device_id', $request->integer('device_id'));
        }

        $repairs = $query->latest('id')
            ->paginate(15)
            ->through(fn (Repair $repair) => RepairResource::make($repair)->resolve($request));

        return ApiResponse::ok($repairs);
    }

    public function store(RepairStoreRequest $request, RepairJobNumberGenerator $jobNumbers): JsonResponse
    {
        $validated = $request->validated();

        $device = Device::findOrFail($validated['device_id']);

        if ((int) $device->customer_id !== (int) $validated['customer_id']) {
            return ApiResponse::error('Device does not belong to the given customer.', 422);
        }

        $repair = DB::transaction(function () use ($validated, $request, $jobNumbers) {
            $record = Repair::create([
                ...$validated,
                'job_number' => $jobNumbers->next(),
                'status' => RepairStatus::Received->value,
                'created_by' => $request->user()->id,
            ]);

            $record->history()->create([
                'status' => RepairStatus::Received->value,
                'changed_by' => $request->user()->id,
            ]);

            return $record;
        });

        $repair->load(['customer', 'device', 'technician', 'history']);

        return ApiResponse::created([
            'repair' => new RepairResource($repair),
        ], 'Repair created.');
    }

    public function show(int $repair): JsonResponse
    {
        $record = Repair::with(['customer', 'device', 'technician', 'history.changedBy', 'diagnoses.diagnosedBy', 'notes.author'])->findOrFail($repair);

        return ApiResponse::ok([
            'repair' => new RepairResource($record),
        ]);
    }

    public function update(RepairUpdateRequest $request, int $repair): JsonResponse
    {
        $record = Repair::findOrFail($repair);
        $record->update($request->validated());
        $record->load(['customer', 'device', 'technician']);

        return ApiResponse::ok([
            'repair' => new RepairResource($record),
        ], 'Repair updated.');
    }

    public function destroy(int $repair): JsonResponse
    {
        $record = Repair::findOrFail($repair);
        $record->delete();

        return ApiResponse::ok(null, 'Repair deleted.');
    }

    public function assign(RepairAssignRequest $request, int $repair, RepairStatusService $statuses): JsonResponse
    {
        $record = Repair::findOrFail($repair);
        $record->update(['assigned_technician_id' => $request->validated()['assigned_technician_id']]);
        $record->load(['customer', 'device', 'technician']);

        return ApiResponse::ok([
            'repair' => new RepairResource($record),
        ], 'Technician assigned.');
    }

    public function diagnosis(RepairDiagnosisRequest $request, int $repair): JsonResponse
    {
        $record = Repair::findOrFail($repair);

        $diagnosis = $record->diagnoses()->create([
            ...$request->validated(),
            'diagnosed_by' => $request->user()->id,
        ]);

        $diagnosis->load('diagnosedBy');

        $record->load(['customer', 'device', 'technician', 'diagnoses.diagnosedBy']);

        return ApiResponse::created([
            'repair' => new RepairResource($record),
        ], 'Diagnosis recorded.');
    }

    public function notes(RepairNoteRequest $request, int $repair): JsonResponse
    {
        $record = Repair::findOrFail($repair);

        $record->notes()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $record->load(['customer', 'device', 'technician', 'notes.author']);

        return ApiResponse::created([
            'repair' => new RepairResource($record),
        ], 'Note added.');
    }

    public function approve(Request $request, int $repair, RepairStatusService $statuses): JsonResponse
    {
        $record = Repair::findOrFail($repair);
        $record->update(['approved_at' => now()]);

        $record = $statuses->transition($record, RepairStatus::Approved, $request->user());
        $record->load(['customer', 'device', 'technician', 'history']);

        return ApiResponse::ok([
            'repair' => new RepairResource($record),
        ], 'Estimate approved.');
    }

    public function status(RepairStatusRequest $request, int $repair, RepairStatusService $statuses): JsonResponse
    {
        $record = Repair::findOrFail($repair);

        $record = $statuses->transition(
            $record,
            RepairStatus::from($request->validated()['status']),
            $request->user(),
            $request->validated()['note'] ?? null,
        );

        $record->load(['customer', 'device', 'technician', 'history']);

        return ApiResponse::ok([
            'repair' => new RepairResource($record),
        ], 'Status updated.');
    }

    public function complete(Request $request, int $repair, RepairStatusService $statuses): JsonResponse
    {
        $record = Repair::findOrFail($repair);

        $record = $statuses->transition($record, RepairStatus::ReadyForCollection, $request->user());
        $record->load(['customer', 'device', 'technician', 'history']);

        return ApiResponse::ok([
            'repair' => new RepairResource($record),
        ], 'Repair marked ready for collection.');
    }

    public function collect(Request $request, int $repair, RepairStatusService $statuses): JsonResponse
    {
        $record = Repair::findOrFail($repair);

        $record = $statuses->transition($record, RepairStatus::Collected, $request->user());

        $record->update([
            'collected_at' => now(),
            'warranty_expires_at' => $record->warranty_days > 0
                ? now()->addDays($record->warranty_days)->toDateString()
                : null,
        ]);

        $record->load(['customer', 'device', 'technician', 'history']);

        return ApiResponse::ok([
            'repair' => new RepairResource($record),
        ], 'Repair collected.');
    }
}
