<?php

namespace App\Http\Resources\Api\V1;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryItem
 */
class InventoryItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'unit_cost' => $this->unit_cost,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_low_stock' => $this->isLowStock(),
            'preferred_supplier_id' => $this->preferred_supplier_id,
            'category' => new InventoryCategoryResource($this->whenLoaded('category')),
            'movements' => InventoryMovementResource::collection($this->whenLoaded('movements')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
