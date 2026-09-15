<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Repair;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Repair
 */
class RepairResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_number' => $this->job_number,
            'customer_id' => $this->customer_id,
            'device_id' => $this->device_id,
            'assigned_technician_id' => $this->assigned_technician_id,
            'status' => $this->status,
            'priority' => $this->priority,
            'reported_problem' => $this->reported_problem,
            'intake_condition' => $this->intake_condition,
            'estimate_amount' => $this->estimate_amount,
            'final_cost' => $this->final_cost,
            'expected_completion_at' => $this->expected_completion_at,
            'approved_at' => $this->approved_at,
            'collected_at' => $this->collected_at,
            'warranty_days' => $this->warranty_days,
            'warranty_expires_at' => $this->warranty_expires_at,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'device' => new DeviceResource($this->whenLoaded('device')),
            'technician' => new UserResource($this->whenLoaded('technician')),
            'history' => RepairStatusHistoryResource::collection($this->whenLoaded('history')),
            'diagnoses' => RepairDiagnosisResource::collection($this->whenLoaded('diagnoses')),
            'notes' => RepairNoteResource::collection($this->whenLoaded('notes')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
