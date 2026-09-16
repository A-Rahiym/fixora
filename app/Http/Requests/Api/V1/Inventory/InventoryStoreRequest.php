<?php

namespace App\Http\Requests\Api\V1\Inventory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InventoryStoreRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:inventory_categories,id'],
            'sku' => ['required', 'string', 'max:50', 'unique:inventory_items,sku'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            // Opening stock is recorded through InventoryMovementService,
            // never written to quantity directly.
            'opening_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'preferred_supplier_id' => ['nullable', 'integer'],
        ];
    }
}
