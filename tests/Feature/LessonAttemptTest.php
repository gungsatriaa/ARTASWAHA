<?php

namespace Tests\Feature;

use App\Models\LearnerProfile;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\LessonAttempt;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\LearnerInterviewAnalyzer;
use App\Services\LearningProgressService;
use App\Services\LessonAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LessonAttemptTest extends TestCase
{
    use RefreshDatabase;

    private function lesson(): Lesson
    {
        return Lesson::factory()->create(['exercises' => [
            ['section' => 0, 'question_type' => 'single', 'exercise_prompt' => 'Pilih Ha', 'exercise_options' => ['Ha', 'Na'], 'correct_option' => 0, 'exercise_explanation' => 'Ha adalah jawaban.'],
            ['section' => 0, 'question_type' => 'multiple', 'exercise_prompt' => 'Pilih dua', 'exercise_options' => ['Ha', 'Na', 'Ca'], 'correct_options' => [0, 2]],
            ['section' => 1, 'question_type' => 'essay', 'exercise_prompt' => 'Tulis Ha', 'accepted_answers' => ['ᬳ']],
        ]]);
    }

    public function test_wrong_answers_advance_all_types_and_results_are_only_revealed_at_the_end(): void
    {
        $lesson = $this->lesson();
        $this->actingAs(User::factory()->create())->withCookie('artaswaha_device', (string) Str::uuid());
        $this->post(route('learning.exercises.answer', $lesson), ['question_index' => 0, 'answer_index' => 1])->assertRedirect()->assertSessionMissing('lesson_feedback');
        $attempt = LessonAttempt::sole();
        $this->assertFalse($attempt->answers[0]['is_correct']);
        $this->get(route('learning.lessons.show', $lesson))->assertInertia(fn (Assert $page): Assert => $page
            ->where('quiz.index', 1)->where('quiz.results', [])->where('quiz.correct', null)->where('quiz.completed', false)
            ->missing('quiz.question.correct_options')->missing('lesson.exercises')->missing('lesson.exercise_explanation'));
        $this->post(route('learning.exercises.answer', $lesson), ['attempt_id' => $attempt->id, 'question_index' => 1, 'answer_indexes' => []])->assertSessionHasErrors('answer_indexes');
        $this->assertCount(1, $attempt->fresh()->answers);
        $this->post(route('learning.exercises.answer', $lesson), ['attempt_id' => $attempt->id, 'question_index' => 1, 'answer_indexes' => [1]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('learning_progress', 0);
        $this->post(route('learning.exercises.answer', $lesson), ['attempt_id' => $attempt->id, 'question_index' => 2, 'answer_text' => 'ᬳ'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('learning_progress', 1);
        $this->get(route('learning.lessons.show', $lesson))->assertInertia(fn (Assert $page): Assert => $page
            ->where('quiz.completed', true)->where('quiz.correct', 1)->has('quiz.results', 3)
            ->where('quiz.results.0.selected', 'Na')->where('quiz.results.0.correct', 'Ha')->where('quiz.results.0.is_correct', false));
        $this->post(route('learning.exercises.answer', $lesson), ['question_index' => 2, 'answer_text' => 'ᬳ'])->assertConflict();
    }

    public function test_retry_creates_a_new_attempt_preserves_old_answers_and_does_not_award_extra_points(): void
    {
        $lesson = Lesson::factory()->create(['question_type' => 'single', 'exercise_prompt' => 'Ha?', 'exercise_options' => ['Ha', 'Na'], 'correct_option' => 0]);
        $user = User::factory()->create();
        $this->actingAs($user)->withCookie('artaswaha_device', (string) Str::uuid());
        $this->post(route('learning.exercises.answer', $lesson), ['answer_index' => 1])->assertRedirect();
        $first = LessonAttempt::sole();
        $snapshot = $first->getAttributes();
        $this->post(route('learning.exercises.retry', $lesson), ['attempt_id' => $first->id])->assertRedirect();
        $second = LessonAttempt::latest('id')->first();
        $this->assertNotSame($first->id, $second->id);
        $this->get(route('learning.lessons.show', $lesson))->assertInertia(fn (Assert $page): Assert => $page->where('quiz.completed', false)->where('quiz.index', 0)->where('quiz.results', []));
        $this->post(route('learning.exercises.retry', $lesson), ['attempt_id' => $first->id])->assertConflict();
        $this->post(route('learning.exercises.answer', $lesson), ['attempt_id' => $first->id, 'answer_index' => 0])->assertConflict();
        $this->post(route('learning.exercises.answer', $lesson), ['attempt_id' => $second->id, 'answer_index' => 0])->assertRedirect();
        $this->assertSame($snapshot, $first->fresh()->getAttributes());
        $this->assertTrue($second->fresh()->answers[0]['is_correct']);
        $this->assertSame(10, LearningProgress::sum('points'));
        $this->assertDatabaseCount('learning_progress', 1);
        $this->get(route('learning.lessons.show', $lesson))->assertInertia(fn (Assert $page): Assert => $page->has('quiz.history', 2)->where('quiz.correct', 1));
    }

    public function test_partial_attempt_resumes_on_another_device_for_the_same_account_but_is_not_shared(): void
    {
        $lesson = $this->lesson();
        $user = User::factory()->create();
        $this->actingAs($user)->withCookie('artaswaha_device', (string) Str::uuid());
        $this->post(route('learning.exercises.answer', $lesson), ['answer_index' => 0])->assertRedirect();
        $attempt = LessonAttempt::sole();
        $this->flushSession();
        $this->actingAs($user)->withCookie('artaswaha_device', (string) Str::uuid());
        $this->get(route('learning.lessons.show', $lesson))->assertInertia(fn (Assert $page): Assert => $page->where('quiz.id', $attempt->id)->where('quiz.index', 1));
        $this->actingAs(User::factory()->create());
        $this->get(route('learning.lessons.show', $lesson))->assertInertia(fn (Assert $page): Assert => $page->where('quiz.id', null)->where('quiz.index', 0)->where('quiz.history', []));
        $this->post(route('learning.exercises.answer', $lesson), ['attempt_id' => $attempt->id, 'answer_index' => 0])->assertConflict();
    }

    public function test_anonymous_attempts_are_isolated_claimed_on_login_and_deleted_with_the_identity(): void
    {
        $device = (string) Str::uuid();
        $otherDevice = (string) Str::uuid();
        $lesson = $this->lesson();
        $attempt = LessonAttempt::factory()->for($lesson)->create(['visitor_id' => $device, 'version' => $lesson->exerciseVersion()]);
        $other = LessonAttempt::factory()->for($lesson)->create(['visitor_id' => $otherDevice, 'version' => $lesson->exerciseVersion()]);
        $service = app(LessonAttemptService::class);
        $this->assertSame($other->id, $service->latest($lesson, $otherDevice, null)->id);
        $this->assertSame($attempt->id, $service->latest($lesson, $device, null)->id);
        $user = User::factory()->create();
        $request = Request::create('/', 'GET', [], ['artaswaha_device' => $device]);
        app(LearningProgressService::class)->claimDeviceProgress($request, $user);
        $this->assertSame($user->id, $attempt->fresh()->user_id);
        $this->assertNull($other->fresh()->user_id);
        app(AccountDeletionService::class)->delete($user, 'test');
        $this->assertModelMissing($attempt);
        $this->assertModelExists($other);
        $profile = LearnerProfile::create(['visitor_id' => $otherDevice, 'learning_path_id' => $lesson->learning_path_id, 'name' => 'Anonim', 'role' => 'murid', 'mode' => 'anonymous']);
        app(AccountDeletionService::class)->delete($profile, 'test');
        $this->assertModelMissing($other);
    }

    public function test_bank_edits_preserve_old_partial_answers_and_restart_at_the_first_question(): void
    {
        $lesson = $this->lesson();
        $user = User::factory()->create();
        $this->actingAs($user)->withCookie('artaswaha_device', (string) Str::uuid());
        $this->post(route('learning.exercises.answer', $lesson), ['answer_index' => 1])->assertRedirect();
        $old = LessonAttempt::sole();
        $questions = $lesson->exercises;
        $questions[0]['exercise_prompt'] = 'Pertanyaan diperbarui';
        $lesson->update(['exercises' => $questions]);
        $this->get(route('learning.lessons.show', $lesson))->assertInertia(fn (Assert $page): Assert => $page->where('quiz.index', 0)->where('quiz.id', null));
        $this->post(route('learning.exercises.answer', $lesson), ['attempt_id' => $old->id, 'answer_index' => 0])->assertConflict();
        $this->post(route('learning.exercises.answer', $lesson), ['answer_index' => 0])->assertRedirect();
        $this->assertDatabaseCount('lesson_attempts', 2);
        $this->assertSame('Pilih Ha', $old->fresh()->questions[0]['exercise_prompt']);
        $this->assertFalse($old->fresh()->answers[0]['is_correct']);
    }

    public function test_completion_points_are_not_duplicated_across_devices_or_shared_between_accounts(): void
    {
        $lesson = $this->lesson();
        $device = (string) Str::uuid();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $service = app(LearningProgressService::class);
        $progress = $service->completeLesson($device, $lesson, $first);
        $again = $service->completeLesson((string) Str::uuid(), $lesson, $first);
        $this->assertSame($progress->id, $again->id);
        $other = $service->completeLesson($device, $lesson, $second);
        $this->assertSame($second->id, $other->user_id);
        $this->assertSame($first->id, $progress->fresh()->user_id);
        $this->assertDatabaseCount('learning_progress', 2);
    }

    public function test_module_answers_and_retry_improvement_are_sent_to_ai_without_raw_essay_text(): void
    {
        config(['services.ai' => ['url' => 'http://localhost:20128/v1', 'model' => 'test', 'key' => '']]);
        Http::preventStrayRequests();
        $lesson = $this->lesson();
        $user = User::factory()->create();
        $device = (string) Str::uuid();
        $profile = LearnerProfile::create(['visitor_id' => $device, 'user_id' => $user->id, 'learning_path_id' => $lesson->learning_path_id, 'learning_path_ids' => [$lesson->learning_path_id], 'name' => 'Pelajar', 'role' => 'murid', 'mode' => 'account']);
        foreach ([false, true] as $correct) {
            LessonAttempt::factory()->for($lesson)->create(['user_id' => $user->id, 'answers' => [['answered_at' => now()->toIso8601String(), 'is_correct' => $correct, 'selected' => 'PRIVATE ESSAY']], 'completed_at' => now()]);
        }
        LessonAttempt::factory()->for($lesson)->create(['user_id' => $user->id, 'answers' => [['answered_at' => now()->subDays(20)->toIso8601String(), 'is_correct' => true]], 'completed_at' => now()->subDays(20)]);
        LessonAttempt::factory()->for($lesson)->create(['user_id' => User::factory(), 'answers' => [['answered_at' => now()->toIso8601String(), 'is_correct' => true]], 'completed_at' => now()]);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $activity = $analyzer->activity($profile);
        $this->assertSame(2, $activity['module_answers_14']);
        $this->assertSame(1, $activity['module_correct_14']);
        $this->assertSame(2, $activity['module_attempts_completed_14']);
        $this->assertSame(0, $activity['module_attempts'][0]['first_accuracy']);
        $this->assertSame(100, $activity['module_attempts'][0]['latest_accuracy']);
        $this->assertSame(1, $activity['active_days_7']);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode(['summary' => 'Ketepatan meningkat.', 'recommendations' => [['learning_path_id' => $lesson->learning_path_id, 'reason' => 'Terus berlatih.']]])]]]])]);
        $analyzer->generate(['role' => 'murid', 'learning_path_ids' => [$lesson->learning_path_id], 'activity' => $activity]);
        Http::assertSent(function ($request): bool {
            $content = $request['messages'][1]['content'];
            $data = json_decode($content, true);

            return $data['learning_activity']['module_answers_14'] === 2 && ! str_contains($content, 'PRIVATE ESSAY');
        });
    }
}
