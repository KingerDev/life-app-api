<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'   => 'sometimes|nullable|string|max:200',
            'content' => 'sometimes|nullable|string',
            'tags'    => 'sometimes|nullable|array|max:10',
            'tags.*'  => 'string|max:50',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'error'  => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
