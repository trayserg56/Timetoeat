<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_admin_user_with_generated_password(): void
    {
        $this->artisan('admin:create', [
            'email' => 'admin@ecocample.ru',
            '--name' => 'Администратор',
        ])
            ->assertSuccessful();

        $user = User::query()->where('email', 'admin@ecocample.ru')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->isAdmin());
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_refuses_to_overwrite_existing_user_without_force(): void
    {
        User::factory()->create(['email' => 'admin@ecocample.ru']);

        $this->artisan('admin:create', [
            'email' => 'admin@ecocample.ru',
        ])
            ->assertFailed();
    }

    public function test_force_updates_existing_user_to_admin(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@ecocample.ru',
            'is_admin' => false,
        ]);

        $this->artisan('admin:create', [
            'email' => 'admin@ecocample.ru',
            '--password' => 'secret-password',
            '--force' => true,
        ])
            ->assertSuccessful();

        $user->refresh();

        $this->assertTrue($user->isAdmin());
    }
}
