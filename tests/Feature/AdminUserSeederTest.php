<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_log_in_and_access_the_dashboard(): void
    {
        $output = new BufferedOutput;
        Artisan::call('db:seed', ['--class' => AdminUserSeeder::class, '--no-interaction' => true], $output);
        preg_match('/Kata sandi awal admin: ([^\r\n]+)/', $output->fetch(), $matches);
        $password = $matches[1];

        $user = User::query()->where('email', 'admin@artaswaha.com')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->post(route('login.store'), ['email' => $user->email, 'password' => $password])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->get(route('admin.index'))->assertOk();
    }

    public function test_reseeding_preserves_an_existing_accounts_password_and_name(): void
    {
        $user = User::factory()->create(['email' => 'admin@artaswaha.com', 'name' => 'Admin Lokal']);
        $originalPassword = $user->password;

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue($user->fresh()->is_admin);
        $this->assertSame($originalPassword, $user->fresh()->password);
        $this->assertSame('Admin Lokal', $user->fresh()->name);
    }

    public function test_admin_is_not_seeded_in_production(): void
    {
        $this->app->instance('env', 'production');

        $this->artisan('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeding_does_not_depend_on_admin_configuration(): void
    {
        config(['admin.name' => '', 'admin.email' => '', 'admin.password' => '']);
        $this->seed(AdminUserSeeder::class);
        $this->assertDatabaseHas('users', ['email' => 'admin@artaswaha.com', 'is_admin' => true]);
    }
}
