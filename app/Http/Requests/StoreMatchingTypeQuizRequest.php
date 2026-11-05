<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMatchingTypeQuizRequest extends FormRequest
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

            'choices' => ['required', 'array', 'min:2', 'max:26'],
            'choices.*.choice_letter' => ['required', 'string', 'max:10'],
            'choices.*.choice_text' => ['required', 'string', 'max:500'],

            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.item_number' => ['nullable', 'integer'],
            'items.*.description' => ['required', 'string', 'max:2000'],
            'items.*.correct_choice_letter' => ['required', 'string', 'max:10'],
            'items.*.hint' => ['nullable', 'string', 'max:1000'],
            'items.*.explanation' => ['nullable', 'string', 'max:1000'],
            'items.*.points' => ['required', 'integer', 'min:1', 'max:1000'],
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
            'choices.required' => 'At least two choices in Column B are required.',
            'choices.min' => 'At least two choices in Column B are required.',
            'choices.*.choice_text.required' => 'Choice text cannot be blank.',
            'items.required' => 'At least one description item in Column A is required.',
            'items.min' => 'At least one description item in Column A is required.',
            'items.*.description.required' => 'Please enter the description prompt for this item.',
            'items.*.correct_choice_letter.required' => 'Please select the matching choice from Column B.',
        ];
    }
}
