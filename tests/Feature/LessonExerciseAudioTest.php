<?php

namespace Tests\Feature;

use App\Jobs\GenerateContentAudio;
use App\Models\ContentAudio;
use App\Models\Lesson;
use App\Models\User;
use App\Services\ContentAudioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\AssertablePage as Assert;
use Tests\TestCase;

class LessonExerciseAudioTest extends TestCase
{
    use RefreshDatabase;

    private function lesson(): Lesson
    {
        return Lesson::factory()->create(['title' => 'Tebak kegiatan',
            'content' => "## Materi\nAmati **gerak**. [Baca contoh](https://example.com/contoh).\n## Sumber belajar\nRujukan konsep: https://example.com/sumber\nCatatan lisensi.\n## Penutup\nSelamat belajar.",
            'exercises' => [
                ['section' => 0, 'question_type' => 'single', 'exercise_prompt' => 'Kegiatan apa?', 'exercise_options' => ['Nyampat', 'Macukur'], 'correct_option' => 0, 'exercise_explanation' => 'RAHASIA pembahasan'],
                ['section' => 0, 'question_type' => 'essay', 'exercise_prompt' => 'Tulis kegiatan.', 'exercise_options' => [], 'accepted_answers' => ['RAHASIA acuan']],
            ]]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
        $this->withCredentials();
    }

    public function test_material_narration_removes_sources_urls_and_markdown_without_removing_later_material(): void
    {
        $service = app(ContentAudioService::class);
        $lesson = $this->lesson();
        $text = $service->defaultText($lesson);
        $this->assertStringContainsString('Amati gerak. Baca contoh.', $text);
        $this->assertStringContainsString('Selamat belajar.', $text);
        foreach (['https', '##', '**', 'Sumber belajar', 'Rujukan', 'lisensi', 'Kegiatan apa?', 'RAHASIA'] as $excluded) {
            $this->assertStringNotContainsString($excluded, $text);
        }
        $old = ContentAudio::factory()->create(['lesson_id' => $lesson->id]);
        $old->update(['source_hash' => hash('sha256', json_encode([$lesson->title, $lesson->content], JSON_THROW_ON_ERROR))]);
        $this->assertFalse($service->isCurrent($old));
    }

    public function test_admin_can_generate_each_exercise_separately_without_answers_or_creating_audio_on_get(): void
    {
        $lesson = $this->lesson();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $url = route('admin.audio.lessons.edit', ['lesson' => $lesson, 'slot' => 1]);
        $this->get($url)->assertOk()->assertReact(fn (Assert $page) => $page
            ->where('text', "Kegiatan apa?\nA. Nyampat\nB. Macukur")->has('targets', 3)->etc());
        $this->get(route('admin.audio.lessons.edit', ['lesson' => $lesson, 'slot' => 2]))->assertReact(fn (Assert $page) => $page->where('text', 'Tulis kegiatan.')->etc());
        $this->assertDatabaseCount('content_audio', 0);
        config(['services.tts.enabled' => true, 'services.tts.engine' => 'espeak', 'services.tts.binary' => PHP_BINARY]);
        foreach ([0, 1, 2] as $slot) {
            $this->post(route('admin.audio.lessons.store', ['lesson' => $lesson, 'slot' => $slot]), [
                'spoken_text' => app(ContentAudioService::class)->defaultText($lesson, $slot),
                'language' => 'id', 'speed' => 150, 'action' => 'generate',
            ])->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('content_audio', 3);
        $this->assertSame(0, $lesson->fresh()->audio->lesson_audio_slot);
        Queue::assertPushed(GenerateContentAudio::class, 3);
        $this->get(route('admin.audio.lessons.edit', ['lesson' => $lesson, 'slot' => 3]))->assertNotFound();
        $this->assertDatabaseCount('content_audio', 3);
    }

    public function test_only_reviewed_current_exercise_audio_is_exposed_and_future_audio_is_not_accessible(): void
    {
        $lesson = $this->lesson();
        $user = User::factory()->create();
        $device = (string) Str::uuid();
        $this->actingAs($user)->withCookie('artaswaha_device', $device);
        $audios = [];
        foreach ([1, 2] as $slot) {
            $path = 'content-audio/question-'.$slot.'.wav';
            Storage::disk('local')->put($path, 'test audio');
            $audios[$slot] = ContentAudio::factory()->create(['lesson_id' => $lesson->id, 'lesson_audio_slot' => $slot,
                'status' => 'ready', 'reviewed_at' => now(), 'path' => $path]);
        }
        $first = route('content-audio.show', $audios[1]);
        $second = route('content-audio.show', $audios[2]);
        $this->get(route('learning.lessons.show', $lesson))->assertReact(fn (Assert $page) => $page
            ->where('quiz.question.audio.url', $first)->missing('quiz.question.correct_option')->etc());
        $this->get($first)->assertOk();
        $this->get($second)->assertNotFound();
        $this->post(route('learning.exercises.answer', $lesson), ['question_index' => 0, 'answer_index' => 1])->assertRedirect();
        $this->get(route('learning.lessons.show', $lesson))->assertReact(fn (Assert $page) => $page->where('quiz.question.audio.url', $second)->etc());
        $this->get($first)->assertNotFound();
        $this->get($second)->assertOk();
        $audios[2]->update(['reviewed_at' => null, 'status' => 'review']);
        $this->get(route('learning.lessons.show', $lesson))->assertReact(fn (Assert $page) => $page->where('quiz.question.audio', null)->etc());
        $this->get($second)->assertNotFound();
    }

    public function test_question_edits_or_removal_invalidate_only_the_affected_audio(): void
    {
        $lesson = $this->lesson();
        $service = app(ContentAudioService::class);
        $material = ContentAudio::factory()->create(['lesson_id' => $lesson->id]);
        $question = ContentAudio::factory()->create(['lesson_id' => $lesson->id, 'lesson_audio_slot' => 1]);
        $essay = ContentAudio::factory()->create(['lesson_id' => $lesson->id, 'lesson_audio_slot' => 2]);
        $questions = $lesson->exercises;
        $questions[0]['exercise_prompt'] = 'Pertanyaan baru';
        $lesson->update(['exercises' => $questions]);
        $this->assertTrue($service->isCurrent($material->fresh()));
        $this->assertFalse($service->isCurrent($question->fresh()));
        $this->assertTrue($service->isCurrent($essay->fresh()));
        $lesson->update(['exercises' => [$questions[0]]]);
        $this->assertFalse($service->isCurrent($essay->fresh()));
        $this->actingAs(User::factory()->create())->get(route('admin.audio.lessons.edit', ['lesson' => $lesson, 'slot' => 1]))->assertForbidden();
    }
}
