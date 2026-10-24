<?php

namespace Tests\Feature;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\User;
use App\Services\ProfileAvatarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningInterviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_interview_is_saved_for_the_device_and_rejects_invalid_uploads(): void
    {
        Storage::fake('local');
        $path = LearningPath::factory()->create(['is_published' => true]);

        $this->get(route('learning.interview.show'))->assertOk()->assertSee($path->title);
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_id' => $path->id])->assertRedirect(route('learning.interview.show', ['step' => 2]));
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'murid'])->assertRedirect(route('learning.interview.show', ['step' => 3]));
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'anonymous'])->assertRedirect(route('learning.interview.show', ['step' => 4]));
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Komang', 'avatar_upload' => UploadedFile::fake()->create('bad.pdf', 5, 'application/pdf')])->assertSessionHasErrors('avatar_upload');

        $response = $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Komang', 'avatar_upload' => UploadedFile::fake()->image('foto.png')]);
        $response->assertRedirect(route('learning.paths.show', $path->slug))->assertCookie('artaswaha_device');

        $profile = LearnerProfile::query()->firstOrFail();
        $this->assertSame('Komang', $profile->name);
        $this->assertSame('murid', $profile->role);
        $this->assertSame($path->id, $profile->learning_path_id);
        Storage::disk('local')->assertExists($profile->avatar);

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('learning.paths.show', $path->slug))->assertOk()->assertSee('Halo, Komang!');
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('learning.interview.avatar'))->assertOk();
        $this->withCookie('artaswaha_device', (string) Str::uuid())->get(route('learning.paths.show', $path->slug))->assertDontSee('Halo, Komang!');
        $this->withCookie('artaswaha_device', (string) Str::uuid())->get(route('learning.interview.avatar'))->assertNotFound();
    }

    public function test_account_choice_survives_login_and_links_the_interview(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $user = User::factory()->create(['password' => 'safe-password']);

        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_id' => $path->id]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'guru']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'account'])->assertRedirect(route('login'))->assertCookie('artaswaha_device');

        $profile = LearnerProfile::query()->firstOrFail();
        $this->withCookie('artaswaha_device', $profile->visitor_id)->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password'])
            ->assertRedirect(route('learning.paths.show', $path->slug));

        $this->assertDatabaseHas('learner_profiles', ['visitor_id' => $profile->visitor_id, 'user_id' => $user->id, 'name' => $user->name, 'role' => 'guru']);
    }

    public function test_anonymous_avatar_is_random_when_no_file_is_uploaded(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);

        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_id' => $path->id]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'pendatang']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'anonymous']);
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Made'])->assertRedirect(route('learning.paths.show', $path->slug));

        $this->assertContains(LearnerProfile::query()->firstOrFail()->avatar, array_keys(ProfileAvatarService::DEFAULT_AVATARS));
    }

    public function test_completed_anonymous_interview_opens_the_topic_picker_and_cannot_be_repeated(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $otherPath = LearningPath::factory()->create(['is_published' => true]);

        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_id' => $path->id]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'murid']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'anonymous']);
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Made']);

        $profile = LearnerProfile::query()->firstOrFail();

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))
            ->assertOk()
            ->assertSee('data-learning-picker-open aria-haspopup="dialog" aria-controls="learning-picker"', false)
            ->assertSee('>Mulai belajar</button>', false)
            ->assertDontSee('>Mulai belajar gratis</a>', false);
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('learning.interview.show'))
            ->assertRedirect(route('home'));
        $this->withCookie('artaswaha_device', $profile->visitor_id)->post(route('learning.interview.store'), [
            'step' => 1,
            'learning_path_id' => $otherPath->id,
        ])->assertRedirect(route('home'));
        $this->assertDatabaseHas('learner_profiles', [
            'id' => $profile->id,
            'learning_path_id' => $path->id,
            'role' => 'murid',
        ]);

        $this->withCookie('artaswaha_device', (string) Str::uuid())->get(route('learning.interview.show'))->assertOk();
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
            ->assertSee('>Mulai belajar</button>', false);
        $this->get(route('learning.interview.show'))->assertRedirect(route('home'));
    }

    public function test_anonymous_profile_replaces_guest_navigation_on_desktop_and_mobile(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_id' => $path->id]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'murid']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'anonymous']);
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Made']);

        $profile = LearnerProfile::query()->firstOrFail();
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))
            ->assertOk()
            ->assertSee('Menu profil anonim Made')
            ->assertSee('Profil anonim')
            ->assertSee('Akun anonim')
            ->assertSee(route('learning.anonymous-profile.show'), false)
            ->assertDontSee('>Daftar</a>', false);

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('learning.anonymous-profile.show'))
            ->assertOk()
            ->assertSee('Belajar sebagai Made')
            ->assertSee($path->title)
            ->assertSee('Daftar akun');

        $otherDevice = (string) Str::uuid();
        $this->withCookie('artaswaha_device', $otherDevice)->get(route('learning.anonymous-profile.show'))->assertNotFound();
        $this->withCookie('artaswaha_device', $otherDevice)->get(route('home'))->assertSee(route('register'), false)->assertDontSee('Menu profil anonim Made');
    }

    public function test_account_choice_survives_registration(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);

        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_id' => $path->id]);
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
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_id' => $path->id]);
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
            ->assertSee('Menu profil Putu Dewi')
            ->assertDontSee('Menu profil anonim Putu');
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('learning.anonymous-profile.show'))->assertRedirect(route('profile.show'));
        $this->assertDatabaseHas('learner_profiles', ['visitor_id' => $profile->visitor_id, 'mode' => 'account', 'user_id' => auth()->id()]);
    }

    public function test_interview_requires_published_path_and_prior_steps(): void
    {
        $path = LearningPath::factory()->create(['is_published' => false]);

        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_id' => $path->id])->assertSessionHasErrors('learning_path_id');
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Made'])->assertRedirect(route('learning.interview.show'));
        $this->assertDatabaseCount('learner_profiles', 0);
    }

    public function test_interview_returns_to_topic_selection_if_material_is_archived_midway(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_id' => $path->id]);
        $path->update(['is_published' => false]);

        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'guru'])
            ->assertRedirect(route('learning.interview.show'))
            ->assertSessionHasErrors('learning_path_id');
        $this->assertDatabaseCount('learner_profiles', 0);
    }
}
