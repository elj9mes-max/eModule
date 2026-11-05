<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationQuizRequest extends FormRequest
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

            'word_helpers' => ['nullable', 'array'],
            'word_helpers.*' => ['required', 'string', 'max:100'],

            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.section_title' => ['required', 'string', 'max:255'],
            'items.*.conflict_type' => ['nullable', 'string', 'max:100'],
            'items.*.question_prompt' => ['required', 'string', 'max:2000'],
            'items.*.step1_prompt' => ['nullable', 'string', 'max:255'],
            'items.*.sentence_template' => ['required', 'string', 'max:2000'],
            'items.*.hint' => ['nullable', 'string', 'max:1000'],
            'items.*.points' => ['required', 'integer', 'min:1', 'max:1000'],

            'items.*.options' => ['required', 'array', 'min:2', 'max:10'],
            'items.*.options.*.option_text' => ['required', 'string', 'max:255'],
            'items.*.options.*.stance_value' => ['nullable', 'string', 'max:100'],
            'items.*.options.*.is_correct' => ['nullable', 'boolean'],
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
            'items.required' => 'At least one evaluation question item is required.',
            'items.min' => 'At least one evaluation question item is required.',
            'items.*.question_prompt.required' => 'Please enter the critical thinking question prompt.',
            'items.*.sentence_template.required' => 'Please provide a sentence starter template with at least one blank [___].',
            'items.*.options.min' => 'Each item requires at least 2 choice buttons for Step 1.',
        ];
    }
}
