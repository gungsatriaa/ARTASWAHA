<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizDraft extends Model
{
    protected $fillable = ['user_id', 'status', 'source_path', 'source_text', 'questions', 'error'];

    protected function casts(): array
    {
        return ['questions' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
