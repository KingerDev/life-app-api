<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'   => 'nullable|string|max:200',
            'content' => 'nullable|string',
            'tags'    => 'nullable|array|max:10',
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
