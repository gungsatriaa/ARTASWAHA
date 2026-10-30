<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Hidden(['correct_option', 'correct_options', 'accepted_answers', 'exercises'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    protected $attributes = ['question_type' => 'single'];

    protected $fillable = ['exercises', 'question_type', 'correct_options', 'accepted_answers', 'learning_path_id', 'title', 'slug', 'summary', 'content', 'sort_order', 'is_published', 'reviewed_at', 'exercise_prompt', 'exercise_options', 'correct_option', 'exercise_explanation', 'keyboard_auto_open'];

    protected function casts(): array
    {
        return ['exercises' => 'array', 'correct_options' => 'array', 'accepted_answers' => 'array', 'is_published' => 'boolean', 'reviewed_at' => 'datetime', 'exercise_options' => 'array', 'correct_option' => 'integer', 'keyboard_auto_open' => 'boolean'];
    }

    /** @return list<array<string, mixed>> */
    public function exerciseQuestions(): array
    {
        if (! empty($this->exercises)) {
            return $this->exercises;
        }

        return $this->exercise_prompt === null ? [] : [[...$this->only(['exercise_prompt', 'exercise_options', 'question_type', 'correct_option', 'correct_options', 'accepted_answers', 'exercise_explanation', 'keyboard_auto_open']), 'section' => 0]];
    }

    public function exerciseVersion(): string
    {
        return hash('sha256', json_encode($this->exerciseQuestions(), JSON_THROW_ON_ERROR));
    }

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function audio(): HasOne
    {
        return $this->hasOne(ContentAudio::class)->where('lesson_audio_slot', 0);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }
}
