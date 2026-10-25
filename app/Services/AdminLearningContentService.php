<?php

namespace App\Services;

use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class AdminLearningContentService
{
    public function learningPaths(): Collection
    {
        return LearningPath::query()
            ->withCount('allLessons')
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();
    }

    public function lessons(?int $pathId = null, ?string $status = null): LengthAwarePaginator
    {
        return Lesson::query()
            ->with('learningPath:id,title')
            ->when($pathId !== null, fn (Builder $query): Builder => $query->where('learning_path_id', $pathId))
            ->when($status === 'draft', fn (Builder $query): Builder => $query->whereNull('reviewed_at'))
            ->when($status === 'published', fn (Builder $query): Builder => $query->where('is_published', true)->whereNotNull('reviewed_at'))
            ->when($status === 'archived', fn (Builder $query): Builder => $query->where('is_published', false)->whereNotNull('reviewed_at'))
            ->orderBy('learning_path_id')
            ->orderBy('sort_order')->orderBy('id')
            ->paginate(20)->withQueryString();
    }

    public function storeLearningPath(array $data): LearningPath
    {
        return LearningPath::query()->create([...$data, 'is_published' => (bool) ($data['is_published'] ?? false)]);
    }

    public function updateLearningPath(LearningPath $learningPath, array $data): void
    {
        $learningPath->update([...$data, 'is_published' => (bool) ($data['is_published'] ?? false)]);
    }

    public function storeLesson(array $data): Lesson
    {
        return Lesson::query()->create([...$this->lessonData($data), 'is_published' => false, 'reviewed_at' => null]);
    }

    public function updateLesson(Lesson $lesson, array $data): void
    {
        $lesson->update([...$this->lessonData($data), 'is_published' => false, 'reviewed_at' => null]);
        if ($lesson->wasChanged(['title', 'content'])) {
            $lesson->audio()->update(['status' => 'stale', 'reviewed_at' => null, 'version' => (string) Str::uuid()]);
        }
    }

    public function publishLesson(Lesson $lesson): void
    {
        $lesson->update(['is_published' => true, 'reviewed_at' => $lesson->reviewed_at ?? now()]);
    }

    public function archiveLesson(Lesson $lesson): void
    {
        $lesson->update(['is_published' => false]);
    }

    private function lessonData(array $data): array
    {
        $hasExercise = (bool) ($data['has_exercise'] ?? false);
        unset($data['has_exercise']);

        if (! $hasExercise) {
            return [...$data, 'exercise_prompt' => null, 'exercise_options' => null, 'correct_option' => null, 'exercise_explanation' => null];
        }

        return $data;
    }
}
