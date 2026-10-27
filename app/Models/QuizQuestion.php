<?php

namespace App\Models;

use Database\Factories\QuizQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Hidden(['correct_option', 'correct_options', 'accepted_answers'])]
class QuizQuestion extends Model
{
    /** @use HasFactory<QuizQuestionFactory> */
    use HasFactory;

    protected $attributes = ['question_type' => 'single'];

    protected $fillable = ['question_type', 'correct_options', 'accepted_answers', 'quiz_id', 'prompt', 'options', 'correct_option', 'explanation', 'sort_order', 'keyboard_auto_open', 'time_limit_seconds'];

    protected function casts(): array
    {
        return ['correct_options' => 'array', 'accepted_answers' => 'array', 'options' => 'array', 'correct_option' => 'integer', 'keyboard_auto_open' => 'boolean', 'time_limit_seconds' => 'integer'];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function audio(): HasOne
    {
        return $this->hasOne(ContentAudio::class);
    }
}
