<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateQuizRequest extends StoreQuizRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'questions.*.id' => [
            'nullable', 'integer', 'distinct', Rule::exists('quiz_questions', 'id')->where('quiz_id', $this->route('quiz')?->id),
        ]];
    }

    public function authorize(): bool
    {
        return $this->route('quiz')?->user_id === $this->user()?->id
            && ! $this->route('quiz')->is_archived;
    }
}
