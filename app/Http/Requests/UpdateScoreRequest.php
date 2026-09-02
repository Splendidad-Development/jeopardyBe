<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScoreRequest extends FormRequest
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
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'score' => ['nullable', 'integer'],
            'team1_score' => ['nullable', 'integer'],
            'team2_score' => ['nullable', 'integer'],
            'team_scores' => ['nullable', 'array'],
            'team_scores.*' => ['integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
