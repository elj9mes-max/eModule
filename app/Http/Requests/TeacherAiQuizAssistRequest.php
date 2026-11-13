<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeacherAiQuizAssistRequest extends FormRequest
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
            'quiz_type' => ['required', 'string', 'in:multiple_choice,matching_type,reflection,color_the_conflict'],
            'topic_or_passage' => ['required', 'string', 'min:3', 'max:6000'],
            'question_count' => ['nullable', 'integer', 'min:1', 'max:10'],
            'grade_level' => ['nullable', 'string', 'max:100'],
        ];
    }
}
