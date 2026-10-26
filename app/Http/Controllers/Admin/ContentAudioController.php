<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentAudio;
use App\Models\Lesson;
use App\Models\QuizQuestion;
use App\Services\ContentAudioService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentAudioController extends Controller
{
    public function index(Request $request, ContentAudioService $service): View
    {
        $filters = $request->validate(['type' => ['nullable', 'in:records,modules,questions'], 'status' => ['nullable', 'in:draft,queued,processing,review,ready,failed,stale,unavailable'], 'q' => ['nullable', 'string', 'max:100']]);
        $type = $filters['type'] ?? 'records';
        $items = match ($type) {
            'modules' => Lesson::query()->with('learningPath:id,title')->when($filters['q'] ?? null, fn (Builder $query, string $text): Builder => $query->where('title', 'like', '%'.$text.'%'))->latest('id')->paginate(15)->withQueryString(),
            'questions' => QuizQuestion::query()->with('quiz:id,title')->when($filters['q'] ?? null, fn (Builder $query, string $text): Builder => $query->where('prompt', 'like', '%'.$text.'%'))->latest('id')->paginate(15)->withQueryString(),
            default => ContentAudio::query()->with(['lesson', 'question.quiz:id,title'])->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))->latest('updated_at')->paginate(15)->withQueryString(),
        };
        $configured = $service->isConfigured();

        return view('admin.audio.index', compact('items', 'type', 'configured', 'service'));
    }

    public function lesson(Lesson $lesson, ContentAudioService $service): View
    {
        return $this->editor($lesson, $service);
    }

    public function question(QuizQuestion $question, ContentAudioService $service): View
    {
        return $this->editor($question, $service);
    }

    public function saveLesson(Request $request, Lesson $lesson, ContentAudioService $service): RedirectResponse
    {
        return $this->save($request, $lesson, $service);
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

    public function status(ContentAudio $contentAudio, ContentAudioService $service): JsonResponse
    {
        $current = $service->isCurrent($contentAudio);
        $status = $current ? $contentAudio->status : 'stale';

        return response()->json([
            'version' => $contentAudio->version,
            'status' => $status,
            'html' => in_array($status, ['queued', 'processing'], true) ? null
                : view('admin.audio.result', ['audio' => $contentAudio, 'current' => $current])->render(),
        ])->header('Cache-Control', 'private, no-store');
    }

    private function editor(Lesson|QuizQuestion $content, ContentAudioService $service): View
    {
        $audio = $service->forContent($content);
        $isLesson = $content instanceof Lesson;
        $title = $isLesson ? $content->title : $content->prompt;
        $current = $audio === null || $service->isCurrent($audio);
        $text = $audio !== null && $current ? $audio->spoken_text : $service->defaultText($content);
        $action = route($isLesson ? 'admin.audio.lessons.store' : 'admin.audio.questions.store', $content);
        $configured = $service->isConfigured();

        return view('admin.audio.edit', compact('content', 'audio', 'title', 'text', 'action', 'configured', 'current', 'isLesson'));
    }

    private function save(Request $request, Lesson|QuizQuestion $content, ContentAudioService $service): RedirectResponse
    {
        $data = $request->validate([
            'spoken_text' => ['required', 'string', 'max:8000'], 'language' => ['required', 'in:id,en,ban'],
            'speed' => ['required', 'integer', 'between:100,220'], 'action' => ['required', 'in:save,generate,upload'], 'version' => ['nullable', 'uuid'],
            'audio_upload' => ['required_if:action,upload', 'nullable', 'file', 'mimes:mp3,wav,ogg', 'max:10240'],
        ]);
        $service->save($content, $data, $data['action'] === 'upload' ? $request->file('audio_upload') : null);

        return back()->with('status', 'Pengaturan audio tersimpan. Periksa status dan dengarkan hasilnya sebelum menyetujui.');
    }
}
