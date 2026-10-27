<?php

namespace App\Services;

use App\Models\ContentAudio;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizDraft;
use App\Models\QuizQuestion;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class PagePresenter
{
    public function __construct(private ContentAudioService $audioService) {}

    /** @param array<string, mixed> $data */
    public function render(string $view, array $data = []): Response
    {
        [$page, $props, $title] = match ($view) {
            'welcome' => ['Home', $this->home($data), 'Belajar bahasa dan aksara Bali'],
            'auth.login', 'auth.register', 'auth.forgot-password', 'auth.reset-password' => ['Auth/Form', ['mode' => substr($view, 5), 'token' => $data['token'] ?? '', 'email' => request()->query('email', '')], match ($view) {
                'auth.login' => 'Selamat datang kembali', 'auth.register' => 'Buat akun belajarmu', 'auth.forgot-password' => 'Lupa kata sandi?', default => 'Atur kata sandi baru'
            }],
            'privacy-policy' => ['Privacy', [], 'Kebijakan privasi'],
            'learning.interview' => ['Learning/Interview', ['paths' => $data['learningPaths']->map(fn ($path) => $this->path($path))->values(), 'answers' => $data['answers'], 'step' => $data['step'], 'roles' => $data['roles'], 'avatar' => asset(array_key_first(ProfileAvatarService::DEFAULT_AVATARS))], 'Mulai belajar'],
            'learning.lessons.show' => ['Learning/Lesson', $this->learnerLesson($data), $data['lesson']->title],
            'profile.show', 'profile.about' => ['Profile/Show', $this->profile($data, $view === 'profile.about'), $view === 'profile.about' ? 'Tentang ARTASWAHA' : 'Profil belajarmu'],
            'quizzes.index' => ['Quizzes/Index', ['quizzes' => $this->listing($data['quizzes'], fn ($quiz) => $this->quiz($quiz)), 'filters' => $data['filters'], 'code' => request()->query('code', '')], 'Arena kuis Bali'],
            'quizzes.create' => ['Quizzes/Editor', ['quiz' => isset($data['quiz']) ? $this->quiz($data['quiz'], true) : null, 'hasRooms' => $data['hasRooms'] ?? false, 'preview' => request()->boolean('preview')], isset($data['quiz']) ? 'Edit kuis' : 'Buat kuis'],
            'quizzes.show' => ['Quizzes/Show', ['quiz' => $this->quiz($data['quiz'], true)], $data['quiz']->title],
            'rooms.show' => ['Rooms/Show', $this->room($data), 'Room '.$data['room']->code],
            'admin.dashboard' => ['Admin/Dashboard', ['overview' => $data['overview'], 'counts' => $data['lessonCounts']->only(['total', 'published', 'drafts', 'archived']), 'tasks' => $data['aiTasks'], 'paths' => $data['learningPaths']->map(fn ($path) => $this->adminPath($path)), 'lessons' => $data['recentLessons']->map(fn ($lesson) => $this->lesson($lesson, true, false))], 'Dashboard pengelola'],
            'admin.analytics' => ['Admin/Analytics', $data, 'Analitik ARTASWAHA'],
            'admin.learning-paths.index' => ['Admin/Paths', ['paths' => $this->listing($data['learningPaths'], fn ($path) => $this->adminPath($path))], 'Jalur belajar'],
            'admin.learning-paths.form' => ['Admin/PathForm', ['path' => $this->adminPath($data['learningPath'])], $data['learningPath']->exists ? 'Ubah jalur belajar' : 'Buat jalur belajar'],
            'admin.lessons.index' => ['Admin/Lessons', ['lessons' => $this->listing($data['lessons'], fn ($lesson) => $this->lesson($lesson, true, false)), 'paths' => $data['learningPaths']->map(fn ($path) => $this->path($path)), 'filters' => request()->only(['learning_path_id', 'status'])], 'Modul pembelajaran'],
            'admin.lessons.form' => ['Admin/LessonForm', ['lesson' => $this->lesson($data['lesson'], true), 'paths' => $data['learningPaths']->map(fn ($path) => $this->path($path))], $data['lesson']->exists ? 'Ubah modul' : 'Modul baru'],
            'admin.lessons.preview' => ['Admin/Preview', ['lesson' => $this->lesson($data['lesson'], true), 'audio' => $this->audio($data['audio'] ?? null)], 'Preview modul'],
            'admin.audio.index' => ['Admin/AudioIndex', $this->audioIndex($data), 'Audio pembelajaran'],
            'admin.audio.edit' => ['Admin/AudioEdit', ['title' => $data['title'], 'text' => $data['text'], 'action' => $data['action'], 'configured' => $data['configured'], 'audio' => $this->audio($data['audio']), 'backUrl' => $data['isLesson'] ? route('admin.lessons.preview', $data['content']) : route('quizzes.index')], 'Siapkan audio'],
            'admin.ai.index' => ['Admin/Ai', $this->aiIndex($data), 'Pusat AI'],
            'admin.ai.profile' => ['Admin/AiDetail', ['kind' => 'profile', 'record' => ['id' => $data['learnerProfile']->id, 'mode' => $data['learnerProfile']->mode, 'status' => $data['analysis']['status'], 'analysis' => $data['analysis'], 'paths' => $data['paths']->map(fn ($path) => $this->path($path)), 'retryUrl' => route('admin.ai.profiles.retry', $data['learnerProfile'])]], 'Detail analisis interview'],
            'admin.ai.draft' => ['Admin/AiDetail', ['kind' => 'draft', 'record' => $data['quizDraft']->only(['id', 'status', 'questions'])], 'Detail draf kuis AI'],
            'admin.ai.report' => ['Admin/AiDetail', ['kind' => 'report', 'record' => $data['aiReport']->only(['id', 'status', 'period_start', 'period_end', 'snapshot', 'result', 'completed_at'])], 'Interpretasi statistik AI'],
        };

        return Inertia::render($page, [...$props, 'meta' => ['title' => $title.' — ARTASWAHA', 'description' => $data['lesson']->summary ?? 'Belajar bahasa, sastra, dan aksara Bali melalui materi, latihan, dan kuis ARTASWAHA.', 'schema' => $page === 'Home' ? ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'ARTASWAHA', 'url' => route('home')] : null, 'canonical' => request()->url(), 'robots' => in_array($page, ['Home', 'Privacy', 'Learning/Lesson'], true) ? 'index, follow' : 'noindex, nofollow']]);
    }

    public function path(LearningPath $path): array
    {
        return [...$path->only(['id', 'slug', 'title', 'description', 'group']), 'lessonsCount' => (int) ($path->lessons_count ?? 0), 'completed' => (int) ($path->completed_lessons_count ?? 0), 'url' => $path->slug ? route('learning.paths.show', $path->slug) : null];
    }

    private function adminPath(LearningPath $path): array
    {
        return [...$this->path($path), ...$path->only(['sort_order', 'is_published']), 'count' => $path->all_lessons_count ?? 0, 'editUrl' => $path->exists ? route('admin.learning-paths.edit', $path) : null, 'action' => $path->exists ? route('admin.learning-paths.update', $path) : route('admin.learning-paths.store')];
    }

    private function lesson(Lesson $lesson, bool $author = false, bool $detail = true): array
    {
        $result = [...$lesson->only(['id', 'slug', 'title', 'summary', 'content', 'exercise_prompt', 'exercise_options', 'question_type', 'keyboard_auto_open']), 'url' => $lesson->slug ? route('learning.lessons.show', $lesson->slug) : null];
        if ($author) {
            $result += [...$lesson->only(['learning_path_id', 'sort_order', 'is_published', 'reviewed_at', 'correct_option', 'correct_options', 'accepted_answers', 'exercise_explanation']), 'action' => $lesson->exists ? route('admin.lessons.update', $lesson) : route('admin.lessons.store')];
            foreach (['edit', 'preview', 'publish', 'archive'] as $action) {
                $result[$action.'Url'] = $lesson->exists ? route('admin.lessons.'.$action, $lesson) : null;
            }
            $result['audioUrl'] = $lesson->exists ? route('admin.audio.lessons.edit', $lesson) : null;
        }

        if (! $detail) {
            unset($result['content'], $result['exercise_prompt'], $result['exercise_options'], $result['correct_option'], $result['exercise_explanation']);
        }

        return $result;
    }

    private function learnerLesson(array $data): array
    {
        $lesson = $data['lesson'];
        $progress = $data['progress'];

        return ['lesson' => [...$this->lesson($lesson), 'path' => $this->path($lesson->learningPath), 'answerUrl' => route('learning.exercises.answer', $lesson->slug), 'completeUrl' => route('learning.progress.store', $lesson->slug), 'explanation' => $progress?->completed_at ? $lesson->exercise_explanation : null], 'progress' => $progress?->only(['points', 'completed_at']), 'audio' => isset($data['audio']) ? ['url' => route('content-audio.show', $data['audio'])] : null];
    }

    private function home(array $data): array
    {
        $progress = $data['lastLearningProgress'];

        return ['paths' => $data['learningPaths']->map(fn ($path) => $this->path($path))->values(), 'selectedIds' => $data['profile']?->learning_path_ids ?: array_filter([$data['profile']?->learning_path_id]), 'hasInterview' => $data['hasCompletedInterview'], 'analysis' => $data['analysis'], 'recent' => $progress ? ['title' => $progress->lesson->title, 'pathTitle' => $progress->lesson->learningPath->title, 'url' => route('learning.lessons.show', $progress->lesson->slug), 'completedAt' => $progress->completed_at->toISOString(), 'points' => $progress->points] : null];
    }

    private function profile(array $data, bool $about): array
    {
        $profile = $data['analysisProfile'] ?? $data['profile'] ?? null;
        $ids = $profile?->learning_path_ids ?: array_filter([$profile?->learning_path_id]);

        return ['about' => $about, 'email' => request()->user()?->email, 'version' => config('app.version'), 'completedLessons' => $data['completedLessons'] ?? 0, 'totalLessons' => $data['totalLessons'] ?? 0, 'paths' => $data['learningPaths']->filter(fn ($path) => $path->completed_lessons_count > 0)->map(fn ($path) => $this->path($path))->values(), 'interests' => $data['learningPaths']->whereIn('id', $ids)->map(fn ($path) => $this->path($path))->values(), 'analysis' => $data['analysis'] ?? null, 'activity' => $data['activity'] ?? null, 'avatars' => collect(ProfileAvatarService::DEFAULT_AVATARS)->map(fn ($name, $path) => ['value' => $path, 'name' => $name, 'url' => asset($path)])->values()];
    }

    private function quiz(Quiz $quiz, bool $questions = false): array
    {
        $result = [...$quiz->only(['id', 'title', 'description', 'is_archived', 'seconds_per_question', 'time_limit_seconds', 'show_answers_immediately']), 'questionsCount' => $quiz->questions_count, 'roomsCount' => $quiz->rooms_count];
        foreach (['show' => 'url', 'edit' => 'editUrl', 'duplicate' => 'duplicateUrl', 'archive' => 'archiveUrl', 'update' => 'action'] as $action => $key) {
            $result[$key] = route('quizzes.'.$action, $quiz);
        }
        $result['roomUrl'] = route('rooms.store', $quiz);
        if ($questions) {
            $result['questions'] = $quiz->questions->map(fn ($question) => [...$question->only(['id', 'prompt', 'options', 'question_type', 'correct_options', 'accepted_answers', 'correct_option', 'explanation', 'keyboard_auto_open', 'time_limit_seconds']), 'audio' => $this->audioService->isPlayable($this->audioService->forContent($question)) ? $this->audio($this->audioService->forContent($question)) : null, 'audioEditUrl' => request()->user()?->is_admin ? route('admin.audio.questions.edit', $question) : null]);
        }

        return $result;
    }

    private function room(array $data): array
    {
        ['room' => $room, 'participant' => $participant, 'activeQuestion' => $question, 'currentAnswer' => $answer, 'isFeedback' => $feedback] = $data;
        $deadline = $participant?->question_ends_at;
        if ($deadline && $room->ends_at && $room->ends_at->lt($deadline)) {
            $deadline = $room->ends_at;
        }
        $participants = $room->participants->map(function ($member) use ($room): array {
            $identity = $member->user ?? $member->learnerProfile;
            $avatar = $identity?->avatar ?? array_key_first(ProfileAvatarService::DEFAULT_AVATARS);

            return ['id' => $member->id, 'name' => $identity?->name ?? $member->display_name, 'host' => $member->user_id === $room->host_id, 'avatar' => str_starts_with($avatar, 'avatars/') ? route($member->user_id ? 'rooms.participant-avatar' : 'rooms.anonymous-avatar', [$room->code, $member->user_id ?? $member->learner_profile_id]) : asset($avatar)];
        });

        return ['room' => ['code' => $room->code, 'status' => $room->status, 'title' => $room->quiz->title, 'isHost' => request()->user()?->id === $room->host_id, 'participants' => $participants, 'count' => $room->quiz->questions->count(), 'position' => $question ? $room->quiz->questions->search(fn ($item) => $item->id === $question->id) + 1 : 0, 'question' => $question?->only(['id', 'prompt', 'options', 'question_type', 'keyboard_auto_open']), 'answer' => $answer?->only(['answer_index', 'answer_indexes', 'answer_text', 'is_correct']), 'correctOption' => $feedback ? $question?->correct_option : null, 'correctOptions' => $feedback ? $question?->correct_options : null, 'correctText' => $feedback && $question ? app(QuestionAnswerService::class)->correctText($question) : null, 'feedback' => $feedback, 'showAnswers' => $room->quiz->show_answers_immediately, 'score' => $participant?->score ?? 0, 'serverNow' => now()->toISOString(), 'deadline' => ! $feedback ? $deadline?->toISOString() : null, 'endsAt' => $room->ends_at?->toISOString(), 'seconds' => $question?->time_limit_seconds ?? $room->seconds_per_question, 'totalSeconds' => $room->time_limit_seconds, 'totalExpired' => $room->status === 'finished' && $room->ends_at && $room->finished_at?->equalTo($room->ends_at), 'audio' => $this->audio($data['audio'] ?? null), 'urls' => collect(['show', 'status', 'start', 'answer', 'leave', 'destroy', 'heartbeat'])->mapWithKeys(fn ($action) => [$action => route('rooms.'.$action, $room->code)]), 'invite' => $room->status === 'waiting' ? route('quizzes.index', ['code' => $room->code]) : null, 'results' => $room->status === 'finished' ? $room->quiz->questions->map(function ($item) use ($data): array {
            $answer = $data['answersByQuestion']->get($item->id);

            return ['prompt' => $item->prompt, 'correct' => app(QuestionAnswerService::class)->correctText($item), 'selected' => $answer ? ($item->question_type === 'essay' ? $answer->answer_text : ($item->question_type === 'multiple' ? implode(', ', array_map(fn (int $index): string => $item->options[$index] ?? '', $answer->answer_indexes ?? [])) : ($item->options[$answer->answer_index] ?? null))) : null, 'isCorrect' => $answer?->is_correct ?? false];
        }) : []]];
    }

    public function audio(?ContentAudio $audio): ?array
    {
        if ($audio === null) {
            return null;
        }
        $admin = request()->user()?->is_admin;
        $current = $this->audioService->isCurrent($audio);
        $playable = $this->audioService->isPlayable($audio);
        if (! $admin && ! $playable) {
            return null;
        }

        return ['url' => ($playable || ($admin && $audio->path)) ? route('content-audio.show', $audio) : null, ...($admin ? [...$audio->only(['id', 'version', 'language', 'speed']), 'status' => $current ? $audio->status : 'stale', 'progress' => $this->audioService->generationProgress($audio), 'statusUrl' => route('admin.audio.status', $audio), 'approveUrl' => route('admin.audio.approve', $audio)] : [])];
    }

    private function audioIndex(array $data): array
    {
        return ['type' => $data['type'], 'configured' => $data['configured'], 'filters' => request()->only(['q', 'status', 'type']), 'items' => $this->listing($data['items'], function ($item) use ($data): array {
            $record = $data['type'] === 'records';
            $lesson = $record ? $item->lesson : ($item instanceof Lesson ? $item : null);
            $question = $record ? $item->question : ($item instanceof QuizQuestion ? $item : null);

            return ['id' => $item->id, 'title' => $lesson?->title ?? $question?->prompt ?? 'Materi tidak tersedia', 'status' => $record ? ($this->audioService->isCurrent($item) ? $item->status : 'stale') : null, 'url' => $lesson ? route('admin.audio.lessons.edit', $lesson) : ($question ? route('admin.audio.questions.edit', $question) : null)];
        })];
    }

    private function aiIndex(array $data): array
    {
        $summary = fn ($item) => [...$item->only(['id', 'status', 'analysis_status', 'mode', 'role', 'created_at', 'period_start', 'period_end']), 'url' => route(match (get_class($item)) {
            LearnerProfile::class => 'admin.ai.profiles.show', QuizDraft::class => 'admin.ai.drafts.show', default => 'admin.ai.reports.show'
        }, $item)];

        return ['tab' => $data['tab'], 'start' => $data['start']->toDateString(), 'end' => $data['end']->toDateString(), 'snapshot' => $data['snapshot'], 'statuses' => $data['statuses']->map(fn ($item) => $item->only(['analysis_status', 'total'])), 'profiles' => $this->listing($data['profiles'], $summary), 'drafts' => $this->listing($data['drafts'], $summary), 'reports' => $this->listing($data['reports'], $summary), 'paths' => $data['learningPaths']->map(fn ($path) => $this->path($path)), 'aiConfigured' => $data['aiConfigured'], 'ttsConfigured' => $data['ttsConfigured'], 'pendingJobs' => $data['pendingJobs'], 'audioCounts' => $data['audioCounts'], 'filters' => request()->only(['role', 'status', 'mode', 'learning_path_id'])];
    }

    private function listing(mixed $items, Closure $map): mixed
    {
        return $items instanceof LengthAwarePaginator ? $items->through($map) : $items?->map($map)->values();
    }
}
