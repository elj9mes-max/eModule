<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeacherAiModuleAssistRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:2', 'max:500'],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'theme' => ['nullable', 'string', 'max:50'],
        ];
    }
}
