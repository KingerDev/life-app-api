<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateQuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quarter' => 'sometimes|integer|min:1|max:4',
            'year' => 'sometimes|integer|min:2024|max:2100',
            'type' => 'sometimes|string|in:work,life',
            'discovery_answers' => 'sometimes|nullable|array',
            'discovery_answers.question1' => 'nullable|string|max:2000',
            'discovery_answers.question2' => 'nullable|string|max:2000',
            'discovery_answers.question3' => 'nullable|string|max:2000',
            'discovery_answers.question4' => 'nullable|string|max:2000',
            'discovery_answers.question5' => 'nullable|string|max:2000',
            'main_goal' => 'sometimes|string|max:2000',
            'why_important' => 'sometimes|string|max:2000',
            'success_criteria' => 'sometimes|string|max:2000',
            'excitement' => 'sometimes|string|max:2000',
            'commitment' => 'sometimes|string|max:2000',
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
