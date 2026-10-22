<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProfileAvatarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_receive_a_random_default_avatar(): void
    {
        $user = User::factory()->create();

        $this->assertContains($user->avatar, array_keys(ProfileAvatarService::DEFAULT_AVATARS));
    }

    public function test_a_user_can_choose_a_default_avatar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.avatar.update'), ['avatar' => 'avatar/gajah.webp'])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHas('status', 'Avatar profil berhasil diperbarui.');

        $this->assertSame('avatar/gajah.webp', $user->fresh()->avatar);
        $this->actingAs($user)->get(route('profile.show'))
            ->assertOk()
            ->assertSee(asset('avatar/gajah.webp'))
            ->assertSee('Ganti avatar')
            ->assertSee('Unggah avatar sendiri')
            ->assertSee('avatar/penyu.webp');
    }

    public function test_a_user_can_upload_and_replace_a_custom_avatar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['avatar' => 'avatars/old.webp']);
        Storage::disk('local')->put('avatars/old.webp', 'old avatar');

        $this->actingAs($user)
            ->put(route('profile.avatar.update'), [
                'avatar' => 'avatar/gajah.webp',
                'avatar_upload' => UploadedFile::fake()->image('profile.png'),
            ])
            ->assertRedirect(route('profile.show'));

        $uploadedAvatar = $user->fresh()->avatar;
        $this->assertStringStartsWith('avatars/', $uploadedAvatar);
        Storage::disk('local')->assertExists($uploadedAvatar);
        Storage::disk('local')->assertMissing('avatars/old.webp');
        $avatarResponse = $this->actingAs($user)
            ->get(route('profile.avatar.show'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $avatarResponse->headers->get('Cache-Control'));

        $this->actingAs($user)
            ->put(route('profile.avatar.update'), ['avatar' => 'avatar/kucing.webp'])
            ->assertRedirect(route('profile.show'));

        $this->assertSame('avatar/kucing.webp', $user->fresh()->avatar);
        Storage::disk('local')->assertMissing($uploadedAvatar);
    }

    public function test_avatar_upload_requires_authentication_and_rejects_non_image_files(): void
    {
        $this->put(route('profile.avatar.update'), ['avatar' => 'avatar/gajah.webp'])
            ->assertRedirect(route('login'));
        $this->get(route('profile.avatar.show'))->assertRedirect(route('login'));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.avatar.update'), [
                'avatar_upload' => UploadedFile::fake()->create('not-an-image.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('avatar_upload');

        $this->assertSame($user->avatar, $user->fresh()->avatar);
    }
}
