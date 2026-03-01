<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week_start' => 'required|date',
            'week_end' => 'required|date|after:week_start',
            'ratings' => 'required|array|size:8',
            'ratings.*.aspectId' => 'required|string|in:physical_health,mental_health,family_friends,romantic_life,career,finances,personal_growth,purpose',
            'ratings.*.value' => 'required|integer|min:0|max:10',
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
