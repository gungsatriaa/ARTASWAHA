<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ProductionAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductionAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_seeder_creates_an_admin_that_can_log_in_in_production(): void
    {
        $this->app->instance('env', 'production');
        config(['admin.name' => 'Admin Production', 'admin.email' => 'admin@example.test', 'admin.password' => 'ProductionTest!2026']);

        $this->artisan('db:seed', ['--class' => ProductionAdminSeeder::class, '--force' => true])->assertSuccessful();

        $user = User::query()->where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('ProductionTest!2026', $user->password));
        $this->withSession(['_token' => 'admin-seeder-test-token'])
            ->post(route('login.store'), ['_token' => 'admin-seeder-test-token', 'email' => $user->email, 'password' => 'ProductionTest!2026'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->get(route('admin.index'))->assertOk();
    }

    public function test_reseeding_promotes_one_existing_account_and_preserves_its_name_and_password(): void
    {
        $user = User::factory()->create(['name' => 'Nama Awal', 'email' => 'admin@example.test']);
        $originalPassword = $user->password;
        config(['admin.name' => 'Nama Baru', 'admin.email' => $user->email, 'admin.password' => 'DifferentPassword!2026']);

        $this->seed(ProductionAdminSeeder::class);
        $this->seed(ProductionAdminSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue($user->fresh()->is_admin);
        $this->assertSame('Nama Awal', $user->fresh()->name);
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_default_seeding_runs_all_seeders_without_duplicates_or_external_requests(): void
    {
        $this->app->instance('env', 'production');
        config(['admin.email' => 'admin@example.test', 'admin.password' => 'ProductionTest!2026']);
        config(['services.ai.url' => '', 'services.ai.model' => '']);
        Http::preventStrayRequests();

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['email' => 'admin@example.test', 'is_admin' => true]);
        $this->assertDatabaseCount('learning_paths', 8);
        $this->assertDatabaseCount('lessons', 29);
        $this->assertDatabaseCount('content_audio', 29);
        $this->assertDatabaseCount('ai_reports', 1);
        Http::assertNothingSent();
    }

    #[DataProvider('invalidCredentials')]
    public function test_invalid_credentials_do_not_create_an_account(string $email, string $password, string $field): void
    {
        config(['admin.name' => 'Admin Production', 'admin.email' => $email, 'admin.password' => $password]);

        try {
            (new ProductionAdminSeeder)->run();
            $this->fail('Invalid admin credentials must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }

        $this->assertDatabaseCount('users', 0);
    }

    /** @return array<string, array{string, string, string}> */
    public static function invalidCredentials(): array
    {
        return [
            'missing email' => ['', 'ProductionTest!2026', 'email'],
            'invalid email' => ['not-an-email', 'ProductionTest!2026', 'email'],
            'missing password' => ['admin@example.test', '', 'password'],
            'short password' => ['admin@example.test', 'short', 'password'],
        ];
    }
}
