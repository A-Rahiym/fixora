<?php

namespace App\Http\Requests\Api\V1\Repairs;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RepairDiagnosisRequest extends FormRequest
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
            'findings' => ['required', 'string'],
            'recommended_action' => ['nullable', 'string'],
        ];
    }
}
