<?php

namespace App\Http\Requests;

use App\Services\QuestionAnswerService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateAdminLessonRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('title')) {
            $this->merge(['slug' => Str::slug((string) $this->input('title'))]);
        }
    }

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
        $rules = [
            'learning_path_id' => ['required', 'integer', 'exists:learning_paths,id'],
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:150', Rule::unique('lessons', 'slug')->ignore($this->route('lesson'))],
            'summary' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:50000'],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
            'has_exercise' => ['sometimes', 'boolean'],
            'keyboard_auto_open' => ['sometimes', 'boolean'],
            'exercise_prompt' => $this->boolean('has_exercise') ? ['required', 'string', 'max:500'] : ['exclude'],
            'exercise_explanation' => $this->boolean('has_exercise') ? ['nullable', 'string', 'max:500'] : ['exclude'],
        ];
        foreach (app(QuestionAnswerService::class)->definitionRules($this->all(), 'exercise_options', 4, 4) as $field => $ruleset) {
            $rules[$field] = $this->boolean('has_exercise') ? $ruleset : ['exclude'];
        }

        return $rules;
    }
}
