<?php

namespace Tests\Feature;

use App\Livewire\Staff\StaffForm;
use App\Models\User;
use App\Notifications\StaffInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        $staff = User::factory()->create();

        foreach (['/', '/board', '/projects', '/plans', '/recurring', '/staff', '/groups', '/settings', '/profile'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($manager)->get('/staff')->assertOk();
        $this->actingAs($manager)->get('/settings')->assertForbidden();

        $this->actingAs($staff)->get('/board')->assertOk();
        $this->actingAs($staff)->get('/staff/'.$staff->id)->assertOk();
        $this->actingAs($staff)->get('/staff/'.$manager->id)->assertForbidden();
        $this->actingAs($staff)->get('/staff')->assertForbidden();
        $this->actingAs($staff)->get('/recurring')->assertForbidden();
        $this->actingAs($staff)->get('/settings')->assertForbidden();
    }

    public function test_deactivated_users_are_signed_out(): void
    {
        User::factory()->admin()->create();
        $staff = User::factory()->create(['is_active' => false]);

        $this->actingAs($staff)->get('/')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_manager_can_add_staff_and_send_an_invitation(): void
    {
        Notification::fake();
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)->test(StaffForm::class)
            ->call('create')
            ->set('name', 'New Person')
            ->set('email', 'new@example.com')
            ->set('role', 'staff')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::firstWhere('email', 'new@example.com');
        $this->assertSame('staff', $user->role->value);
        Notification::assertSentTo($user, StaffInvitation::class);
    }

    public function test_manager_cannot_create_admins_or_edit_admins(): void
    {
        $manager = User::factory()->manager()->create();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($manager)->test(StaffForm::class)
            ->call('create')
            ->set('name', 'Sneaky')
            ->set('email', 'sneaky@example.com')
            ->set('role', 'admin')
            ->call('save')
            ->assertHasErrors('role');

        Livewire::actingAs($manager)->test(StaffForm::class)
            ->call('edit', $admin->id)
            ->assertForbidden();
    }

    public function test_admins_cannot_demote_or_deactivate_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(StaffForm::class)
            ->call('edit', $admin->id)
            ->set('role', 'staff')
            ->call('save')
            ->assertHasErrors('role');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_admin_can_promote_staff_to_manager(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        Livewire::actingAs($admin)->test(StaffForm::class)
            ->call('edit', $staff->id)
            ->set('role', 'manager')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($staff->fresh()->isManager());
    }
}
