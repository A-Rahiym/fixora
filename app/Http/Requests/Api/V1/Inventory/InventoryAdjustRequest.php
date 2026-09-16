<?php

namespace App\Http\Requests\Api\V1\Inventory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InventoryAdjustRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Movement type is derived from the sign (positive in,
            // negative out) so callers cannot send a contradictory pair.
            'quantity_change' => ['required', 'integer', 'not_in:0'],
            'note' => ['nullable', 'string'],
        ];
    }
}
