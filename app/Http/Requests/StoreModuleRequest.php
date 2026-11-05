<?php

namespace App\Http\Requests;

use App\Enums\ModuleStatus;
use App\Enums\ModuleTheme;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModuleRequest extends FormRequest
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
            'grade_level' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'theme' => ['required', Rule::enum(ModuleTheme::class)],
            'icon' => ['required', 'string', 'max:32'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'xp_bonus' => ['required', 'integer', 'min:0', 'max:100000'],
            'boss_name' => ['nullable', 'string', 'max:120'],
            'max_students' => ['nullable', 'integer', 'min:1', 'max:500'],
            'status' => ['required', Rule::enum(ModuleStatus::class)],
        ];
    }
}
