<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Hidden(['correct_option', 'correct_options', 'accepted_answers'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    protected $attributes = ['question_type' => 'single'];

    protected $fillable = ['question_type', 'correct_options', 'accepted_answers', 'learning_path_id', 'title', 'slug', 'summary', 'content', 'sort_order', 'is_published', 'reviewed_at', 'exercise_prompt', 'exercise_options', 'correct_option', 'exercise_explanation', 'keyboard_auto_open'];

    protected function casts(): array
    {
        return ['correct_options' => 'array', 'accepted_answers' => 'array', 'is_published' => 'boolean', 'reviewed_at' => 'datetime', 'exercise_options' => 'array', 'correct_option' => 'integer', 'keyboard_auto_open' => 'boolean'];
    }

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function audio(): HasOne
    {
        return $this->hasOne(ContentAudio::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }
}
