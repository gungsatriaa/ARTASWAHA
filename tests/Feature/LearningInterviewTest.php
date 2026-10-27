<?php

namespace Tests\Feature;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\User;
use App\Services\ProfileAvatarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LearningInterviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_learning_selection_shows_a_friendly_message_above_the_choices(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $message = 'Silakan pilih minimal satu materi yang ingin kamu pelajari untuk melanjutkan, ya.';

        $this->from(route('learning.interview.show'))
            ->followingRedirects()
            ->post(route('learning.interview.store'), ['step' => 1])
            ->assertOk()
            ->assertInertiaValue('errors.learning_path_ids', $message)
            ->assertInertiaNotContains('validation.required');
    }

    public function test_anonymous_interview_is_saved_for_the_device_and_rejects_invalid_uploads(): void
    {
        Storage::fake('local');
        $path = LearningPath::factory()->create(['is_published' => true]);

        $this->get(route('learning.interview.show'))->assertOk()->assertInertiaContains($path->title);
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id]])->assertRedirect(route('learning.interview.show', ['step' => 2]));
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'murid'])->assertRedirect(route('learning.interview.show', ['step' => 3]));
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'anonymous'])->assertRedirect(route('learning.interview.show', ['step' => 4]));
        $this->get(route('learning.interview.show', ['step' => 4]))
            ->assertOk()
            ->assertInertiaNotContains('Petualanganmu dimulai di sini.');
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Komang', 'avatar_upload' => UploadedFile::fake()->create('bad.pdf', 5, 'application/pdf')])->assertSessionHasErrors('avatar_upload');

        $response = $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Komang', 'avatar_upload' => UploadedFile::fake()->image('foto.png')]);
        $response->assertRedirect(route('learning.paths.show', $path->slug))->assertCookie('artaswaha_device');

        $profile = LearnerProfile::query()->firstOrFail();
        $this->assertSame('Komang', $profile->name);
        $this->assertSame('murid', $profile->role);
        $this->assertSame($path->id, $profile->learning_path_id);
        Storage::disk('local')->assertExists($profile->avatar);

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('learning.paths.show', $path->slug))->assertOk()->assertInertiaValue('viewer.name', 'Komang');
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('learning.interview.avatar'))->assertOk();
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.avatar.show'))->assertOk();
        $this->withCookie('artaswaha_device', (string) Str::uuid())->get(route('learning.paths.show', $path->slug));
        $this->withCookie('artaswaha_device', (string) Str::uuid())->get(route('learning.interview.avatar'))->assertNotFound();
        $this->withCookie('artaswaha_device', (string) Str::uuid())->get(route('profile.avatar.show'))->assertRedirect(route('login'));
    }

    public function test_account_choice_survives_login_and_links_the_interview(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $otherPath = LearningPath::factory()->create(['is_published' => true]);
        $user = User::factory()->create(['password' => 'safe-password']);

        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id, $otherPath->id]]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'guru']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'account'])->assertRedirect(route('login'))->assertCookie('artaswaha_device');

        $profile = LearnerProfile::query()->firstOrFail();
        $this->withCookie('artaswaha_device', $profile->visitor_id)->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password'])
            ->assertRedirect(route('learning.paths.show', $path->slug));

        $this->assertDatabaseHas('learner_profiles', ['visitor_id' => $profile->visitor_id, 'user_id' => $user->id, 'name' => $user->name, 'role' => 'guru']);
        $this->assertSame([$path->id, $otherPath->id], $profile->fresh()->learning_path_ids);
    }

    public function test_multiple_topics_are_selected_and_saved_for_the_profile(): void
    {
        $firstPath = LearningPath::factory()->create(['is_published' => true, 'title' => 'Cecimpedan', 'group' => 'paribasa']);
        $secondPath = LearningPath::factory()->create(['is_published' => true, 'title' => 'Pepengangge', 'group' => 'aksara']);

        $this->get(route('learning.interview.show'))
            ->assertInertia(fn (AssertableInertia $page) => $page->etc());

        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$firstPath->id, $secondPath->id]])
            ->assertRedirect(route('learning.interview.show', ['step' => 2]));
        $this->get(route('learning.interview.show', ['step' => 1]))
            ->assertInertiaValue('answers.learning_path_ids', [$firstPath->id, $secondPath->id]);

        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'murid']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'anonymous']);
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Made'])
            ->assertRedirect(route('learning.paths.show', $firstPath->slug));

        $profile = LearnerProfile::query()->firstOrFail();
        $this->assertSame($firstPath->id, $profile->learning_path_id);
        $this->assertSame([$firstPath->id, $secondPath->id], $profile->learning_path_ids);
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))
            ->assertOk()
            ->assertInertiaContains($firstPath->title)
            ->assertInertiaContains($secondPath->title);
    }

    public function test_topic_selection_rejects_empty_duplicate_and_unpublished_choices(): void
    {
        $published = LearningPath::factory()->create(['is_published' => true]);
        $unpublished = LearningPath::factory()->create(['is_published' => false]);

        $this->post(route('learning.interview.store'), ['step' => 1])->assertSessionHasErrors('learning_path_ids');
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$published->id, $published->id]])
            ->assertSessionHasErrors('learning_path_ids.0');
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$published->id, $unpublished->id]])
            ->assertSessionHasErrors('learning_path_ids.1');
        $this->assertDatabaseCount('learner_profiles', 0);
    }

    public function test_anonymous_avatar_is_random_when_no_file_is_uploaded(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);

        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id]]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'pendatang']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'anonymous']);
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Made'])->assertRedirect(route('learning.paths.show', $path->slug));

        $this->assertContains(LearnerProfile::query()->firstOrFail()->avatar, array_keys(ProfileAvatarService::DEFAULT_AVATARS));
    }

    public function test_completed_anonymous_interview_opens_the_topic_picker_and_cannot_be_repeated(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $otherPath = LearningPath::factory()->create(['is_published' => true]);

        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id]]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'murid']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'anonymous']);
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Made']);

        $profile = LearnerProfile::query()->firstOrFail();

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('learning.interview.show'))
            ->assertRedirect(route('home'));
        $this->withCookie('artaswaha_device', $profile->visitor_id)->post(route('learning.interview.store'), [
            'step' => 1,
            'learning_path_ids' => [$otherPath->id],
        ])->assertRedirect(route('home'));
        $this->assertDatabaseHas('learner_profiles', [
            'id' => $profile->id,
            'learning_path_id' => $path->id,
            'role' => 'murid',
        ]);

        $this->withCookie('artaswaha_device', (string) Str::uuid())->get(route('learning.interview.show'))->assertOk();
    }

    public function test_a_signed_in_account_without_an_interview_does_not_receive_or_repeat_it(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('learning.interview.show'))->assertRedirect(route('home'));
        $this->post(route('learning.interview.store'), ['step' => 1])->assertRedirect(route('home'));
        $this->assertDatabaseCount('learner_profiles', 0);
    }

    public function test_completed_account_interview_is_recognized_on_another_device(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $user = User::factory()->create();
        LearnerProfile::query()->create([
            'visitor_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
            'role' => 'guru',
            'name' => $user->name,
            'mode' => 'account',
        ]);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $this->get(route('learning.interview.show'))->assertRedirect(route('home'));
    }

    public function test_anonymous_profile_replaces_guest_navigation_on_desktop_and_mobile(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id]]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'murid']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'anonymous']);
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Made']);

        $profile = LearnerProfile::query()->firstOrFail();
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))
            ->assertOk()
            ->assertInertiaValue('viewer.name', 'Made')
            ->assertInertiaContains(route('profile.show'))
            ->assertInertiaNotContains('/profil-anonim');

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))
            ->assertOk()
            ->assertInertiaValue('viewer.name', 'Made')
            ->assertInertiaContains($path->title)
            ->assertInertia(fn (AssertableInertia $page) => $page->etc());

        $otherDevice = (string) Str::uuid();
        $this->withCookie('artaswaha_device', $otherDevice)->get(route('profile.show'))->assertRedirect(route('login'));
        $this->withCookie('artaswaha_device', $otherDevice)->get(route('home'))->assertInertiaContains(route('register'))->assertInertiaNotContains('Made');
    }

    public function test_anonymous_profile_shows_only_this_devices_learning_progress_and_account_menu(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true, 'group' => 'paribasa']);
        $completedLesson = Lesson::factory()->create(['learning_path_id' => $path->id]);
        $otherLesson = Lesson::factory()->create(['learning_path_id' => $path->id]);
        $visitorId = (string) Str::uuid();
        LearnerProfile::query()->create([
            'visitor_id' => $visitorId,
            'learning_path_id' => $path->id,
            'role' => 'murid',
            'name' => 'Made',
            'avatar' => 'avatar/gajah.webp',
            'mode' => 'anonymous',
        ]);
        LearningProgress::factory()->create(['visitor_id' => $visitorId, 'lesson_id' => $completedLesson->id]);
        LearningProgress::factory()->create(['visitor_id' => (string) Str::uuid(), 'lesson_id' => $otherLesson->id]);

        $this->withCookie('artaswaha_device', $visitorId)->get(route('profile.show'))
            ->assertOk()
            ->assertInertiaValue('viewer.name', 'Made')
            ->assertInertiaNotContains('Gabung kuis')
            ->assertInertiaContains(route('quizzes.index'))
            ->assertInertiaContains(route('profile.about'))
            ->assertInertiaContains(route('profile.avatar.update'));

        $this->withCookie('artaswaha_device', $visitorId)->get(route('profile.about'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $this->withCookie('artaswaha_device', (string) Str::uuid())->get(route('profile.about'))->assertRedirect(route('login'));
        $this->get('/profil-anonim')->assertRedirect(route('profile.show'));
    }

    public function test_anonymous_avatar_can_be_changed_only_from_its_own_device(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $visitorId = (string) Str::uuid();
        $profile = LearnerProfile::query()->create([
            'visitor_id' => $visitorId,
            'learning_path_id' => $path->id,
            'role' => 'murid',
            'name' => 'Made',
            'avatar' => 'avatar/gajah.webp',
            'mode' => 'anonymous',
        ]);

        $this->withCookie('artaswaha_device', (string) Str::uuid())
            ->put(route('profile.avatar.update'), ['avatar' => 'avatar/kucing.webp'])
            ->assertRedirect(route('login'));
        $this->assertSame('avatar/gajah.webp', $profile->fresh()->avatar);

        $this->withCookie('artaswaha_device', $visitorId)
            ->put(route('profile.avatar.update'), ['avatar' => 'avatar/kucing.webp'])
            ->assertRedirect(route('profile.show'));
        $this->assertSame('avatar/kucing.webp', $profile->fresh()->avatar);
    }

    public function test_account_choice_survives_registration(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);

        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id]]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'pendatang']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'account'])->assertRedirect(route('login'));
        $profile = LearnerProfile::query()->firstOrFail();

        $this->withCookie('artaswaha_device', $profile->visitor_id)->post(route('register.store'), [
            'name' => 'Kadek',
            'email' => 'kadek@example.test',
            'password' => 'safe-password',
            'password_confirmation' => 'safe-password',
        ])->assertRedirect(route('learning.paths.show', $path->slug));

        $this->assertDatabaseHas('learner_profiles', ['visitor_id' => $profile->visitor_id, 'user_id' => auth()->id(), 'name' => 'Kadek']);
    }

    public function test_registering_from_anonymous_profile_changes_navigation_to_account_profile(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id]]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'guru']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'anonymous']);
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Putu']);
        $profile = LearnerProfile::query()->firstOrFail();

        $this->withCookie('artaswaha_device', $profile->visitor_id)->post(route('register.store'), [
            'name' => 'Putu Dewi',
            'email' => 'putu@example.test',
            'password' => 'safe-password',
            'password_confirmation' => 'safe-password',
        ])->assertRedirect(route('home'));

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))
            ->assertOk()
            ->assertInertiaValue('viewer.name', 'Putu Dewi')
            ->assertInertiaValue('viewer.mode', 'account');
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))->assertOk()->assertInertiaContains('Putu Dewi');
        $this->assertDatabaseHas('learner_profiles', ['visitor_id' => $profile->visitor_id, 'mode' => 'account', 'user_id' => auth()->id()]);
    }

    public function test_interview_requires_published_path_and_prior_steps(): void
    {
        $path = LearningPath::factory()->create(['is_published' => false]);

        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id]])->assertSessionHasErrors('learning_path_ids.0');
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Made'])->assertRedirect(route('learning.interview.show'));
        $this->assertDatabaseCount('learner_profiles', 0);
    }

    public function test_interview_returns_to_topic_selection_if_material_is_archived_midway(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id]]);
        $path->update(['is_published' => false]);

        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'guru'])
            ->assertRedirect(route('learning.interview.show'))
            ->assertSessionHasErrors('learning_path_ids');
        $this->assertDatabaseCount('learner_profiles', 0);
    }
}
