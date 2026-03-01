<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreQuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quarter' => 'required|integer|min:1|max:4',
            'year' => 'required|integer|min:2024|max:2100',
            'type' => 'required|string|in:work,life',
            'discovery_answers' => 'nullable|array',
            'discovery_answers.question1' => 'nullable|string|max:2000',
            'discovery_answers.question2' => 'nullable|string|max:2000',
            'discovery_answers.question3' => 'nullable|string|max:2000',
            'discovery_answers.question4' => 'nullable|string|max:2000',
            'discovery_answers.question5' => 'nullable|string|max:2000',
            'main_goal' => 'required|string|max:2000',
            'why_important' => 'required|string|max:2000',
            'success_criteria' => 'required|string|max:2000',
            'excitement' => 'required|string|max:2000',
            'commitment' => 'required|string|max:2000',
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
