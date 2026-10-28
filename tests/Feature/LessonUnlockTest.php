<?php

namespace Tests\Feature;

use App\Models\ContentAudio;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LessonUnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_modules_unlock_in_order_and_direct_urls_and_post_requests_cannot_skip(): void
    {
        $path = LearningPath::factory()->create();
        $first = Lesson::factory()->for($path, 'learningPath')->create(['sort_order' => 1]);
        $second = Lesson::factory()->for($path, 'learningPath')->create(['sort_order' => 2]);
        $third = Lesson::factory()->for($path, 'learningPath')->create(['sort_order' => 3, 'exercise_prompt' => 'Pilih', 'exercise_options' => ['Ha', 'Na'], 'correct_option' => 0]);
        $user = User::factory()->create();
        $this->actingAs($user)->withCookie('artaswaha_device', (string) Str::uuid());
        $this->get(route('learning.paths.show', $path))->assertInertia(fn (Assert $page): Assert => $page
            ->where('lessons.data.0.locked', false)->where('lessons.data.1.locked', true)->where('lessons.data.2.url', null));
        $this->get(route('learning.lessons.show', $first))->assertOk();
        $this->get(route('learning.lessons.show', $second))->assertRedirectToRoute('learning.paths.show', $path);
        $this->post(route('learning.progress.store', $second))->assertForbidden();
        $this->post(route('learning.exercises.answer', $third), ['answer_index' => 0])->assertForbidden();
        Storage::fake('local');
        Storage::disk('local')->put('content-audio/locked.wav', 'test audio');
        $audio = ContentAudio::factory()->for($second)->create([
            'status' => 'ready', 'reviewed_at' => now(), 'path' => 'content-audio/locked.wav',
        ]);
        $this->get(route('content-audio.show', $audio))->assertForbidden();
        $this->assertDatabaseCount('learning_progress', 0);
        $this->post(route('learning.progress.store', $first))->assertRedirect();
        $this->get(route('learning.paths.show', $path))->assertInertia(fn (Assert $page): Assert => $page
            ->where('lessons.data.1.locked', false)->where('lessons.data.2.locked', true));
        $this->get(route('learning.lessons.show', $second))->assertOk();
        $this->get(route('content-audio.show', $audio))->assertOk();
        $this->post(route('learning.progress.store', $second))->assertRedirect();
        $this->get(route('learning.lessons.show', $third))->assertOk();
        $this->post(route('learning.exercises.answer', $third), ['answer_index' => 0])->assertRedirect();
        $this->assertDatabaseCount('learning_progress', 3);
    }

    public function test_anonymous_progress_is_device_specific_and_drafts_do_not_block_or_leak(): void
    {
        $path = LearningPath::factory()->create();
        $draft = Lesson::factory()->for($path, 'learningPath')->create(['sort_order' => 0, 'reviewed_at' => null]);
        $first = Lesson::factory()->for($path, 'learningPath')->create(['sort_order' => 1]);
        $second = Lesson::factory()->for($path, 'learningPath')->create(['sort_order' => 1]);
        $device = (string) Str::uuid();
        LearnerProfile::create(['visitor_id' => $device, 'learning_path_id' => $path->id, 'role' => 'murid', 'mode' => 'anonymous', 'name' => 'Made']);
        LearningProgress::factory()->for($first)->create(['visitor_id' => (string) Str::uuid()]);
        $this->withCookie('artaswaha_device', $device)->get(route('learning.lessons.show', $first))->assertOk();
        $this->get(route('learning.lessons.show', $second))->assertRedirectToRoute('learning.paths.show', $path);
        $this->post(route('learning.progress.store', $first))->assertRedirect();
        $this->get(route('learning.lessons.show', $second))->assertOk();
        $this->get(route('learning.lessons.show', $draft))->assertNotFound();
        $this->assertDatabaseMissing('learning_progress', ['lesson_id' => $draft->id]);
    }

    public function test_account_completion_on_other_device_unlocks_but_another_users_progress_does_not(): void
    {
        $path = LearningPath::factory()->create();
        $first = Lesson::factory()->for($path, 'learningPath')->create(['sort_order' => 1]);
        $second = Lesson::factory()->for($path, 'learningPath')->create(['sort_order' => 2]);
        LearningProgress::factory()->for($first)->for(User::factory())->create();
        $user = User::factory()->create();
        $this->actingAs($user)->withCookie('artaswaha_device', (string) Str::uuid());
        $this->get(route('learning.lessons.show', $second))->assertRedirectToRoute('learning.paths.show', $path);
        LearningProgress::factory()->for($first)->for($user)->create();
        $this->get(route('learning.lessons.show', $second))->assertOk();
        $this->get(route('learning.lessons.show', $first))->assertInertia(fn (Assert $page): Assert => $page->where('progress.points', 10));
        $otherCategory = Lesson::factory()->create();
        $this->get(route('learning.lessons.show', $otherCategory))->assertOk();
    }
}
