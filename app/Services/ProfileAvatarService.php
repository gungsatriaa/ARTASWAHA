<?php

namespace App\Services;

use App\Models\LearnerProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProfileAvatarService
{
    public const DEFAULT_AVATARS = [
        'avatar/anjing.webp' => 'Anjing',
        'avatar/gajah.webp' => 'Gajah',
        'avatar/jalak_bali.webp' => 'Jalak Bali',
        'avatar/kucing.webp' => 'Kucing',
        'avatar/kupu_kupu.webp' => 'Kupu-kupu',
        'avatar/monyet.webp' => 'Monyet',
        'avatar/penyu.webp' => 'Penyu',
        'avatar/rusa.webp' => 'Rusa',
        'avatar/tokek.webp' => 'Tokek',
    ];

    /** @param array{avatar?: string|null, avatar_upload?: UploadedFile|null} $data */
    public function update(User|LearnerProfile $user, array $data): void
    {
        $previousAvatar = $user->avatar;
        $storedPath = null;

        if (isset($data['avatar_upload'])) {
            $storedPath = $data['avatar_upload']->store('avatars', 'local');

            if ($storedPath === false) {
                throw new RuntimeException('Avatar gagal disimpan.');
            }

            $avatar = $storedPath;
        } else {
            $avatar = $data['avatar'];
        }

        try {
            $user->update(['avatar' => $avatar]);
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }

        if ($previousAvatar !== $avatar && str_starts_with($previousAvatar ?? '', 'avatars/')) {
            Storage::disk('local')->delete($previousAvatar);
        }
    }
}
