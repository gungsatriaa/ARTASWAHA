<?php

namespace App\Http\Requests;

class UpdateQuizRequest extends StoreQuizRequest
{
    public function authorize(): bool
    {
        return $this->route('quiz')?->user_id === $this->user()?->id
            && ! $this->route('quiz')->is_archived;
    }
}
