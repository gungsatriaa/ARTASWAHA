<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Hidden(['correct_option'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    protected $fillable = ['learning_path_id', 'title', 'slug', 'summary', 'content', 'sort_order', 'is_published', 'reviewed_at', 'exercise_prompt', 'exercise_options', 'correct_option', 'exercise_explanation'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'reviewed_at' => 'datetime', 'exercise_options' => 'array', 'correct_option' => 'integer'];
    }

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }
}
