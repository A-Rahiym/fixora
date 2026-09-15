<?php

namespace App\Http\Requests\Api\V1\Repairs;

use App\Enums\RepairPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RepairStoreRequest extends FormRequest
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
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'device_id' => ['required', 'integer', 'exists:devices,id'],
            'assigned_technician_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['sometimes', 'string', 'in:'.implode(',', RepairPriority::values())],
            'reported_problem' => ['required', 'string'],
            'intake_condition' => ['nullable', 'array'],
            'estimate_amount' => ['nullable', 'numeric', 'min:0'],
            'final_cost' => ['nullable', 'numeric', 'min:0'],
            'expected_completion_at' => ['nullable', 'date'],
            'warranty_days' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
