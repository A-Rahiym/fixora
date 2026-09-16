<?php

namespace App\Http\Requests\Api\V1\Inventory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InventoryUpdateRequest extends FormRequest
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
        $item = $this->route('item');

        return [
            'category_id' => ['sometimes', 'integer', 'exists:inventory_categories,id'],
            'sku' => ['sometimes', 'string', 'max:50', 'unique:inventory_items,sku,'.$item],
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'unit_cost' => ['sometimes', 'numeric', 'min:0'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'preferred_supplier_id' => ['nullable', 'integer'],
        ];
    }
}
