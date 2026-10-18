<?php

namespace Tests\Feature;

use App\Models\RecurringTask;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecurringTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_the_due_task_and_schedules_the_next_one(): void
    {
        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-10-19 08:00'));

        $user = User::factory()->create();
        $schedule = RecurringTask::create([
            'title' => 'Weekly report',
            'frequency' => 'weekly',
            'assignee_id' => $user->id,
            'starts_on' => '2026-10-19',
            'next_run_on' => '2026-10-19',
            'due_time' => '16:00',
            'remind_before_minutes' => 60,
        ]);

        $this->artisan('tasks:generate-recurring')->assertSuccessful();
        $this->artisan('tasks:generate-recurring')->assertSuccessful();

        $this->assertCount(1, $schedule->tasks);
        $task = $schedule->tasks->first();
        $this->assertSame('2026-10-19 16:00', $task->due_at->format('Y-m-d H:i'));
        $this->assertSame('2026-10-19 15:00', $task->remind_at->format('Y-m-d H:i'));
        $this->assertSame($user->id, $task->assignee_id);
        $this->assertSame('2026-10-26', $schedule->fresh()->next_run_on->toDateString());
    }

    public function test_missed_occurrences_are_skipped_instead_of_flooding(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 08:00'));

        $schedule = RecurringTask::create([
            'title' => 'Daily check',
            'frequency' => 'daily',
            'starts_on' => '2026-10-10',
            'next_run_on' => '2026-10-10',
        ]);

        $this->artisan('tasks:generate-recurring');

        $this->assertCount(1, $schedule->tasks);
        $this->assertSame('2026-10-20', $schedule->tasks->first()->due_at->toDateString());
        $this->assertSame('2026-10-21', $schedule->fresh()->next_run_on->toDateString());
    }

    public function test_stops_after_the_end_date(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-12-01'));

        $schedule = RecurringTask::create([
            'title' => 'Ended',
            'frequency' => 'monthly',
            'starts_on' => '2026-11-01',
            'next_run_on' => '2026-12-01',
            'ends_on' => '2026-11-30',
        ]);

        $this->artisan('tasks:generate-recurring');

        $this->assertCount(0, $schedule->tasks);
    }
}
