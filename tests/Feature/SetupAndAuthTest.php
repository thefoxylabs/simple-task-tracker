<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Setup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class SetupAndAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_install_redirects_to_setup(): void
    {
        $this->get('/')->assertRedirect('/setup');
        $this->get('/login')->assertRedirect('/setup');
        $this->get('/setup')->assertOk()->assertSee('Create workspace');
    }

    public function test_setup_creates_the_first_admin_and_branding(): void
    {
        Livewire::test(Setup::class)
            ->set('app_name', 'Foxy Tasks')
            ->set('company_name', 'Foxy Labs')
            ->set('name', 'Clint Admin')
            ->set('email', 'clint@example.com')
            ->set('password', 'secret-password-123')
            ->set('password_confirmation', 'secret-password-123')
            ->call('save')
            ->assertRedirect(route('dashboard'));

        $admin = User::firstWhere('email', 'clint@example.com');
        $this->assertTrue($admin->isAdmin());
        $this->assertAuthenticatedAs($admin);
        $this->assertSame('Foxy Tasks', settings('app_name'));

        $this->get('/setup')->assertRedirect('/login');
    }

    public function test_setup_cannot_run_twice(): void
    {
        User::factory()->admin()->create();

        Livewire::test(Setup::class)
            ->set('app_name', 'Hijack')
            ->set('name', 'Mallory')
            ->set('email', 'mallory@example.com')
            ->set('password', 'secret-password-123')
            ->set('password_confirmation', 'secret-password-123')
            ->call('save')
            ->assertForbidden();
    }

    public function test_setup_token_is_required_when_configured(): void
    {
        config(['app.setup_token' => 'let-me-in']);

        Livewire::test(Setup::class)
            ->set('name', 'Clint')
            ->set('email', 'clint@example.com')
            ->set('password', 'secret-password-123')
            ->set('password_confirmation', 'secret-password-123')
            ->set('setup_token', 'wrong')
            ->call('save')
            ->assertHasErrors('setup_token');

        $this->assertSame(0, User::count());
    }

    public function test_inactive_staff_cannot_sign_in(): void
    {
        User::factory()->admin()->create();
        User::factory()->create(['email' => 'gone@example.com', 'is_active' => false]);
        Cache::forever('app.installed', true);

        Livewire::test(Login::class)
            ->set('email', 'gone@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }
}
