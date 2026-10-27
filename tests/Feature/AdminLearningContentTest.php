<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminLearningContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_access_is_required_for_content_management(): void
    {
        $this->get(route('admin.index'))->assertRedirect(route('login'));
        $this->get(route('admin.lessons.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.index'))->assertForbidden();
        $this->get(route('admin.lessons.index'))->assertForbidden();
    }

    public function test_admin_dashboard_summarizes_modules_and_links_to_management(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $learningPath = LearningPath::factory()->create(['title' => 'Paribasa Bali']);
        Lesson::factory()->create(['learning_path_id' => $learningPath->id, 'title' => 'Modul terbit']);
        Lesson::factory()->create(['learning_path_id' => $learningPath->id, 'title' => 'Modul draf', 'is_published' => false, 'reviewed_at' => null]);
        Lesson::factory()->create(['learning_path_id' => $learningPath->id, 'title' => 'Modul arsip', 'is_published' => false]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertInertiaContains(route('admin.lessons.create'))
            ->assertInertiaContains(route('admin.learning-paths.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->etc());

        $this->actingAs($admin)->get(route('admin.lessons.index'))
            ->assertOk()
            ->assertInertiaContains(route('admin.learning-paths.index'));
    }

    public function test_admin_can_save_review_publish_edit_and_archive_a_lesson(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $learningPath = LearningPath::factory()->create(['is_published' => true]);

        $this->actingAs($admin)->get(route('admin.lessons.index'))->assertOk();
        $this->get(route('admin.lessons.create'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $this->get(route('admin.learning-paths.create'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $this->post(route('admin.lessons.store'), [
            'learning_path_id' => $learningPath->id,
            'title' => 'Salam Bali',
            'slug' => 'salam-bali',
            'summary' => 'Ungkapan salam sederhana.',
            'content' => 'Rahajeng semeng.',
            'sort_order' => 1,
            'has_exercise' => '1',
            'exercise_prompt' => 'Ucapan untuk pagi hari?',
            'exercise_options' => ['Rahajeng semeng', 'Rahajeng wengi', 'Suksma', 'Napi kabar'],
            'correct_option' => 0,
            'exercise_explanation' => 'Rahajeng semeng berarti selamat pagi.',
        ])->assertRedirect();

        $lesson = Lesson::query()->where('slug', 'salam-bali')->firstOrFail();
        $this->get(route('admin.lessons.preview', $lesson))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $this->assertFalse($lesson->is_published);
        $this->assertNull($lesson->reviewed_at);
        $this->get(route('learning.lessons.show', $lesson->slug))->assertNotFound();

        $this->post(route('admin.lessons.publish', $lesson))->assertRedirect(route('admin.lessons.index'));
        $this->assertTrue($lesson->fresh()->is_published);
        $this->assertArrayNotHasKey('correct_option', $lesson->fresh()->toArray());
        $this->get(route('learning.lessons.show', $lesson->slug))->assertOk()->assertInertiaContains('Ucapan untuk pagi hari?')->assertInertiaNotContains('exercise_explanation');

        $visitorId = (string) Str::uuid();
        $answerRoute = route('learning.exercises.answer', $lesson->slug);
        $this->withCookie('artaswaha_device', $visitorId)->post($answerRoute, ['answer_index' => 1])->assertRedirect()->assertSessionHas('status', 'Belum tepat. Coba ingat lagi materi di atas, lalu pilih jawaban lain.')->assertSessionHas('status_tone', 'info');
        $this->withSession(['status' => 'Belum tepat. Coba lagi.', 'status_tone' => 'info'])
            ->get(route('learning.lessons.show', $lesson->slug))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $this->assertDatabaseCount('learning_progress', 0);
        $this->withCookie('artaswaha_device', $visitorId)->post(route('learning.progress.store', $lesson->slug))->assertForbidden();
        $this->assertDatabaseCount('learning_progress', 0);
        $this->withCookie('artaswaha_device', $visitorId)->post($answerRoute, ['answer_index' => 0])->assertRedirect();
        $this->assertDatabaseHas('learning_progress', ['visitor_id' => $visitorId, 'lesson_id' => $lesson->id, 'points' => 10]);
        $this->withCookie('artaswaha_device', $visitorId)->get(route('learning.lessons.show', $lesson->slug))->assertOk()->assertInertiaContains('Rahajeng semeng berarti selamat pagi.');

        $learningPath->update(['is_published' => false]);
        $this->get(route('learning.lessons.show', $lesson->slug))->assertNotFound();
        $learningPath->update(['is_published' => true]);

        $this->put(route('admin.lessons.update', $lesson), [
            'learning_path_id' => $learningPath->id,
            'title' => 'Salam Bali Dasar',
            'slug' => 'salam-bali',
            'summary' => 'Ungkapan salam sehari-hari.',
            'content' => 'Rahajeng wengi.',
            'sort_order' => 1,
        ])->assertRedirect(route('admin.lessons.preview', $lesson));
        $this->assertFalse($lesson->fresh()->is_published);
        $this->assertNull($lesson->fresh()->reviewed_at);
        $this->get(route('learning.lessons.show', $lesson->slug))->assertNotFound();

        $this->post(route('admin.lessons.publish', $lesson))->assertRedirect(route('admin.lessons.index'));
        $this->post(route('admin.lessons.archive', $lesson))->assertRedirect(route('admin.lessons.index'));
        $this->assertFalse($lesson->fresh()->is_published);
        $this->get(route('learning.lessons.show', $lesson->slug))->assertNotFound();
    }

    public function test_admin_can_manage_learning_path_and_command_promotes_existing_account(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->post(route('admin.learning-paths.store'), [
            'group' => 'aksara',
            'title' => 'Aksara Dasar',
            'slug' => 'aksara-dasar',
            'description' => 'Belajar aksara Bali dasar.',
            'sort_order' => 1,
        ])->assertRedirect(route('admin.learning-paths.index'));

        $learningPath = LearningPath::query()->where('slug', 'aksara-dasar')->firstOrFail();
        $this->assertFalse($learningPath->is_published);
        $this->put(route('admin.learning-paths.update', $learningPath), [
            'group' => 'aksara',
            'title' => 'Aksara Dasar',
            'slug' => 'aksara-dasar',
            'description' => 'Belajar aksara Bali dasar.',
            'sort_order' => 1,
            'is_published' => true,
        ])->assertRedirect(route('admin.learning-paths.index'));
        $this->assertTrue($learningPath->fresh()->is_published);
        $this->get(route('home'))->assertOk()->assertInertiaContains('Aksara Dasar');
        $this->put(route('admin.learning-paths.update', $learningPath), [
            'group' => 'aksara',
            'title' => 'Aksara Dasar',
            'slug' => 'aksara-dasar',
            'description' => 'Belajar aksara Bali dasar.',
            'sort_order' => 1,
        ])->assertRedirect(route('admin.learning-paths.index'));
        $this->assertFalse($learningPath->fresh()->is_published);
        $this->get(route('learning.paths.show', $learningPath->slug))->assertNotFound();
        $this->get(route('home'))->assertInertiaNotContains('Aksara Dasar');

        $account = User::factory()->create();
        $this->artisan('app:make-admin-user', ['email' => $account->email])->assertExitCode(0);
        $this->assertTrue($account->fresh()->is_admin);
    }
}
