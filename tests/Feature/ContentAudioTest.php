<?php

namespace Tests\Feature;

use App\Jobs\GenerateContentAudio;
use App\Models\ContentAudio;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\ContentAudioService;
use App\Services\QuizService;
use App\Services\RoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ContentAudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Process::fake();
        config(['services.tts.enabled' => false, 'services.tts.engine' => 'espeak', 'services.tts.binary' => '']);
        $this->withCredentials();
    }

    public function test_admin_audio_pages_render_and_get_does_not_create_audio(): void
    {
        $lesson = Lesson::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach (['records', 'modules', 'questions'] as $type) {
            $this->get(route('admin.audio.index', ['type' => $type]))->assertOk()->assertSee('Audio pembelajaran');
        }
        $this->get(route('admin.audio.lessons.edit', $lesson))->assertOk()->assertSee('Teks yang dibacakan')->assertSee('Gunakan rekaman');
        $this->assertDatabaseCount('content_audio', 0);
        Process::assertNothingRan();
    }

    public function test_upload_requires_review_then_supports_public_playback_and_byte_ranges(): void
    {
        $lesson = Lesson::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post(route('admin.audio.lessons.store', $lesson), [...$this->data(), 'action' => 'upload', 'audio_upload' => UploadedFile::fake()->createWithContent('voice.wav', $this->wav())])->assertRedirect();
        $audio = ContentAudio::firstOrFail();
        $this->assertSame('review', $audio->status);
        Storage::disk('local')->assertExists($audio->path);
        $this->get(route('content-audio.show', $audio))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('content-audio.show', $audio))->assertNotFound();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post(route('admin.audio.approve', $audio), ['version' => $audio->version])->assertRedirect();
        $this->assertSame('ready', $audio->fresh()->status);
        $this->actingAs(User::factory()->create())->get(route('content-audio.show', $audio))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->withHeader('Range', 'bytes=0-11')->get(route('content-audio.show', $audio))->assertStatus(206)->assertHeader('Content-Range', 'bytes 0-11/60');
        $this->flushHeaders();
        $this->get(route('learning.lessons.show', $lesson->slug))->assertOk()->assertSee('Dengarkan materi');
    }

    public function test_invalid_upload_and_unsupported_bali_generation_are_rejected(): void
    {
        $lesson = Lesson::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post(route('admin.audio.lessons.store', $lesson), [...$this->data(), 'action' => 'upload', 'audio_upload' => UploadedFile::fake()->create('bad.pdf', 5, 'application/pdf')])->assertSessionHasErrors('audio_upload');
        $this->post(route('admin.audio.lessons.store', $lesson), [...$this->data(), 'language' => 'ban', 'action' => 'generate'])->assertSessionHasErrors('language');
        $this->assertDatabaseCount('content_audio', 0);
        Process::assertNothingRan();
    }

    public function test_missing_engine_does_not_block_saving_or_queue_work(): void
    {
        Queue::fake([GenerateContentAudio::class]);
        $lesson = Lesson::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post(route('admin.audio.lessons.store', $lesson), [...$this->data(), 'action' => 'generate'])->assertRedirect();
        $this->assertSame('unavailable', ContentAudio::firstOrFail()->status);
        Queue::assertNotPushed(GenerateContentAudio::class);
        Process::assertNothingRan();
    }

    public function test_generation_is_deduplicated_and_synthesis_uses_stdin_not_shell_interpolation(): void
    {
        Queue::fake([GenerateContentAudio::class]);
        config(['services.tts.enabled' => true, 'services.tts.binary' => PHP_BINARY, 'services.tts.data_path' => '/test/espeak-ng-data']);
        $lesson = Lesson::factory()->create();
        $service = app(ContentAudioService::class);
        $data = [...$this->data(), 'spoken_text' => 'Teks; $(jangan jalankan) --help', 'action' => 'generate'];
        $audio = $service->save($lesson, $data);
        $service->save($lesson, [...$data, 'version' => $audio->version]);
        Queue::assertPushed(GenerateContentAudio::class, 1);
        Process::fake(function ($process) {
            file_put_contents($process->command[array_search('-w', $process->command, true) + 1], $this->wav());

            return Process::result(exitCode: 0);
        });

        (new GenerateContentAudio($audio->id, $audio->version))->handle($service);

        $this->assertSame('review', $audio->fresh()->status);
        $this->assertNull($audio->fresh()->reviewed_at);
        Process::assertRan(fn ($process): bool => is_array($process->command) && $process->input === $data['spoken_text'] && ! in_array($data['spoken_text'], $process->command, true));
        Process::assertRan(fn ($process): bool => ($process->environment['ESPEAK_DATA_PATH'] ?? null) === '/test/espeak-ng-data');
    }

    public function test_neural_generation_replaces_espeak_audio_and_uses_only_local_model_files(): void
    {
        Queue::fake([GenerateContentAudio::class]);
        $lesson = Lesson::factory()->create();
        $audio = $this->ready($lesson);
        $audio->update(['engine' => 'espeak', 'spoken_text' => $this->data()['spoken_text'], 'language' => 'id', 'speed' => 150]);
        $modelDirectory = Storage::disk('local')->path('mms-model');
        foreach (['config.json', 'model.safetensors', 'vocab.json'] as $file) {
            Storage::disk('local')->put('mms-model/'.$file, '{}');
        }
        config(['services.tts.enabled' => true, 'services.tts.engine' => 'mms', 'services.tts.python' => PHP_BINARY, 'services.tts.model_path' => $modelDirectory]);
        $service = app(ContentAudioService::class);
        $replacement = $service->save($lesson, [...$this->data(), 'action' => 'generate', 'version' => $audio->version]);
        $this->assertSame('mms', $replacement->engine);
        $this->assertSame('queued', $replacement->status);
        Queue::assertPushed(GenerateContentAudio::class, 1);
        Process::fake(function ($process) {
            file_put_contents($process->command[array_search('-w', $process->command, true) + 1], $this->wav());

            return Process::result(exitCode: 0);
        });

        (new GenerateContentAudio($replacement->id, $replacement->version))->handle($service);

        $this->assertSame('review', $replacement->fresh()->status);
        $this->assertNull($replacement->fresh()->reviewed_at);
        Process::assertRan(fn ($process): bool => $process->command[0] === PHP_BINARY
            && $process->command[1] === base_path('app/Services/tts_mms.py')
            && $process->command[3] === $modelDirectory
            && $process->input === $this->data()['spoken_text']
            && $process->environment['HF_HUB_OFFLINE'] === '1'
            && $process->environment['TRANSFORMERS_OFFLINE'] === '1');
    }

    public function test_chatterbox_cli_generates_review_audio_without_http_or_shell_interpolation(): void
    {
        Http::preventStrayRequests();
        Queue::fake([GenerateContentAudio::class]);
        $directory = Storage::disk('local')->path('chatterbox model');
        foreach (['manifest.json', 'base/ve.pt', 'base/s3gen.pt', 'base/tokenizer.json', 'base/conds.pt', 'indonesian/t3_cfg.safetensors'] as $file) {
            Storage::disk('local')->put('chatterbox model/'.$file, '{}');
        }
        Storage::disk('local')->put('suara indonesia.wav', $this->wav());
        $reference = Storage::disk('local')->path('suara indonesia.wav');
        config(['services.tts.enabled' => true, 'services.tts.engine' => 'chatterbox', 'services.tts.python' => PHP_BINARY, 'services.tts.chatterbox_path' => $directory, 'services.tts.device' => 'cpu', 'services.tts.reference_audio' => $reference, 'services.tts.cpu_threads' => 8]);
        $service = app(ContentAudioService::class);
        $data = [...$this->data(), 'spoken_text' => 'Narasi; $(jangan jalankan)', 'action' => 'generate'];
        $audio = $service->save(Lesson::factory()->create(), $data);
        $service->save($audio->lesson, [...$data, 'version' => $audio->version]);
        Process::fake(function ($process) {
            file_put_contents($process->command[array_search('-w', $process->command, true) + 1], $this->wav());

            return Process::result(exitCode: 0);
        });

        (new GenerateContentAudio($audio->id, $audio->version))->handle($service);

        $this->assertSame('chatterbox', $audio->fresh()->engine);
        $this->assertSame('review', $audio->fresh()->status);
        $this->assertNull($audio->fresh()->reviewed_at);
        Storage::disk('local')->assertExists($audio->fresh()->path);
        Process::assertRan(fn ($process): bool => $process->command[0] === PHP_BINARY
            && $process->command[1] === base_path('app/Services/tts_chatterbox.py')
            && $process->command[2] === 'synthesize' && $process->command[4] === $directory
            && $process->command[6] === 'cpu' && $process->input === $data['spoken_text']
            && array_slice($process->command, -2) === ['--reference-audio', $reference]
            && $process->environment['TTS_CPU_THREADS'] === '8'
            && ! in_array($data['spoken_text'], $process->command, true)
            && $process->timeout === 240 && $process->environment['HF_HUB_OFFLINE'] === '1');
        Queue::assertPushed(GenerateContentAudio::class, 1);
        Http::assertNothingSent();
    }

    public function test_chatterbox_incomplete_assets_or_invalid_device_cannot_queue_and_other_languages_are_rejected(): void
    {
        Http::preventStrayRequests();
        Queue::fake([GenerateContentAudio::class]);
        $lesson = Lesson::factory()->create();
        $directory = Storage::disk('local')->path('chatterbox');
        config(['services.tts.enabled' => true, 'services.tts.engine' => 'chatterbox', 'services.tts.python' => PHP_BINARY, 'services.tts.chatterbox_path' => $directory, 'services.tts.device' => 'cpu']);
        $service = app(ContentAudioService::class);
        $audio = $service->save($lesson, [...$this->data(), 'action' => 'generate']);
        $this->assertSame('unavailable', $audio->status);
        foreach (['manifest.json', 'base/ve.pt', 'base/s3gen.pt', 'base/tokenizer.json', 'base/conds.pt', 'indonesian/t3_cfg.safetensors'] as $file) {
            Storage::disk('local')->put('chatterbox/'.$file, '{}');
        }
        config(['services.tts.reference_audio' => $directory.'/missing.wav']);
        $this->assertFalse($service->isConfigured());
        config(['services.tts.reference_audio' => '']);
        $this->assertTrue($service->isConfigured());
        config(['services.tts.device' => 'unsupported']);
        $audio = $service->save($lesson, [...$this->data(), 'action' => 'generate', 'version' => $audio->version]);
        $this->assertSame('unavailable', $audio->status);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post(route('admin.audio.lessons.store', $lesson), [...$this->data(), 'action' => 'generate', 'language' => 'en', 'version' => $audio->version])->assertSessionHasErrors('language');
        Queue::assertNotPushed(GenerateContentAudio::class);
        Process::assertNothingRan();
        Http::assertNothingSent();
    }

    public function test_internal_tts_api_generates_private_audio_without_local_models(): void
    {
        Http::preventStrayRequests();
        Http::fake(['http://tts.internal:8001/v1/audio/speech' => Http::response($this->wav(), 200, ['Content-Type' => 'audio/wav'])]);
        Queue::fake([GenerateContentAudio::class]);
        config(['services.tts.enabled' => true, 'services.tts.engine' => 'openai', 'services.tts.url' => 'http://tts.internal:8001/v1', 'services.tts.key' => 'test-private-key', 'services.tts.model' => 'indonesian-model', 'services.tts.voice' => 'teacher']);
        $service = app(ContentAudioService::class);
        $audio = $service->save(Lesson::factory()->create(), [...$this->data(), 'action' => 'generate']);

        (new GenerateContentAudio($audio->id, $audio->version))->handle($service);

        $this->assertSame('openai', $audio->fresh()->engine);
        $this->assertSame('review', $audio->fresh()->status);
        $this->assertNull($audio->fresh()->reviewed_at);
        Storage::disk('local')->assertExists($audio->fresh()->path);
        Http::assertSent(fn ($request): bool => $request->url() === 'http://tts.internal:8001/v1/audio/speech'
            && $request->hasHeader('Authorization', 'Bearer test-private-key')
            && $request['model'] === 'indonesian-model' && $request['voice'] === 'teacher'
            && $request['input'] === $this->data()['spoken_text'] && $request['response_format'] === 'wav'
            && (float) $request['speed'] === 1.0);
        Http::assertSentCount(1);
        Queue::assertPushed(GenerateContentAudio::class, 1);
        Process::assertNothingRan();
    }

    public function test_tts_api_without_valid_url_model_or_voice_does_not_queue_generation(): void
    {
        Http::preventStrayRequests();
        Queue::fake([GenerateContentAudio::class]);
        config(['services.tts.enabled' => true, 'services.tts.engine' => 'openai']);
        $lesson = Lesson::factory()->create();
        $audio = null;
        foreach ([['file:///models', 'model', 'voice'], ['http://tts.internal/v1', '', 'voice'], ['http://tts.internal/v1', 'model', '']] as [$url, $model, $voice]) {
            config(['services.tts.url' => $url, 'services.tts.model' => $model, 'services.tts.voice' => $voice]);
            $audio = app(ContentAudioService::class)->save($lesson, [...$this->data(), 'action' => 'generate', 'version' => $audio?->version]);
            $this->assertSame('unavailable', $audio->status);
        }
        Queue::assertNotPushed(GenerateContentAudio::class);
        Http::assertNothingSent();
        Process::assertNothingRan();
    }

    public function test_tts_api_error_timeout_and_invalid_audio_are_cleaned_up(): void
    {
        Http::preventStrayRequests();
        Queue::fake([GenerateContentAudio::class]);
        config(['services.tts.enabled' => true, 'services.tts.engine' => 'openai', 'services.tts.url' => 'http://tts.internal/v1', 'services.tts.key' => '', 'services.tts.model' => 'model', 'services.tts.voice' => 'teacher']);
        foreach ([Http::response('Provider error', 503), Http::response('{"status":"ok"}', 200), Http::failedConnection()] as $response) {
            Http::fake(['http://tts.internal/v1/audio/speech' => $response]);
            $audio = app(ContentAudioService::class)->save(Lesson::factory()->create(), [...$this->data(), 'action' => 'generate']);
            $job = new GenerateContentAudio($audio->id, $audio->version);
            try {
                $job->handle(app(ContentAudioService::class));
                $this->fail('Invalid API output must not become playable.');
            } catch (\Exception $exception) {
                $job->failed($exception);
            }
            $this->assertSame('failed', $audio->fresh()->status);
            $this->assertNull($audio->fresh()->path);
            $this->assertSame([], Storage::disk('local')->allFiles('content-audio'));
        }
        Http::assertSentCount(1);
        Queue::assertPushed(GenerateContentAudio::class, 3);
        Process::assertNothingRan();
    }

    public function test_neural_model_missing_files_is_unavailable_and_unsupported_language_is_rejected(): void
    {
        Queue::fake([GenerateContentAudio::class]);
        config(['services.tts.enabled' => true, 'services.tts.engine' => 'mms', 'services.tts.python' => PHP_BINARY, 'services.tts.model_path' => Storage::disk('local')->path('missing-model')]);
        $lesson = Lesson::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post(route('admin.audio.lessons.store', $lesson), [...$this->data(), 'action' => 'generate'])->assertRedirect();
        $audio = ContentAudio::firstOrFail();
        $this->assertSame('unavailable', $audio->status);
        $this->post(route('admin.audio.lessons.store', $lesson), [...$this->data(), 'action' => 'generate', 'language' => 'en', 'version' => $audio->version])->assertSessionHasErrors('language');
        Queue::assertNotPushed(GenerateContentAudio::class);
        Process::assertNothingRan();
    }

    public function test_old_job_cannot_overwrite_a_new_recording(): void
    {
        Queue::fake([GenerateContentAudio::class]);
        config(['services.tts.enabled' => true, 'services.tts.binary' => PHP_BINARY]);
        $lesson = Lesson::factory()->create();
        $service = app(ContentAudioService::class);
        $audio = $service->save($lesson, [...$this->data(), 'action' => 'generate']);
        $job = new GenerateContentAudio($audio->id, $audio->version);
        $replacement = $service->save($lesson, [...$this->data(), 'version' => $audio->version, 'action' => 'upload'], UploadedFile::fake()->createWithContent('new.wav', $this->wav()));

        $job->handle($service);

        $this->assertSame($replacement->path, $audio->fresh()->path);
        Process::assertNothingRan();
    }

    public function test_material_changes_invalidate_approved_audio_and_stale_approval_is_rejected(): void
    {
        $lesson = Lesson::factory()->create();
        $audio = $this->ready($lesson);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->put(route('admin.lessons.update', $lesson), ['learning_path_id' => $lesson->learning_path_id, 'title' => $lesson->title, 'slug' => $lesson->slug, 'summary' => $lesson->summary, 'content' => 'Isi yang diperbarui.', 'sort_order' => 1])->assertRedirect();
        $this->assertSame('stale', $audio->fresh()->status);
        $this->post(route('admin.audio.approve', $audio), ['version' => $audio->version])->assertConflict();
        $this->actingAs(User::factory()->create())->get(route('content-audio.show', $audio))->assertNotFound();
    }

    public function test_failed_process_cleans_temporary_files_and_keeps_learning_available(): void
    {
        config(['services.tts.enabled' => true, 'services.tts.binary' => PHP_BINARY]);
        Queue::fake([GenerateContentAudio::class]);
        $lesson = Lesson::factory()->create();
        $service = app(ContentAudioService::class);
        $audio = $service->save($lesson, [...$this->data(), 'action' => 'generate']);
        Process::fake(function ($process) {
            file_put_contents($process->command[array_search('-w', $process->command, true) + 1], 'invalid output');

            return Process::result(exitCode: 1);
        });
        $job = new GenerateContentAudio($audio->id, $audio->version);
        try {
            $job->handle($service);
            $this->fail('A failed synthesizer must not produce usable audio.');
        } catch (RuntimeException $exception) {
            $job->failed($exception);
        }
        $this->assertSame('failed', $audio->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles('content-audio'));
        $this->get(route('learning.lessons.show', $lesson->slug))->assertOk()->assertDontSee('data-content-audio', false);
    }

    public function test_question_narration_excludes_answer_key_and_future_audio_is_not_accessible(): void
    {
        $host = User::factory()->create();
        $player = User::factory()->create();
        $quiz = Quiz::factory()->create(['user_id' => $host->id]);
        $first = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'sort_order' => 1, 'explanation' => 'PRIVATE ANSWER EXPLANATION']);
        $second = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'sort_order' => 2]);
        $firstAudio = $this->ready($first);
        $secondAudio = $this->ready($second);
        $text = app(ContentAudioService::class)->defaultText($first);
        $this->assertStringNotContainsString('PRIVATE ANSWER EXPLANATION', $text);
        $rooms = app(RoomService::class);
        $room = $rooms->create($quiz, $host);
        $rooms->join($room->code, $player);
        $rooms->start($room, $host);

        $this->actingAs($player)->get(route('rooms.show', $room->code))->assertOk()->assertSee('Dengarkan soal dan pilihan jawaban');
        $this->get(route('content-audio.show', ['contentAudio' => $firstAudio, 'room' => $room->code]))->assertOk();
        $this->get(route('content-audio.show', ['contentAudio' => $secondAudio, 'room' => $room->code]))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('content-audio.show', ['contentAudio' => $firstAudio, 'room' => $room->code]))->assertForbidden();
    }

    public function test_anonymous_participant_can_play_only_their_current_question(): void
    {
        $host = User::factory()->create();
        $quiz = Quiz::factory()->create(['user_id' => $host->id]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        $audio = $this->ready($question);
        $path = LearningPath::factory()->create();
        $profile = LearnerProfile::create(['visitor_id' => (string) Str::uuid(), 'learning_path_id' => $path->id, 'learning_path_ids' => [$path->id], 'role' => 'murid', 'name' => 'Anonim', 'mode' => 'anonymous']);
        $rooms = app(RoomService::class);
        $room = $rooms->create($quiz, $host);
        $rooms->join($room->code, $profile);
        $rooms->start($room, $host);

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('content-audio.show', ['contentAudio' => $audio, 'room' => $room->code]))->assertOk();
    }

    public function test_replacing_unplayed_quiz_cleans_obsolete_audio(): void
    {
        $owner = User::factory()->create();
        $quiz = Quiz::factory()->create(['user_id' => $owner->id]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        $audio = $this->ready($question);
        app(QuizService::class)->update($quiz, $owner, ['title' => 'Updated', 'questions' => [['prompt' => 'Baru?', 'options' => ['A', 'B'], 'correct_option' => 0]]]);
        $this->assertDatabaseMissing('content_audio', ['id' => $audio->id]);
        Storage::disk('local')->assertMissing($audio->path);
    }

    /** @return array{spoken_text: string, language: string, speed: int, action: string} */
    private function data(): array
    {
        return ['spoken_text' => 'Narasi yang sudah diperiksa.', 'language' => 'id', 'speed' => 150, 'action' => 'save'];
    }

    private function ready(Lesson|QuizQuestion $content): ContentAudio
    {
        $audio = app(ContentAudioService::class)->save($content, [...$this->data(), 'action' => 'upload'], UploadedFile::fake()->createWithContent('voice.wav', $this->wav()));
        app(ContentAudioService::class)->approve($audio, $audio->version);

        return $audio->fresh();
    }

    private function wav(): string
    {
        return 'RIFF'.pack('V', 52).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 22050, 44100, 2, 16).'data'.pack('V', 16).str_repeat("\0", 16);
    }
}
