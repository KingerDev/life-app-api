<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week_start' => 'sometimes|date',
            'week_end' => 'sometimes|date|after:week_start',
            'ratings' => 'sometimes|array|size:8',
            'ratings.*.aspectId' => 'required_with:ratings|string|in:physical_health,mental_health,family_friends,romantic_life,career,finances,personal_growth,purpose',
            'ratings.*.value' => 'required_with:ratings|integer|min:0|max:10',
            'notes' => 'nullable|string|max:1000',
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
