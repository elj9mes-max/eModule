<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadLectureMediaRequest extends FormRequest
{
    /**
     * Largest inline media file a teacher can upload, in kilobytes (2 GB).
     */
    public const MAX_FILE_KILOBYTES = 2_097_152;

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
            'file' => ['required', 'file', 'mimetypes:image/*,audio/*,video/*', 'max:'.self::MAX_FILE_KILOBYTES],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.uploaded' => 'The file failed to upload. Check post_max_size and upload_max_filesize in php.ini.',
            'file.max' => 'Media files may not be larger than 2 GB.',
            'file.mimetypes' => 'Only image, audio, or video files can be inserted.',
        ];
    }
}
