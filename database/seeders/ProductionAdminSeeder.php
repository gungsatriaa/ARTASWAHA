<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;

class ProductionAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = Validator::make([
            'name' => config('admin.name'),
            'email' => config('admin.email'),
            'password' => config('admin.password'),
        ], [
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12'],
        ])->validate();

        $user = User::query()->firstOrNew(['email' => $data['email']]);

        if (! $user->exists) {
            $user->fill(['name' => $data['name'], 'password' => $data['password']]);
        }

        $user->forceFill(['is_admin' => true])->save();
    }
}
