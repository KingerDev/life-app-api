<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBeliefEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'domain' => 'required|string|in:career,relationships,health,creativity,learning,money,confidence,impact',

            // Predefined beliefs (required if not custom)
            'limiting_belief_id' => 'nullable|string|max:50|required_without:limiting_belief_custom',
            'liberating_belief_id' => 'nullable|string|max:50|required_without:liberating_belief_custom',

            // Custom beliefs (required if not using predefined)
            'limiting_belief_custom' => 'nullable|string|max:500|required_without:limiting_belief_id',
            'liberating_belief_custom' => 'nullable|string|max:500|required_without:liberating_belief_id',

            // Flag for custom beliefs
            'is_custom' => 'nullable|boolean',

            'planned_action' => 'required|string|max:2000',
            'suggestion_source' => 'nullable|string|in:wheel_of_life,quest,manual',
            'related_aspect_id' => 'nullable|string|max:50',
            'related_quest_id' => 'nullable|uuid',
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
