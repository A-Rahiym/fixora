<?php

namespace App\Http\Requests\Api\V1\Devices;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeviceStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'category' => ['required', 'string', 'in:phone,laptop,console,tv,other'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:150'],
            'imei' => ['nullable', 'string', 'max:20'],
            'color' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
