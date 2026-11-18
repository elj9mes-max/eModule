<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadConflictMapImageRequest extends FormRequest
{
    /**
     * Largest event card image a teacher can upload, in kilobytes (5 MB).
     */
    public const MAX_IMAGE_KILOBYTES = 5120;

    /**
     * Only the teacher who owns the module may upload card images for it.
     */
    public function authorize(): bool
    {
        $module = $this->route('module');

        return ($this->user()?->isTeacher() ?? false)
            && $module !== null
            && $module->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.self::MAX_IMAGE_KILOBYTES],
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
            'image.image' => 'Event card pictures must be an image file.',
            'image.mimes' => 'Use a JPG, PNG, WEBP, or GIF picture.',
            'image.max' => 'Event card pictures may not be larger than 5 MB.',
            'image.uploaded' => 'The picture failed to upload. Try a smaller file.',
        ];
    }
}
