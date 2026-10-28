<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomAnswerRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'min:1'],
            'answer_index' => ['nullable', 'integer', 'between:0,4'],
            'answer_indexes' => ['nullable', 'array', 'list', 'max:5'],
            'answer_indexes.*' => ['integer', 'distinct', 'between:0,4'],
            'answer_text' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
