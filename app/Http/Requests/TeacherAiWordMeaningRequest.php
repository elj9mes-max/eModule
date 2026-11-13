<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeacherAiWordMeaningRequest extends FormRequest
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
            'word' => ['required', 'string', 'min:1', 'max:100'],
            'context' => ['nullable', 'string', 'max:2000'],
            'grade_level' => ['nullable', 'string', 'max:100'],
        ];
    }
}
