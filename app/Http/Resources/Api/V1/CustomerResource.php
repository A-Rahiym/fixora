<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Customer
 */
class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'whatsapp_phone' => $this->whatsapp_phone,
            'email' => $this->email,
            'address' => $this->address,
            'notes' => $this->notes,
            'devices_count' => $this->whenCounted('devices_count'),
            'devices' => DeviceResource::collection($this->whenLoaded('devices')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
