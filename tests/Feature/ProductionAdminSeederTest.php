<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ProductionAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class ProductionAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_seeder_creates_an_admin_that_can_log_in_in_production(): void
    {
        $this->app->instance('env', 'production');

        $output = new BufferedOutput;
        Artisan::call('db:seed', ['--class' => ProductionAdminSeeder::class, '--force' => true, '--no-interaction' => true], $output);
        preg_match('/Kata sandi awal admin: ([^\r\n]+)/', $output->fetch(), $matches);
        $password = $matches[1];

        $user = User::query()->where('email', 'admin@artaswaha.com')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->withSession(['_token' => 'admin-seeder-test-token'])
            ->post(route('login.store'), ['_token' => 'admin-seeder-test-token', 'email' => $user->email, 'password' => $password])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->get(route('admin.index'))->assertOk();
    }

    public function test_reseeding_promotes_one_existing_account_and_preserves_its_name_and_password(): void
    {
        $user = User::factory()->create(['name' => 'Nama Awal', 'email' => 'admin@artaswaha.com']);
        $originalPassword = $user->password;

        $this->seed(ProductionAdminSeeder::class);
        $this->seed(ProductionAdminSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue($user->fresh()->is_admin);
        $this->assertSame('Nama Awal', $user->fresh()->name);
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_default_seeding_does_not_create_a_production_admin(): void
    {
        $this->app->instance('env', 'production');

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_credentials_come_from_the_seeder_instead_of_environment_configuration(): void
    {
        config(['admin.name' => 'Ignored', 'admin.email' => 'ignored@example.test', 'admin.password' => 'IgnoredPassword!2026']);
        $this->seed(ProductionAdminSeeder::class);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['name' => 'Admin ARTASWAHA', 'email' => 'admin@artaswaha.com', 'is_admin' => true]);
    }
}
