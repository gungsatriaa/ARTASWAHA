<?php

namespace Tests\Feature;

use App\Models\ContentAudio;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\ContentAudioService;
use App\Services\RoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\AssertablePage;
use Tests\TestCase;

class QuizManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_preserves_question_ids_audio_and_new_order(): void
    {
        Storage::fake('local');
        $quiz = Quiz::factory()->create();
        $questions = QuizQuestion::factory()->for($quiz)->count(2)->sequence(['sort_order' => 1], ['sort_order' => 2])->create();
        $audio = $this->audio($questions[0]);
        $data = ['title' => 'Urutan baru', 'questions' => [$this->questionData($questions[1]), $this->questionData($questions[0])]];
        $data['questions'][1]['keyboard_auto_open'] = true;

        $this->actingAs($quiz->user)->put(route('quizzes.update', $quiz), $data)->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame([$questions[1]->id, $questions[0]->id], $quiz->fresh()->questions->pluck('id')->all());
        $this->assertSame($audio->version, $audio->fresh()->version);
        $this->assertTrue(app(ContentAudioService::class)->isPlayable($audio->fresh()));
        Storage::disk('local')->assertExists($audio->path);
    }

    public function test_changed_prompt_invalidates_audio_without_deleting_file(): void
    {
        Storage::fake('local');
        $question = QuizQuestion::factory()->create();
        $audio = $this->audio($question);
        $data = $this->questionData($question);
        $data['prompt'] = 'Pertanyaan yang sudah berubah';

        $this->actingAs($question->quiz->user)->put(route('quizzes.update', $question->quiz), ['title' => 'Ubah', 'questions' => [$data]])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('stale', $audio->fresh()->status);
        $this->assertNotSame($audio->version, $audio->fresh()->version);
        $this->assertFalse(app(ContentAudioService::class)->isPlayable($audio->fresh()));
        Storage::disk('local')->assertExists($audio->path);
    }

    public function test_new_version_copies_current_audio_and_preserves_old_room(): void
    {
        Storage::fake('local');
        $question = QuizQuestion::factory()->create(['keyboard_auto_open' => true]);
        $quiz = $question->quiz;
        $audio = $this->audio($question);
        $room = app(RoomService::class)->create($quiz, $quiz->user);

        $this->actingAs($quiz->user)->put(route('quizzes.update', $quiz), ['title' => 'Versi baru', 'questions' => [$this->questionData($question)]])->assertRedirect();

        $new = Quiz::query()->where('is_archived', false)->sole()->questions()->sole();
        $this->assertNotSame($question->id, $new->id);
        $this->assertTrue($new->keyboard_auto_open);
        $this->assertSame($quiz->id, $room->fresh()->quiz_id);
        $this->assertNotSame($audio->path, $new->audio->path);
        $this->assertTrue(app(ContentAudioService::class)->isPlayable($new->audio));
        Storage::disk('local')->assertExists([$audio->path, $new->audio->path]);
        Storage::disk('local')->delete($new->audio->path);
        Storage::disk('local')->assertExists($audio->path);
    }

    public function test_duplicate_archived_quiz_keeps_keyboard_and_approved_audio_without_rooms(): void
    {
        Storage::fake('local');
        $question = QuizQuestion::factory()->create(['keyboard_auto_open' => true]);
        $quiz = $question->quiz;
        $audio = $this->audio($question);
        $quiz->update(['is_archived' => true]);

        $this->actingAs($quiz->user)->post(route('quizzes.duplicate', $quiz))->assertRedirect();

        $copy = Quiz::query()->where('is_archived', false)->sole();
        $this->assertSame(0, $copy->rooms()->count());
        $this->assertTrue($copy->questions->sole()->keyboard_auto_open);
        $this->assertNotSame($audio->path, $copy->questions->sole()->audio->path);
        $this->assertTrue(app(ContentAudioService::class)->isPlayable($copy->questions->sole()->audio));
    }

    public function test_duplicate_skips_stale_audio(): void
    {
        Storage::fake('local');
        $question = QuizQuestion::factory()->create();
        $this->audio($question)->update(['status' => 'stale']);

        $this->actingAs($question->quiz->user)->post(route('quizzes.duplicate', $question->quiz))->assertRedirect();

        $copy = Quiz::query()->whereKeyNot($question->quiz_id)->sole();
        $this->assertNull($copy->questions->sole()->audio);
    }

    public function test_archive_preserves_room_and_can_be_found_in_archive_filter(): void
    {
        $question = QuizQuestion::factory()->create();
        $quiz = $question->quiz;
        $room = app(RoomService::class)->create($quiz, $quiz->user);

        $this->actingAs($quiz->user)->post(route('quizzes.archive', $quiz))->assertRedirect(route('quizzes.index'));

        $this->assertTrue($quiz->fresh()->is_archived);
        $this->assertSame($quiz->id, $room->fresh()->quiz_id);
        $this->get(route('quizzes.index', ['status' => 'archived', 'q' => $quiz->title]))->assertOk()->assertReactContains($quiz->title);
        $this->get(route('quizzes.index'))->assertReactNotContains($quiz->title);
        $this->post(route('rooms.store', $quiz))->assertStatus(422);
    }

    public function test_other_owner_cannot_archive_duplicate_or_attach_foreign_question(): void
    {
        $question = QuizQuestion::factory()->create();
        $other = User::factory()->create();
        $otherQuiz = Quiz::factory()->for($other)->create();

        $this->actingAs($other)->post(route('quizzes.archive', $question->quiz))->assertNotFound();
        $this->post(route('quizzes.duplicate', $question->quiz))->assertNotFound();
        $this->put(route('quizzes.update', $otherQuiz), ['title' => 'Injeksi', 'questions' => [$this->questionData($question)]])->assertSessionHasErrors('questions.0.id');

        $this->assertFalse($question->quiz->fresh()->is_archived);
        $this->assertDatabaseCount('quizzes', 2);
        $this->assertSame($question->quiz_id, $question->fresh()->quiz_id);
    }

    public function test_editor_and_detail_expose_preview_keyboard_and_admin_audio_controls_safely(): void
    {
        Storage::fake('local');
        $question = QuizQuestion::factory()->create(['prompt' => '<script>alert(1)</script>', 'keyboard_auto_open' => true]);
        $admin = $question->quiz->user;
        $admin->forceFill(['is_admin' => true])->save();
        $audio = $this->audio($question);

        $this->actingAs($admin)->get(route('quizzes.edit', $question->quiz))
            ->assertOk()->assertReact(fn (AssertablePage $page) => $page->etc())
            ->assertReactContains(route('admin.audio.questions.edit', $question))
            ->assertReactContains(route('content-audio.show', $audio))
            ->assertReactContains('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('quizzes.show', $question->quiz))->assertOk()
            ->assertReactContains(route('quizzes.archive', $question->quiz))
            ->assertReactContains(route('quizzes.duplicate', $question->quiz));
        $this->get(route('admin.audio.questions.edit', $question))->assertOk()->assertReact(fn (AssertablePage $page) => $page->etc());
    }

    public function test_search_lists_only_matching_owned_quizzes_and_shows_actions(): void
    {
        $owner = User::factory()->create();
        $matching = Quiz::factory()->for($owner)->create(['title' => 'Belajar aksara']);
        Quiz::factory()->for($owner)->create(['title' => 'Paribasa lainnya']);
        Quiz::factory()->create(['title' => 'Belajar rahasia']);

        $this->actingAs($owner)->get(route('quizzes.index', ['q' => 'Belajar']))
            ->assertOk()->assertReactContains('Belajar aksara')->assertReactNotContains('Paribasa lainnya')->assertReactNotContains('Belajar rahasia')
            ->assertReactContains(route('quizzes.edit', $matching))->assertReact(fn (AssertablePage $page) => $page->etc());
    }

    public function test_repeated_question_id_is_rejected_without_modifying_quiz(): void
    {
        $question = QuizQuestion::factory()->create();
        $data = $this->questionData($question);

        $this->actingAs($question->quiz->user)->put(route('quizzes.update', $question->quiz), ['title' => 'Gagal', 'questions' => [$data, $data]])
            ->assertSessionHasErrors('questions.0.id');

        $this->assertDatabaseCount('quiz_questions', 1);
        $this->assertNotSame('Gagal', $question->quiz->fresh()->title);
    }

    public function test_sparse_question_indexes_are_saved_in_sequential_order(): void
    {
        $question = QuizQuestion::factory()->create();

        $this->actingAs($question->quiz->user)->put(route('quizzes.update', $question->quiz), [
            'title' => 'Urutan rapi', 'questions' => [5 => $this->questionData($question)],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(1, $question->fresh()->sort_order);
    }

    private function questionData(QuizQuestion $question): array
    {
        return [...$question->only(['id', 'prompt', 'options', 'correct_option', 'explanation']), 'keyboard_auto_open' => $question->keyboard_auto_open ?? false];
    }

    private function audio(QuizQuestion $question): ContentAudio
    {
        $path = 'content-audio/'.$question->id.'.wav';
        Storage::disk('local')->put($path, 'audio uji');

        return ContentAudio::factory()->create(['lesson_id' => null, 'quiz_question_id' => $question->id, 'path' => $path, 'status' => 'ready', 'reviewed_at' => now()]);
    }
}
