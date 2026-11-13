<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassroomRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'grade_level' => ['nullable', 'string', 'max:50'],
            'theme' => ['nullable', 'string', 'in:forest,ocean,cosmic,volcano'],
            'icon' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_students' => ['nullable', 'integer', 'min:1', 'max:200'],
            'module_ids' => ['nullable', 'array'],
            'module_ids.*' => ['integer', 'exists:modules,id'],
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
            'name.required' => 'Please provide a name for this classroom or section.',
            'name.max' => 'Classroom name cannot exceed 100 characters.',
        ];
    }
}
