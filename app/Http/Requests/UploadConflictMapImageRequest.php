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
     * Maximum number of pictures a teacher can upload in a single batch.
     */
    public const MAX_BATCH_IMAGES = 30;

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
            'image' => ['required_without:images', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.self::MAX_IMAGE_KILOBYTES],
            'images' => ['required_without:image', 'nullable', 'array', 'min:1', 'max:'.self::MAX_BATCH_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.self::MAX_IMAGE_KILOBYTES],
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
            'image.required_without' => 'Please select at least one picture to upload.',
            'images.required_without' => 'Please select at least one picture to upload.',
            'image.image' => 'Event card pictures must be an image file.',
            'image.mimes' => 'Use a JPG, PNG, WEBP, or GIF picture.',
            'image.max' => 'Event card pictures may not be larger than 5 MB.',
            'image.uploaded' => 'The picture failed to upload. Try a smaller file.',
            'images.array' => 'Pictures must be provided as a list.',
            'images.max' => 'You can upload up to 30 pictures at once.',
            'images.*.image' => 'Each uploaded file must be an image.',
            'images.*.mimes' => 'Each picture must be a JPG, PNG, WEBP, or GIF file.',
            'images.*.max' => 'Each picture may not be larger than 5 MB.',
            'images.*.uploaded' => 'One or more pictures failed to upload. Try smaller files.',
        ];
    }
}
