<?php

namespace App\Http\Requests;

use App\Enums\ConflictType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreColorConflictQuizRequest extends FormRequest
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
            'contexts' => ['nullable', 'array'],
            'contexts.*.scenario' => ['required_with:contexts', 'string'],
            'contexts.*.hint' => ['nullable', 'string', 'max:1000'],
            'xp_reward' => ['required', 'integer', 'min:10', 'max:50000'],
            'passing_percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:10', 'max:7200'],
            'is_published' => ['nullable', 'boolean'],

            'cards' => ['required', 'array', 'min:1', 'max:20'],
            'cards.*.excerpt' => ['required', 'string', 'max:3000'],
            'cards.*.conflict_type' => ['required', Rule::enum(ConflictType::class)],
            'cards.*.hint' => ['nullable', 'string', 'max:1000'],
            'cards.*.explanation' => ['nullable', 'string', 'max:1000'],
            'cards.*.feedback_incorrect' => ['nullable', 'string', 'max:1000'],
            'cards.*.points' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Custom message for cards validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cards.required' => 'At least one conflict classification card is required.',
            'cards.min' => 'At least one conflict classification card is required.',
            'cards.*.excerpt.required' => 'Please enter an excerpt for this card.',
            'cards.*.conflict_type.required' => 'Please select the correct conflict category.',
        ];
    }
}
