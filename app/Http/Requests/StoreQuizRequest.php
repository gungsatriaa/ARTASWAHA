<?php

namespace App\Http\Requests;

use App\Services\QuestionAnswerService;
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
        $rules = [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'leaderboard_enabled' => ['sometimes', 'boolean'],
            'show_answers_immediately' => ['sometimes', 'boolean'],
            'seconds_per_question' => ['nullable', 'integer', 'min:5', 'max:300'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'questions' => ['required', 'array', 'min:1', 'max:20'],
            'questions.*.prompt' => ['required', 'string', 'max:500'],
            'questions.*.id' => ['prohibited'],
            'questions.*.explanation' => ['nullable', 'string', 'max:500'],
            'questions.*.keyboard_auto_open' => ['sometimes', 'boolean'],
            'questions.*.time_limit_seconds' => ['nullable', 'integer', 'min:5', 'max:300'],
        ];
        $questions = $this->input('questions', []);
        foreach (is_array($questions) ? $questions : [] as $index => $question) {
            foreach (app(QuestionAnswerService::class)->definitionRules(is_array($question) ? $question : []) as $field => $ruleset) {
                $rules["questions.{$index}.{$field}"] = $ruleset;
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'questions.*.prompt.required' => 'Isi teks pertanyaan ini terlebih dahulu.',
            'questions.*.correct_option.required' => 'Pilih satu jawaban yang benar untuk soal ini.',
            'questions.*.id.exists' => 'Soal tidak termasuk kuis ini. Muat ulang editor.',
            'questions.*.id.distinct' => 'Soal yang sama tidak dapat disimpan dua kali. Gunakan tombol Duplikasikan soal.',
            'time_limit_minutes.integer' => 'Isi batas total dengan menit bulat.',
            'time_limit_minutes.min' => 'Batas total minimal 1 menit.',
            'time_limit_minutes.max' => 'Batas total maksimal 180 menit.',
            'questions.*.time_limit_seconds.integer' => 'Isi waktu khusus soal dengan detik bulat.',
            'questions.*.time_limit_seconds.min' => 'Waktu khusus soal minimal 5 detik.',
            'questions.*.time_limit_seconds.max' => 'Waktu khusus soal maksimal 300 detik.',
        ];
    }
}
