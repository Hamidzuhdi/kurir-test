<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CourierStatus;
use App\Enums\VehicleType;
use App\Models\Courier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourierRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/', Rule::unique(Courier::class)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique(Courier::class)],
            'level' => ['required', 'integer', 'between:'.Courier::MIN_LEVEL.','.Courier::MAX_LEVEL],
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'vehicle_plate_number' => ['nullable', 'string', 'max:15'],
            'status' => ['sometimes', Rule::enum(CourierStatus::class)],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
