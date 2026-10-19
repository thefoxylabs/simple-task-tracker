<?php

namespace Tests\Feature;

use App\Livewire\SystemStatusPage;
use App\Models\User;
use App\Support\SystemStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SystemStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_see_system_status(): void
    {
        $this->actingAs(User::factory()->manager()->create())->get('/settings/status')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/settings/status')->assertOk()->assertSee('System status');
    }

    public function test_live_checks_test_storage_and_report_each_area(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(SystemStatusPage::class)
            ->call('runLiveChecks')
            ->assertSee('Database')
            ->assertSee('File uploads')
            ->assertSee('A test file was written, read and deleted')
            ->assertSee('Scheduler');
    }

    public function test_scheduler_check_uses_the_heartbeat(): void
    {
        $checks = fn () => app(SystemStatus::class)->checks()['Email & background jobs']['scheduler'];

        $this->assertSame('error', $checks()['status']);

        Cache::forever(SystemStatus::HEARTBEAT, now()->timestamp);
        $this->assertSame('ok', $checks()['status']);

        Cache::forever(SystemStatus::HEARTBEAT, now()->subMinutes(10)->timestamp);
        $this->assertSame('error', $checks()['status']);
    }

    public function test_failed_emails_are_reported(): void
    {
        config(['mail.default' => 'smtp', 'mail.from.address' => 'tasks@company.test']);
        SystemStatus::recordMailFailure(new \RuntimeException('Authentication failed'));

        $check = app(SystemStatus::class)->checks()['Email & background jobs']['mail_config'];

        $this->assertSame('error', $check['status']);
        $this->assertStringContainsString('Authentication failed', $check['message']);
    }

    public function test_stuck_queue_is_reported(): void
    {
        config(['queue.default' => 'database']);
        DB::table('jobs')->insert([
            'queue' => 'default', 'payload' => '{}', 'attempts' => 0,
            'available_at' => now()->subHour()->timestamp, 'created_at' => now()->subHour()->timestamp,
        ]);

        $check = app(SystemStatus::class)->checks()['Email & background jobs']['queue'];

        $this->assertSame('error', $check['status']);
    }

    public function test_admins_see_a_dashboard_warning_when_checks_fail(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/')->assertSee('View system status');
        $this->actingAs(User::factory()->create())->get('/')->assertDontSee('View system status');
    }
}
