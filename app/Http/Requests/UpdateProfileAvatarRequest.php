<?php

namespace App\Http\Requests;

use App\Services\ProfileAvatarService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileAvatarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required_without:avatar_upload', 'nullable', 'string', Rule::in(array_keys(ProfileAvatarService::DEFAULT_AVATARS))],
            'avatar_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'dimensions:max_width=2048,max_height=2048', 'max:2048'],
        ];
    }
}
