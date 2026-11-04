<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLectureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'body' => ['nullable', 'string'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'xp_reward' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
