<?php

namespace App\Http\Requests\Api\V1\Repairs;

use App\Enums\RepairPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RepairUpdateRequest extends FormRequest
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
            'priority' => ['sometimes', 'string', 'in:'.implode(',', RepairPriority::values())],
            'reported_problem' => ['sometimes', 'string'],
            'intake_condition' => ['nullable', 'array'],
            'estimate_amount' => ['nullable', 'numeric', 'min:0'],
            'final_cost' => ['nullable', 'numeric', 'min:0'],
            'expected_completion_at' => ['nullable', 'date'],
            'warranty_days' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
