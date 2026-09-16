<?php

namespace App\Http\Resources\Api\V1;

use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryMovement
 */
class InventoryMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_item_id' => $this->inventory_item_id,
            'type' => $this->type,
            'quantity_change' => $this->quantity_change,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'item' => new InventoryItemResource($this->whenLoaded('item')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
        ];
    }
}
