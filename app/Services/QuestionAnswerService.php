<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\Validator;
use Normalizer;

class QuestionAnswerService
{
    /** @return array<string, list<string>> */
    public function lessonExerciseRules(array $data): array
    {
        $rules = ['exercises' => ['sometimes', 'nullable', 'array', 'list', 'max:50']];
        foreach (is_array($data['exercises'] ?? null) ? $data['exercises'] : [] as $index => $exercise) {
            $prefix = 'exercises.'.$index;
            $rules[$prefix] = ['required', 'array:section,question_type,exercise_prompt,exercise_options,correct_option,correct_options,accepted_answers,exercise_explanation,keyboard_auto_open'];
            $rules[$prefix.'.section'] = ['required', 'integer', 'between:0,20'];
            $rules[$prefix.'.exercise_prompt'] = ['required', 'string', 'max:500'];
            $rules[$prefix.'.exercise_explanation'] = ['nullable', 'string', 'max:1000'];
            $rules[$prefix.'.keyboard_auto_open'] = ['sometimes', 'boolean'];
            foreach ($this->definitionRules(is_array($exercise) ? $exercise : [], 'exercise_options') as $field => $rule) {
                $rules[$prefix.'.'.$field] = $rule;
            }
        }

        return $rules;
    }

    /** @return array<string, list<string>> */
    public function definitionRules(array $data, string $optionsKey = 'options', int $minimum = 2, int $maximum = 5): array
    {
        $type = $data['question_type'] ?? 'single';
        $last = max(0, count(is_array($data[$optionsKey] ?? null) ? $data[$optionsKey] : []) - 1);

        return [
            'question_type' => ['sometimes', 'required', 'in:single,multiple,essay'],
            $optionsKey => $type === 'essay' ? ['exclude'] : ['required', 'array', 'list', "min:{$minimum}", "max:{$maximum}"],
            $optionsKey.'.*' => $optionsKey === 'exercise_options' ? ['required', 'string', 'max:140', 'distinct'] : ['required', 'string', 'max:140'],
            'correct_option' => $type === 'single' ? ['required', 'integer', "between:0,{$last}"] : ['exclude'],
            'correct_options' => $type === 'multiple' ? ['required', 'array', 'list', 'min:1', "max:{$maximum}"] : ['exclude'],
            'correct_options.*' => $type === 'multiple' ? ['required', 'integer', 'distinct', "between:0,{$last}"] : ['exclude'],
            'accepted_answers' => $type === 'essay' ? ['required', 'array', 'list', 'min:1', 'max:10'] : ['exclude'],
            'accepted_answers.*' => $type === 'essay' ? ['required', 'string', 'max:2000', 'distinct'] : ['exclude'],
        ];
    }

    public function attributes(array $data, string $optionsKey = 'options'): array
    {
        $type = $data['question_type'] ?? 'single';

        return [...$data, 'question_type' => $type,
            $optionsKey => $type === 'essay' ? [] : $data[$optionsKey],
            'correct_option' => $type === 'single' ? (int) $data['correct_option'] : null,
            'correct_options' => $type === 'multiple' ? array_map('intval', $data['correct_options']) : null,
            'accepted_answers' => $type === 'essay' ? $data['accepted_answers'] : null,
        ];
    }

    public function isCorrect(Lesson|QuizQuestion $question, array $answer): bool
    {
        $options = $question instanceof Lesson ? $question->exercise_options : $question->options;
        $last = max(0, count($options ?? []) - 1);
        if ($question->question_type === 'essay') {
            $data = Validator::make($answer, ['answer_text' => ['required', 'string', 'max:2000']])->validate();
            $submitted = $this->normalize($data['answer_text']);

            return $submitted !== '' && in_array($submitted, array_map($this->normalize(...), $question->accepted_answers ?? []), true);
        }
        if ($question->question_type === 'multiple') {
            $data = Validator::make($answer, [
                'answer_indexes' => ['required', 'array', 'list', 'min:1', 'max:5'],
                'answer_indexes.*' => ['required', 'integer', 'distinct', "between:0,{$last}"],
            ])->validate();
            $selected = array_map('intval', $data['answer_indexes']);
            $correct = $question->correct_options ?? [];
            sort($selected);
            sort($correct);

            return $correct !== [] && $selected === $correct;
        }
        $data = Validator::make($answer, ['answer_index' => ['required', 'integer', "between:0,{$last}"]])->validate();

        return (int) $data['answer_index'] === $question->correct_option;
    }

    public function correctText(Lesson|QuizQuestion $question): string
    {
        $options = $question instanceof Lesson ? $question->exercise_options : $question->options;

        return match ($question->question_type) {
            'essay' => implode(' / ', $question->accepted_answers ?? []),
            'multiple' => implode(', ', array_map(fn (int $index): string => $options[$index] ?? '', $question->correct_options ?? [])),
            default => $options[$question->correct_option] ?? '',
        };
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', mb_strtolower(Normalizer::normalize($text) ?: $text)) ?? '');
    }
}
