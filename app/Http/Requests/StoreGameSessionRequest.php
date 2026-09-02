<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGameSessionRequest extends FormRequest
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
            'session_code' => ['nullable', 'string', 'max:50', 'unique:game_sessions,session_code'],
            'team1_name' => ['nullable', 'string', 'max:100'],
            'team2_name' => ['nullable', 'string', 'max:100'],
            'timer_duration_seconds' => ['nullable', 'integer', 'min:5', 'max:300'],
        ];
    }
}
