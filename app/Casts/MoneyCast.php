<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Shared money cast (Phase 0 convention).
 *
 * Use on every monetary attribute: `'final_cost' => MoneyCast::class`.
 * Keeps DECIMAL/NUMERIC values as precise decimal strings instead of
 * lossy floats. Phase 6 payments/receipts will rely on this.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class MoneyCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?string
    {
        return $value === null ? null : number_format((float) $value, 2, '.', '');
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        return $value === null ? null : number_format((float) $value, 2, '.', '');
    }
}
