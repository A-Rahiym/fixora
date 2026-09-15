<?php

namespace App\Http\Resources\Api\V1;

use App\Models\RepairDiagnosis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RepairDiagnosis
 */
class RepairDiagnosisResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'findings' => $this->findings,
            'recommended_action' => $this->recommended_action,
            'diagnosed_by' => new UserResource($this->whenLoaded('diagnosedBy')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
