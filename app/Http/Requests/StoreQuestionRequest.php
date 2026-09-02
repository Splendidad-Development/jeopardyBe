<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
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
            'section_id' => ['sometimes', 'required', 'integer', 'exists:sections,id'],
            'question_text' => ['required', 'string'],
            'answer' => ['nullable', 'string'],
            'points' => ['nullable', 'integer', 'min:0'],
            'order' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
