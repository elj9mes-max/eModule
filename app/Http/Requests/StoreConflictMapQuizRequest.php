<?php

namespace App\Http\Requests;

use App\Enums\ConflictType;
use App\Models\Module;
use App\Services\ConflictMapImageService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConflictMapQuizRequest extends FormRequest
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
            'quiz_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'xp_reward' => ['required', 'integer', 'min:10', 'max:50000'],
            'stars_per_correct' => ['nullable', 'integer', 'min:0', 'max:100'],
            'gems_per_correct' => ['nullable', 'integer', 'min:0', 'max:500'],
            'passing_percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:10', 'max:7200'],
            'is_published' => ['nullable', 'boolean'],

            'contexts' => ['nullable', 'array'],
            'contexts.*.scenario' => ['required_with:contexts', 'string'],
            'contexts.*.hint' => ['nullable', 'string', 'max:1000'],

            'zones' => ['required', 'array', 'min:2', 'max:6'],
            'zones.*.conflict_type' => ['required', Rule::enum(ConflictType::class)],
            'zones.*.label' => ['required', 'string', 'max:255'],
            'zones.*.subtitle' => ['nullable', 'string', 'max:500'],
            'zones.*.color_theme' => ['nullable', 'string', 'max:50'],

            'events' => ['required', 'array', 'min:1', 'max:30'],
            'events.*.card_letter' => ['nullable', 'string', 'max:20'],
            'events.*.event_text' => ['required_without:events.*.image_path', 'nullable', 'string', 'max:2000'],
            'events.*.image_path' => ['nullable', 'string', 'max:255', $this->imageUploadedForThisModule()],
            'events.*.target_conflict_type' => ['required', Rule::enum(ConflictType::class)],
            'events.*.hint' => ['nullable', 'string', 'max:1000'],
            'events.*.explanation' => ['nullable', 'string', 'max:1000'],
            'events.*.points' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * An event card image must be a file uploaded for this module's Conflict Map cards.
     */
    private function imageUploadedForThisModule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $module = $this->route('module');

            if (! $module instanceof Module || ! app(ConflictMapImageService::class)->belongsToModule((string) $value, $module)) {
                $fail('This event card picture is missing. Please upload it again.');
            }
        };
    }

    /**
     * Custom message for events validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'events.required' => 'At least one event card is required for the Event Bank.',
            'events.min' => 'At least one event card is required for the Event Bank.',
            'events.*.event_text.required_without' => 'Please enter the event text or add a picture for this card.',
            'events.*.target_conflict_type.required' => 'Please select the target drop zone for this event card.',
            'zones.required' => 'At least 2 conflict category drop zones are required.',
        ];
    }
}
