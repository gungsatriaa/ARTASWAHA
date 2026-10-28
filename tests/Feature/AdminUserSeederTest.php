<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_log_in_and_access_the_dashboard(): void
    {
        config(['admin.email' => 'admin@example.test', 'admin.password' => 'LocalTest!2026']);

        $this->seed(AdminUserSeeder::class);

        $user = User::query()->where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('LocalTest!2026', $user->password));
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'LocalTest!2026'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->get(route('admin.index'))->assertOk();
    }

    public function test_reseeding_preserves_an_existing_accounts_password_and_name(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.test', 'name' => 'Admin Lokal']);
        $originalPassword = $user->password;
        config(['admin.email' => $user->email, 'admin.password' => 'DifferentPassword!2026']);

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
        config(['admin.password' => 'LocalTest!2026']);

        $this->artisan('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_an_empty_password_does_not_create_an_admin(): void
    {
        config(['admin.password' => '']);

        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }
}
