<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $user = User::query()->firstOrNew(['email' => config('admin.email')]);

        if (! $user->exists) {
            $password = config('admin.password');

            if (! is_string($password) || $password === '') {
                $this->command?->warn('Isi ADMIN_PASSWORD di .env untuk membuat akun admin lokal.');

                return;
            }

            $user->fill(['name' => config('admin.name'), 'password' => $password]);
        }

        $user->forceFill(['is_admin' => true])->save();
    }
}
