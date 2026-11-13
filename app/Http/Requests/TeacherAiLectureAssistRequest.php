<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeacherAiLectureAssistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isTeacher();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'min:2', 'max:500'],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'mode' => ['nullable', 'string', 'in:describe,full_story,vocabulary_list'],
            'additional_context' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
