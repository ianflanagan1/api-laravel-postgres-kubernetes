<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AuthRegisterRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'max:255', 'email'], // unique
            'password' => [
                'required', 'string', 'min:8', 'max:255', 'confirmed',
                'not_regex:/^\s/',      // No leading whitespace
                'not_regex:/\s$/',      // No trailing whitespace
                Password::default()->letters()->mixedCase()->numbers()->symbols(),  // Align with message() for ApiErrorCode::VALIDATION_PASSWORD
            ],
        ];
    }
}
