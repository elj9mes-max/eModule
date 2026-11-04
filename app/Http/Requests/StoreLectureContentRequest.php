<?php

namespace App\Http\Requests;

use App\Enums\LectureContentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLectureContentRequest extends FormRequest
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
            'type' => ['required', Rule::enum(LectureContentType::class)],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'media_file' => ['nullable', 'file', 'max:51200'],
            'external_url' => ['nullable', 'url', 'max:2048'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
