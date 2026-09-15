<?php

namespace App\Http\Requests\Api\V1\Repairs;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RepairAssignRequest extends FormRequest
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
            'assigned_technician_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
