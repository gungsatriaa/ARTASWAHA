<?php

namespace Tests\Feature;

use App\Jobs\GenerateQuizDraft;
use App\Models\QuizDraft;
use App\Models\User;
use App\Services\OcrService;
use App\Services\QuizDraftGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuizDraftFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_is_queued_and_draft_status_is_private(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $response = $this->actingAs($owner)->postJson(route('quizzes.drafts.store'), ['file' => UploadedFile::fake()->create('lesson.pdf', 100, 'application/pdf')]);
        $draft = QuizDraft::query()->firstOrFail();
        $response->assertAccepted()->assertJsonPath('id', $draft->id);
        Queue::assertPushed(GenerateQuizDraft::class);
        $this->actingAs($other)->getJson(route('quizzes.drafts.show', $draft))->assertNotFound();

        $this->actingAs($owner)->getJson(route('quizzes.drafts.show', $draft))->assertOk()->assertJsonPath('status', 'queued');
    }

    public function test_invalid_file_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())->postJson(route('quizzes.drafts.store'), ['file' => UploadedFile::fake()->create('bad.txt', 10, 'text/plain')])->assertUnprocessable();
    }

    public function test_ocr_text_discards_obvious_noise_line_and_control_characters(): void
    {
        config(['services.ocr.url' => 'http://ocr.test']);
        Http::fake(['ocr.test/*' => Http::response(['success' => true, 'text' => "nen ne nn ne An EEE IIE EE\n1. Ape ke anak cenik maid cacing?\nJa\x01waban: jaum misi benang"], 200)]);
        $file = UploadedFile::fake()->create('lesson.png');

        $text = app(OcrService::class)->extractText($file->getPathname(), $file->getClientOriginalName());

        $this->assertStringNotContainsString('nen ne nn ne', $text);
        $this->assertStringContainsString('1. Ape ke anak cenik maid cacing?', $text);
        $this->assertStringContainsString('Jawaban: jaum misi benang', $text);
        $this->assertStringNotContainsString("\x01", $text);
    }

    public function test_job_saves_generated_questions_and_deletes_source_file(): void
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->image('source.png')->store('quiz-drafts/1', 'local');
        $draft = QuizDraft::create(['user_id' => User::factory()->create()->id, 'source_path' => $path]);
        $this->mock(OcrService::class)->shouldReceive('extractText')->once()->andReturn('Materi sumber.');
        $this->mock(QuizDraftGenerator::class)->shouldReceive('generate')->once()->with('Materi sumber.')->andReturn([['prompt' => 'Tanya?', 'options' => ['A', 'B'], 'correct_option' => 0, 'explanation' => '']]);

        (new GenerateQuizDraft($draft->id))->handle();

        $this->assertDatabaseHas('quiz_drafts', ['id' => $draft->id, 'status' => 'completed']);
        $this->assertSame('Tanya?', $draft->fresh()->questions[0]['prompt']);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_failed_job_marks_draft_failed_and_deletes_source_file(): void
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->image('source.png')->store('quiz-drafts/1', 'local');
        $draft = QuizDraft::create(['user_id' => User::factory()->create()->id, 'source_path' => $path]);
        $this->mock(OcrService::class)->shouldReceive('extractText')->once()->andThrow(new \RuntimeException('OCR timeout'));

        (new GenerateQuizDraft($draft->id))->handle();

        $this->assertDatabaseHas('quiz_drafts', ['id' => $draft->id, 'status' => 'failed']);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_ai_generator_sends_child_friendly_source_bound_prompt_and_parses_questions(): void
    {
        config(['services.ai.url' => 'http://router.test/v1', 'services.ai.key' => 'test-key', 'services.ai.model' => 'test/model']);
        Http::fake(['router.test/*' => Http::response(['choices' => [['message' => ['content' => json_encode(['questions' => [['prompt' => 'Apa maknanya?', 'options' => ['Makna A', 'Makna B'], 'correct_option' => 1, 'explanation' => 'Sesuai sumber.']]])]]]], 200)]);

        $questions = app(QuizDraftGenerator::class)->generate('Materi tentang paribasa.');

        $this->assertSame('Apa maknanya?', $questions[0]['prompt']);
        Http::assertSent(fn ($request): bool => str_contains($request['messages'][0]['content'], 'ramah anak')
            && str_contains($request['messages'][0]['content'], 'JSON valid')
            && $request['stream'] === false
            && $request['response_format'] === ['type' => 'json_object']
            && str_contains($request['messages'][1]['content'], 'Materi tentang paribasa.'));
    }

    public function test_invalid_ai_response_is_included_in_bounded_diagnostic_exception(): void
    {
        config(['services.ai.url' => 'http://router.test/v1', 'services.ai.key' => 'test-key', 'services.ai.model' => 'test/model']);
        Http::fake(['router.test/*' => Http::response(['choices' => [['message' => ['content' => 'respons tidak valid']]]], 200)]);

        try {
            app(QuizDraftGenerator::class)->generate('Materi sumber.');
            $this->fail('Expected invalid AI response to throw.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('HTTP 200', $exception->getMessage());
            $this->assertStringContainsString('respons tidak valid', $exception->getMessage());
        }
    }
}
