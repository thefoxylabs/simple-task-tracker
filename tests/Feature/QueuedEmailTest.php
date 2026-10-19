<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Notifications\ResetPassword;
use App\Notifications\StaffInvitation;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskReminder;
use App\Notifications\TestMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueuedEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_emails_are_queued_but_the_smtp_test_is_sent_immediately(): void
    {
        foreach ([TaskAssigned::class, TaskReminder::class, StaffInvitation::class, ResetPassword::class] as $class) {
            $this->assertTrue(is_subclass_of($class, ShouldQueue::class), "$class should be queued");
        }

        $this->assertFalse(is_subclass_of(TestMail::class, ShouldQueue::class));
    }

    public function test_assigning_a_task_queues_the_email_instead_of_sending_it(): void
    {
        Queue::fake();
        $staff = User::factory()->create();

        Task::create(['title' => 'Queued', 'assignee_id' => $staff->id]);

        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notification instanceof TaskAssigned);
    }

    public function test_password_reset_emails_are_queued(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        Password::sendResetLink(['email' => $user->email]);

        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notification instanceof ResetPassword);
    }

    public function test_the_queue_worker_delivers_queued_emails(): void
    {
        config(['queue.default' => 'database', 'mail.default' => 'array']);
        $staff = User::factory()->create(['email' => 'staff@company.test']);

        Task::create(['title' => 'Deliver me', 'assignee_id' => $staff->id]);

        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertCount(0, app('mailer')->getSymfonyTransport()->messages());

        $this->artisan('queue:work', ['--stop-when-empty' => true])->assertSuccessful();

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('New task: Deliver me', $messages[0]->getOriginalMessage()->getSubject());
    }

    public function test_the_scheduler_processes_the_queue(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('queue:work --stop-when-empty')
            ->assertSuccessful();
    }
}
