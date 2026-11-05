<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReflectionQuizRequest extends FormRequest
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
            'xp_reward' => ['required', 'integer', 'min:10', 'max:50000'],
            'passing_percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:10', 'max:7200'],
            'is_published' => ['nullable', 'boolean'],

            'contexts' => ['nullable', 'array'],
            'contexts.*.scenario' => ['required_with:contexts', 'string'],
            'contexts.*.hint' => ['nullable', 'string', 'max:1000'],

            'prompts' => ['required', 'array', 'min:1', 'max:20'],
            'prompts.*.prompt_text' => ['required', 'string', 'max:3000'],
            'prompts.*.max_words' => ['required', 'integer', 'min:10', 'max:5000'],
            'prompts.*.min_words' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'prompts.*.guiding_hint' => ['nullable', 'string', 'max:1000'],
            'prompts.*.starter_sentence' => ['nullable', 'string', 'max:1000'],
            'prompts.*.points' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prompts.required' => 'At least one reflection prompt is required.',
            'prompts.min' => 'At least one reflection prompt is required.',
            'prompts.*.prompt_text.required' => 'Please enter the reflection instruction or guiding question prompt.',
            'prompts.*.max_words.required' => 'Please set a maximum word limit for the reflection.',
            'prompts.*.points.required' => 'Points for each prompt are required.',
        ];
    }
}
