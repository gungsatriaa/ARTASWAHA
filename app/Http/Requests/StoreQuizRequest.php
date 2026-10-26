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
            'questions.*.id' => ['prohibited'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:5'],
            'questions.*.options.*' => ['required', 'string', 'max:140'],
            'questions.*.correct_option' => ['required', 'integer', 'min:0', 'max:4'],
            'questions.*.explanation' => ['nullable', 'string', 'max:500'],
            'questions.*.keyboard_auto_open' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'questions.*.prompt.required' => 'Isi teks pertanyaan ini terlebih dahulu.',
            'questions.*.options.*.required' => 'Isi pilihan jawaban ini atau hapus pilihan yang tidak dipakai.',
            'questions.*.correct_option.required' => 'Pilih satu jawaban yang benar untuk soal ini.',
            'questions.*.id.exists' => 'Soal tidak termasuk kuis ini. Muat ulang editor.',
            'questions.*.id.distinct' => 'Soal yang sama tidak dapat disimpan dua kali. Gunakan tombol Duplikasikan soal.',
        ];
    }
}
