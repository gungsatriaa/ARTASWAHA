<?php

namespace Tests\Feature;

use App\Jobs\GenerateQuizDraft;
use App\Models\QuizDraft;
use App\Models\User;
use App\Services\OcrService;
use App\Services\QuizDraftGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
}
