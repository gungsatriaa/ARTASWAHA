<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:make-admin-user {email}')]
#[Description('Promosikan akun yang sudah ada menjadi administrator ARTASWAHA.')]
class MakeAdminUser extends Command
{
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('Akun dengan email tersebut tidak ditemukan.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => true])->save();
        $this->info('Akun berhasil diberi akses administrator.');

        return self::SUCCESS;
    }
}
