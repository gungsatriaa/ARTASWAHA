<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminLessonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'learning_path_id' => ['required', 'integer', 'exists:learning_paths,id'],
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:150', Rule::unique('lessons', 'slug')->ignore($this->route('lesson'))],
            'summary' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:50000'],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
        ];
    }
}
