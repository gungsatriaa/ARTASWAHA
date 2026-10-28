<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductionAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = Validator::make([
            'name' => 'Admin ARTASWAHA',
            'email' => 'admin@artaswaha.com',
            'password' => Str::password(24),
        ], [
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12'],
        ])->validate();

        $user = User::query()->firstOrNew(['email' => $data['email']]);

        $created = ! $user->exists;
        if ($created) {
            $user->fill(['name' => $data['name'], 'password' => $data['password']]);
        }

        $user->forceFill(['is_admin' => true])->save();

        if ($created) {
            $this->command?->info('Email admin: '.$user->email);
            $this->command?->info('Kata sandi awal admin: '.$data['password']);
        }
    }
}
