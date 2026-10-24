<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'leaderboard_enabled' => ['sometimes', 'boolean'],
            'show_answers_immediately' => ['sometimes', 'boolean'],
            'seconds_per_question' => ['nullable', 'integer', 'min:5', 'max:300'],
            'questions' => ['required', 'array', 'min:1', 'max:20'],
            'questions.*.prompt' => ['required', 'string', 'max:500'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:5'],
            'questions.*.options.*' => ['required', 'string', 'max:140'],
            'questions.*.correct_option' => ['required', 'integer', 'min:0', 'max:4'],
            'questions.*.explanation' => ['nullable', 'string', 'max:500'],
        ];
    }
}
