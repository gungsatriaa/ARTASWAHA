<?php

namespace Tests\Feature;

use App\Models\AiReport;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizDraft;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\RoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReactPageCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_page_is_an_inertia_page_without_a_renderer_flag(): void
    {
        $path = LearningPath::factory()->create();
        $lesson = Lesson::factory()->for($path, 'learningPath')->create();
        $routes = [
            ['home', [], 'Home'], ['login', [], 'Auth/Form'], ['register', [], 'Auth/Form'],
            ['password.request', [], 'Auth/Form'], ['password.reset', ['token' => 'test-token'], 'Auth/Form'],
            ['privacy-policy', [], 'Privacy'], ['learning.interview.show', [], 'Learning/Interview'],
            ['learning.paths.index', [], 'Learning/Catalog'], ['learning.paths.show', $path->slug, 'Learning/Path'],
            ['learning.lessons.show', $lesson->slug, 'Learning/Lesson'], ['quizzes.index', [], 'Quizzes/Index'],
        ];
        foreach ($routes as [$route, $parameters, $component]) {
            $this->get(route($route, $parameters))->assertOk()->assertSee('<div data-react-loader', false)->assertDontSee('data-page-loader', false)->assertInertia(fn (Assert $page): Assert => $page->component($component)
                ->missing('viewer.password')->missing('viewer.visitor_id'));
        }
    }

    public function test_every_admin_page_uses_react_and_keeps_data_scoped(): void
    {
        config(['services.ai.url' => '', 'services.tts.enabled' => false]);
        $admin = User::factory()->create(['is_admin' => true]);
        $path = LearningPath::factory()->create();
        $lesson = Lesson::factory()->for($path, 'learningPath')->create();
        $quiz = Quiz::factory()->for($admin)->create();
        $question = QuizQuestion::factory()->for($quiz)->create();
        $profile = LearnerProfile::create(['visitor_id' => (string) Str::uuid(), 'name' => 'Private name', 'learning_path_id' => $path->id, 'role' => 'murid', 'mode' => 'anonymous']);
        $draft = QuizDraft::create(['user_id' => $admin->id, 'status' => 'failed', 'source_path' => 'private/source.pdf']);
        $report = AiReport::factory()->create();
        $routes = [
            ['admin.index', [], 'Admin/Dashboard'], ['admin.analytics.index', [], 'Admin/Analytics'],
            ['admin.learning-paths.index', [], 'Admin/Paths'], ['admin.learning-paths.create', [], 'Admin/PathForm'],
            ['admin.learning-paths.edit', $path, 'Admin/PathForm'], ['admin.lessons.index', [], 'Admin/Lessons'],
            ['admin.lessons.create', [], 'Admin/LessonForm'], ['admin.lessons.edit', $lesson, 'Admin/LessonForm'],
            ['admin.lessons.preview', $lesson, 'Admin/Preview'], ['admin.audio.index', [], 'Admin/AudioIndex'],
            ['admin.audio.lessons.edit', $lesson, 'Admin/AudioEdit'], ['admin.audio.questions.edit', $question, 'Admin/AudioEdit'],
            ['admin.ai.index', [], 'Admin/Ai'], ['admin.ai.profiles.show', $profile, 'Admin/AiDetail'],
            ['admin.ai.drafts.show', $draft, 'Admin/AiDetail'], ['admin.ai.reports.show', $report, 'Admin/AiDetail'],
            ['quizzes.create', [], 'Quizzes/Editor'], ['quizzes.edit', $quiz, 'Quizzes/Editor'], ['quizzes.show', $quiz, 'Quizzes/Show'],
            ['profile.show', [], 'Profile/Show'], ['profile.about', [], 'Profile/Show'],
        ];
        $this->actingAs($admin);
        foreach ($routes as [$route, $parameters, $component]) {
            $this->get(route($route, $parameters))->assertOk()->assertSee('<div data-react-loader', false)->assertDontSee('data-page-loader', false)->assertInertia(fn (Assert $page): Assert => $page->component($component)
                ->missing('viewer.password')->missing('viewer.email')->missing('record.visitor_id')->missing('record.source_path'));
        }
    }

    public function test_a_participant_never_receives_future_questions_or_a_hidden_answer_key(): void
    {
        $host = User::factory()->create();
        $quiz = Quiz::factory()->for($host)->create(['show_answers_immediately' => false]);
        $first = QuizQuestion::factory()->for($quiz)->create(['sort_order' => 1]);
        $future = QuizQuestion::factory()->for($quiz)->create(['sort_order' => 2, 'prompt' => 'Secret future question']);
        $service = app(RoomService::class);
        $room = $service->create($quiz, $host);
        $service->start($room, $host);
        $this->actingAs($host)->get(route('rooms.show', $room->code))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->component('Rooms/Show')->where('room.question.id', $first->id)
                ->missing('room.question.correct_option')->missing('room.question.explanation')->where('room.correctOption', null)->has('room.results', 0))
            ->assertInertiaNotContains($future->prompt);
    }

    public function test_logout_rotates_history_and_removes_account_props(): void
    {
        $this->actingAs(User::factory()->create())->post(route('logout'))->assertRedirect(route('home'));
        $response = $this->get(route('home'))->assertOk()->assertInertiaValue('viewer', null);
        $this->assertTrue($response->viewData('page')['clearHistory']);
        $this->assertTrue($response->viewData('page')['encryptHistory']);
    }
}
