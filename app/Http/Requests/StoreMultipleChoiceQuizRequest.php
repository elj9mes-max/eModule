<?php

namespace App\Http\Requests;

use App\Enums\QuestionSelectionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMultipleChoiceQuizRequest extends FormRequest
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
            'reading_passage' => ['nullable', 'string'],
            'scenario' => ['nullable', 'string'],
            'context_hint' => ['nullable', 'string', 'max:1000'],
            'hint' => ['nullable', 'string', 'max:1000'],
            'contexts' => ['nullable', 'array'],
            'contexts.*.scenario' => ['required_with:contexts', 'string'],
            'contexts.*.hint' => ['nullable', 'string', 'max:1000'],
            'xp_reward' => ['required', 'integer', 'min:10', 'max:50000'],
            'passing_percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:10', 'max:7200'],
            'is_published' => ['nullable', 'boolean'],

            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string', 'max:1000'],
            'questions.*.selection_type' => ['required', Rule::enum(QuestionSelectionType::class)],
            'questions.*.passage_context' => ['nullable', 'string', 'max:1000'],
            'questions.*.hint' => ['nullable', 'string', 'max:500'],
            'questions.*.explanation' => ['nullable', 'string', 'max:1000'],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:1000'],

            'questions.*.options' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.options.*.option_text' => ['required', 'string', 'max:500'],
            'questions.*.options.*.is_correct' => ['required', 'boolean'],
            'questions.*.options.*.feedback' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom message for options and questions validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'questions.required' => 'At least one multiple choice question is required.',
            'questions.min' => 'At least one multiple choice question is required.',
            'questions.*.question_text.required' => 'Please enter a question prompt.',
            'questions.*.options.min' => 'Each question must have at least 2 choices.',
            'questions.*.options.*.option_text.required' => 'Option text cannot be empty.',
        ];
    }
}
