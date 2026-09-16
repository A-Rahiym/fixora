<?php

namespace App\Enums;

/**
 * Stock movement types (database-design §3.13, backend-plan Phase 4).
 *
 * Stored as VARCHAR and validated at the FormRequest/service layer so
 * sqlite test runs stay portable.
 */
enum InventoryMovementType: string
{
    case PurchaseIn = 'purchase_in';
    case RepairUsage = 'repair_usage';
    case SaleUsage = 'sale_usage';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Types the manual adjust endpoint may record. The repair, purchase,
     * and sale flows write their own types via InventoryMovementService.
     *
     * @return list<string>
     */
    public static function manualValues(): array
    {
        return [self::AdjustmentIn->value, self::AdjustmentOut->value];
    }
}
