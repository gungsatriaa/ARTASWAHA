<?php

namespace App\Models;

use Database\Factories\ContentAudioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentAudio extends Model
{
    /** @use HasFactory<ContentAudioFactory> */
    use HasFactory;

    protected $table = 'content_audio';

    protected $fillable = ['lesson_id', 'quiz_question_id', 'source_hash', 'version', 'spoken_text', 'language', 'speed', 'engine', 'status', 'path', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'speed' => 'integer'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
