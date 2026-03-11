<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateHabitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:80',
            'description' => 'nullable|string|max:280',
            'aspect_id' => 'sometimes|string|in:physical_health,mental_health,family_friends,romantic_life,career,finances,personal_growth,purpose,other',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'error' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
