<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single writer for stock changes (backend-plan Phase 4).
 *
 * Every quantity change appends one immutable `inventory_movements` row
 * and updates `inventory_items.quantity` atomically. Controllers never
 * write quantity directly. Phase 5 (repair parts) and Phase 7
 * (purchases) reuse this service with their own movement types.
 */
class InventoryMovementService
{
    public function record(
        InventoryItem $item,
        InventoryMovementType $type,
        int $quantityChange,
        User $user,
        string $referenceType = 'manual',
        ?int $referenceId = null,
        ?string $note = null,
    ): InventoryMovement {
        if ($quantityChange === 0) {
            throw ValidationException::withMessages([
                'quantity_change' => 'Quantity change must not be zero.',
            ]);
        }

        return DB::transaction(function () use ($item, $type, $quantityChange, $user, $referenceType, $referenceId, $note) {
            $locked = InventoryItem::where('id', $item->id)->lockForUpdate()->firstOrFail();

            $newQuantity = $locked->quantity + $quantityChange;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity_change' => 'Insufficient stock for this movement.',
                ]);
            }

            $movement = $locked->movements()->create([
                'type' => $type->value,
                'quantity_change' => $quantityChange,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $note,
                'created_by' => $user->id,
            ]);

            $locked->update(['quantity' => $newQuantity]);

            return $movement->load('item');
        });
    }
}
