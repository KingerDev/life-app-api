<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'due_date'    => 'nullable|date_format:Y-m-d',
            'priority'    => 'nullable|string|in:none,low,medium,high',
            'list_id'     => 'nullable|string|exists:todo_lists,id',
            'aspect_id'   => 'nullable|string|in:physical_health,mental_health,family_friends,romantic_life,career,finances,personal_growth,purpose,other',
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
