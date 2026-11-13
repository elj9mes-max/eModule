<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinModuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'join_code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ];
    }

    /**
     * Custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'join_code.required' => 'Please enter a 6-digit Quest Code.',
            'join_code.size' => 'The Quest Code must be exactly 6 numbers.',
            'join_code.regex' => 'The Quest Code must contain numbers only.',
        ];
    }
}
