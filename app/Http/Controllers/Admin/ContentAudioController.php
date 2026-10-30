<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentAudio;
use App\Models\Lesson;
use App\Models\QuizQuestion;
use App\Services\ContentAudioService;
use App\Services\PagePresenter;
use App\Services\ReactPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContentAudioController extends Controller
{
    public function index(Request $request, ContentAudioService $service): ReactPage
    {
        $filters = $request->validate(['type' => ['nullable', 'in:records,modules,questions'], 'status' => ['nullable', 'in:draft,queued,processing,review,ready,failed,stale,unavailable'], 'q' => ['nullable', 'string', 'max:100']]);
        $type = $filters['type'] ?? 'records';
        $items = match ($type) {
            'modules' => Lesson::query()->with('learningPath:id,title')->when($filters['q'] ?? null, fn (Builder $query, string $text): Builder => $query->where('title', 'like', '%'.$text.'%'))->latest('id')->paginate(15)->withQueryString(),
            'questions' => QuizQuestion::query()->with('quiz:id,title')->when($filters['q'] ?? null, fn (Builder $query, string $text): Builder => $query->where('prompt', 'like', '%'.$text.'%'))->latest('id')->paginate(15)->withQueryString(),
            default => ContentAudio::query()->with(['lesson', 'question.quiz:id,title'])->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))->latest('updated_at')->paginate(15)->withQueryString(),
        };
        $configured = $service->isConfigured();

        return app(PagePresenter::class)->render('admin.audio.index', compact('items', 'type', 'configured', 'service'));
    }

    public function lesson(Lesson $lesson, ContentAudioService $service, int $slot = 0): ReactPage
    {
        return $this->editor($lesson, $service, $slot);
    }

    public function question(QuizQuestion $question, ContentAudioService $service): ReactPage
    {
        return $this->editor($question, $service);
    }

    public function saveLesson(Request $request, Lesson $lesson, ContentAudioService $service, int $slot = 0): RedirectResponse
    {
        return $this->save($request, $lesson, $service, $slot);
    }

    public function saveQuestion(Request $request, QuizQuestion $question, ContentAudioService $service): RedirectResponse
    {
        return $this->save($request, $question, $service);
    }

    public function approve(Request $request, ContentAudio $contentAudio, ContentAudioService $service): RedirectResponse
    {
        $data = $request->validate(['version' => ['required', 'uuid']]);
        $service->approve($contentAudio, $data['version']);

        return back()->with('status', 'Audio sudah ditinjau dan siap digunakan.');
    }

    public function destroy(Request $request, ContentAudio $contentAudio, ContentAudioService $service): RedirectResponse
    {
        $data = $request->validate(['version' => ['required', 'uuid']]);
        $service->delete($contentAudio, $data['version']);

        return back()->with('status', 'Audio dihapus. Narasi tetap tersimpan dan bisa dibuatkan audio kembali.');
    }

    public function status(ContentAudio $contentAudio, ContentAudioService $service): JsonResponse
    {
        $current = $service->isCurrent($contentAudio);
        $status = $current ? $contentAudio->status : 'stale';
        $progress = $service->generationProgress($contentAudio);

        return response()->json([
            'version' => $contentAudio->version,
            'status' => $status,
            'progress' => $progress, 'server_now' => now()->toISOString(),
            'audio' => app(PagePresenter::class)->audio($contentAudio),
        ])->header('Cache-Control', 'private, no-store');
    }

    private function editor(Lesson|QuizQuestion $content, ContentAudioService $service, int $slot = 0): ReactPage
    {
        $defaultText = $service->defaultText($content, $slot);
        $audio = $service->forContent($content, $slot);
        $isLesson = $content instanceof Lesson;
        $title = $isLesson ? $content->title.($slot > 0 ? ' - Soal '.$slot : ' - Materi') : $content->prompt;
        $current = $audio === null || $service->isCurrent($audio);
        $text = $audio !== null && $current ? $audio->spoken_text : $defaultText;
        $action = route($isLesson ? 'admin.audio.lessons.store' : 'admin.audio.questions.store', $isLesson ? ['lesson' => $content, 'slot' => $slot ?: null] : $content);
        $configured = $service->isConfigured();

        $targets = $isLesson ? collect(range(0, count($content->exerciseQuestions())))->map(fn (int $index): array => [
            'title' => $index === 0 ? 'Materi' : 'Soal '.$index,
            'url' => route('admin.audio.lessons.edit', ['lesson' => $content, 'slot' => $index ?: null]),
            'active' => $index === $slot,
        ])->all() : [];

        return app(PagePresenter::class)->render('admin.audio.edit', compact('content', 'audio', 'title', 'text', 'action', 'configured', 'current', 'isLesson', 'targets'));
    }

    private function save(Request $request, Lesson|QuizQuestion $content, ContentAudioService $service, int $slot = 0): RedirectResponse
    {
        $data = $request->validate([
            'spoken_text' => ['required', 'string', 'max:'.ContentAudioService::MAX_TEXT_LENGTH], 'language' => ['required', 'in:id,en,ban'],
            'speed' => ['required', 'integer', 'between:100,220'], 'action' => ['required', 'in:save,generate,upload'], 'version' => ['nullable', 'uuid'],
            'audio_upload' => ['required_if:action,upload', 'nullable', 'file', 'mimes:mp3,wav,ogg', 'max:10240'],
        ]);
        $service->save($content, $data, $data['action'] === 'upload' ? $request->file('audio_upload') : null, $slot);

        return back()->with('status', 'Pengaturan audio tersimpan. Periksa status dan dengarkan hasilnya sebelum menyetujui.');
    }
}
