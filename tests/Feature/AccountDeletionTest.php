<?php

namespace Tests\Feature;

use App\Models\ContentAudio;
use App\Models\LearnerProfile;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizDraft;
use App\Models\QuizQuestion;
use App\Models\Room;
use App\Models\RoomAnswer;
use App\Models\RoomParticipant;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_deletion_removes_owned_data_files_sessions_and_tokens_but_preserves_other_accounts(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['avatar' => 'avatars/owner.webp']);
        $other = User::factory()->create(['avatar' => 'avatars/other.webp']);
        $lesson = Lesson::factory()->create();
        $profile = $this->profile(['user_id' => $user->id, 'mode' => 'account', 'avatar' => 'avatars/interview.webp']);
        LearningProgress::factory()->create(['user_id' => $user->id, 'lesson_id' => $lesson->id]);
        LearningProgress::factory()->create(['user_id' => $other->id, 'lesson_id' => $lesson->id]);
        $quiz = Quiz::factory()->create(['user_id' => $user->id]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        $room = Room::factory()->create(['quiz_id' => $quiz->id, 'host_id' => $user->id]);
        $participant = RoomParticipant::factory()->create(['room_id' => $room->id, 'user_id' => $other->id]);
        $answer = RoomAnswer::factory()->create(['room_participant_id' => $participant->id, 'quiz_question_id' => $question->id]);
        $audio = ContentAudio::factory()->create(['lesson_id' => null, 'quiz_question_id' => $question->id, 'path' => 'content-audio/owner.wav']);
        $draft = QuizDraft::create(['user_id' => $user->id, 'source_path' => 'quiz-drafts/owner.pdf']);
        foreach (['avatars/owner.webp', 'avatars/interview.webp', 'avatars/other.webp', 'content-audio/owner.wav', 'quiz-drafts/owner.pdf'] as $file) {
            Storage::disk('local')->put($file, 'test');
        }
        DB::table('sessions')->insert(['id' => 'other-device-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        Password::broker()->createToken($user);
        DB::table('active_visitors')->insert(['visitor_key' => hash('sha256', 'user:'.$user->id), 'kind' => 'account', 'last_seen_at' => now()]);

        $this->actingAs($user)->delete(route('profile.destroy'), ['confirmation' => 'Hapus Akun Saya', 'user_id' => $other->id])
            ->assertRedirect(route('home'))->assertCookieExpired('artaswaha_device')->assertSessionHas('status');
        $this->assertGuest();
        foreach ([$user, $profile, $quiz, $question, $room, $participant, $answer, $audio, $draft] as $model) {
            $this->assertModelMissing($model);
        }
        $this->assertModelExists($other);
        $this->assertModelExists($lesson);
        $this->assertDatabaseMissing('learning_progress', ['user_id' => $user->id]);
        $this->assertDatabaseHas('learning_progress', ['user_id' => $other->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseCount('active_visitors', 0);
        Storage::disk('local')->assertMissing(['avatars/owner.webp', 'avatars/interview.webp', 'content-audio/owner.wav', 'quiz-drafts/owner.pdf']);
        Storage::disk('local')->assertExists('avatars/other.webp');
    }

    public function test_anonymous_deletion_only_removes_the_current_unclaimed_profile_and_its_progress(): void
    {
        Storage::fake('local');
        $profile = $this->profile(['avatar' => 'avatars/anonymous.webp']);
        $other = $this->profile();
        $lesson = Lesson::factory()->create();
        $claimed = LearningProgress::factory()->create(['visitor_id' => $profile->visitor_id, 'lesson_id' => $lesson->id, 'user_id' => User::factory()->create()->id]);
        $progress = LearningProgress::factory()->create(['visitor_id' => $profile->visitor_id]);
        $otherProgress = LearningProgress::factory()->create(['visitor_id' => $other->visitor_id]);
        $participant = RoomParticipant::factory()->create(['user_id' => null, 'learner_profile_id' => $profile->id]);
        $answer = RoomAnswer::factory()->create(['room_participant_id' => $participant->id]);
        Storage::disk('local')->put($profile->avatar, 'test');

        $this->withCookie('artaswaha_device', $profile->visitor_id)->delete(route('profile.destroy'), ['confirmation' => 'Hapus Akun Saya'])
            ->assertRedirect(route('home'))->assertCookieExpired('artaswaha_device');
        foreach ([$profile, $progress, $participant, $answer] as $model) {
            $this->assertModelMissing($model);
        }
        foreach ([$other, $claimed, $otherProgress, $lesson] as $model) {
            $this->assertModelExists($model);
        }
        Storage::disk('local')->assertMissing('avatars/anonymous.webp');
    }

    public function test_confirmation_must_match_exactly_before_deleting_an_account(): void
    {
        $user = User::factory()->create();
        foreach (['', 'hapus akun saya', 'Hapus Akun', ' Hapus Akun Saya '] as $confirmation) {
            $this->actingAs($user)->delete(route('profile.destroy'), ['confirmation' => $confirmation])->assertSessionHasErrors('confirmation');
            $this->assertModelExists($user);
        }
        $this->assertAuthenticatedAs($user);
    }

    public function test_missing_identity_or_a_cookie_for_a_claimed_profile_cannot_delete_an_account(): void
    {
        $this->delete(route('profile.destroy'), ['confirmation' => 'Hapus Akun Saya'])->assertForbidden();
        $user = User::factory()->create();
        $profile = $this->profile(['user_id' => $user->id, 'mode' => 'account']);
        $this->withCookie('artaswaha_device', $profile->visitor_id)->delete(route('profile.destroy'), ['confirmation' => 'Hapus Akun Saya'])->assertForbidden();
        $this->assertModelExists($user);
        $this->assertModelExists($profile);
    }

    public function test_file_cleanup_failure_is_reported_without_leaving_the_deleted_account_logged_in(): void
    {
        $user = User::factory()->create(['avatar' => 'avatars/unavailable.webp']);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->once()->with($user->avatar)->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        $this->actingAs($user)->delete(route('profile.destroy'), ['confirmation' => 'Hapus Akun Saya'])
            ->assertRedirect(route('home'))->assertSessionHas('status_tone', 'info');
        $this->assertGuest();
        $this->assertModelMissing($user);
    }

    public function test_failed_database_deletion_rolls_back_progress_and_keeps_uploaded_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['avatar' => 'avatars/kept.webp']);
        $progress = LearningProgress::factory()->create(['user_id' => $user->id]);
        Storage::disk('local')->put($user->avatar, 'test');
        User::deleting(static function (): void {
            throw new RuntimeException('Simulated deletion failure');
        });

        try {
            app(AccountDeletionService::class)->delete($user, 'test-session');
            $this->fail('Expected deletion to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated deletion failure', $exception->getMessage());
        }
        $this->assertModelExists($user);
        $this->assertModelExists($progress);
        Storage::disk('local')->assertExists('avatars/kept.webp');
    }

    public function test_anonymous_deletion_requires_confirmation_too(): void
    {
        $profile = $this->profile();
        $this->withCookie('artaswaha_device', $profile->visitor_id)->delete(route('profile.destroy'), ['confirmation' => 'hapus'])
            ->assertSessionHasErrors('confirmation');
        $this->assertModelExists($profile);
    }

    /** @param array<string, mixed> $attributes */
    private function profile(array $attributes = []): LearnerProfile
    {
        return LearnerProfile::create([...[
            'visitor_id' => (string) Str::uuid(), 'learning_path_id' => Lesson::factory()->create()->learning_path_id,
            'name' => 'Kadek', 'role' => 'murid', 'mode' => 'anonymous', 'avatar' => 'avatar/penyu.webp',
        ], ...$attributes]);
    }
}
