<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\ProfileAvatarService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;

#[Fillable(['name', 'email', 'password', 'avatar', 'google_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(static function (User $user): void {
            $user->avatar ??= Arr::random(array_keys(ProfileAvatarService::DEFAULT_AVATARS));
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function learningProgress(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function hostedRooms(): HasMany
    {
        return $this->hasMany(Room::class, 'host_id');
    }

    public function roomParticipations(): HasMany
    {
        return $this->hasMany(RoomParticipant::class);
    }
}
