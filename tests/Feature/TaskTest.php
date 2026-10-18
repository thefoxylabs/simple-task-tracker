<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\Board;
use App\Livewire\TaskForm;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->manager = User::factory()->manager()->create();
        $this->staff = User::factory()->create();
    }

    public function test_manager_creates_task_with_deadline_and_reminder_and_assignee_is_emailed(): void
    {
        $project = Project::create(['name' => 'Launch']);

        Livewire::actingAs($this->manager)
            ->test(TaskForm::class)
            ->call('create')
            ->set('title', 'Write launch email')
            ->set('priority', 'high')
            ->set('project_id', $project->id)
            ->set('assignee_id', $this->staff->id)
            ->set('due_at', '2030-05-10T17:00')
            ->set('reminder', '1440')
            ->call('save')
            ->assertHasNoErrors();

        $task = Task::firstWhere('title', 'Write launch email');
        $this->assertSame($this->staff->id, $task->assignee_id);
        $this->assertSame($this->manager->id, $task->created_by);
        $this->assertSame('2030-05-10 17:00', $task->due_at->format('Y-m-d H:i'));
        $this->assertSame('2030-05-09 17:00', $task->remind_at->format('Y-m-d H:i'));

        Notification::assertSentTo($this->staff, TaskAssigned::class);
    }

    public function test_reminder_without_deadline_is_rejected(): void
    {
        Livewire::actingAs($this->manager)
            ->test(TaskForm::class)
            ->call('create')
            ->set('title', 'No deadline')
            ->set('reminder', '60')
            ->call('save')
            ->assertHasErrors('due_at');
    }

    public function test_staff_can_only_assign_tasks_to_themselves(): void
    {
        Livewire::actingAs($this->staff)
            ->test(TaskForm::class)
            ->call('create')
            ->set('title', 'My own task')
            ->set('assignee_id', $this->manager->id)
            ->set('reminder', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($this->staff->id, Task::firstWhere('title', 'My own task')->assignee_id);
    }

    public function test_assignee_can_change_status_and_attach_proof_but_not_the_deadline(): void
    {
        Storage::fake('local');

        $task = Task::create([
            'title' => 'Deliver report',
            'assignee_id' => $this->staff->id,
            'created_by' => $this->manager->id,
            'due_at' => '2030-01-01 12:00',
        ]);

        Livewire::actingAs($this->staff)
            ->test(TaskForm::class)
            ->call('edit', $task->id)
            ->set('status', 'done')
            ->set('due_at', '2031-01-01T12:00')
            ->set('uploads', [UploadedFile::fake()->create('proof.pdf', 120, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors();

        $task->refresh();
        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertSame('2030-01-01 12:00', $task->due_at->format('Y-m-d H:i'));
        $this->assertCount(1, $task->attachments);
        Storage::disk('local')->assertExists($task->attachments->first()->path);
    }

    public function test_staff_cannot_open_other_peoples_tasks(): void
    {
        $task = Task::create(['title' => 'Secret', 'assignee_id' => $this->manager->id, 'created_by' => $this->manager->id]);

        Livewire::actingAs($this->staff)
            ->test(TaskForm::class)
            ->call('edit', $task->id)
            ->assertForbidden();
    }

    public function test_board_only_shows_staff_their_own_tasks(): void
    {
        Task::create(['title' => 'Mine', 'assignee_id' => $this->staff->id]);
        Task::create(['title' => 'Someone else', 'assignee_id' => $this->manager->id]);

        Livewire::actingAs($this->staff)->test(Board::class)
            ->assertSee('Mine')
            ->assertDontSee('Someone else');

        Livewire::actingAs($this->manager)->test(Board::class)
            ->assertSee('Mine')
            ->assertSee('Someone else');
    }

    public function test_dragging_a_card_changes_its_status_and_order(): void
    {
        $a = Task::create(['title' => 'A', 'status' => 'in_progress', 'position' => 0]);
        $b = Task::create(['title' => 'B', 'status' => 'in_progress', 'position' => 1]);
        $moved = Task::create(['title' => 'Moved', 'status' => 'todo', 'position' => 0]);

        Livewire::actingAs($this->manager)->test(Board::class)
            ->call('moveTask', $moved->id, 1, 'in_progress');

        $this->assertSame(TaskStatus::InProgress, $moved->fresh()->status);
        $this->assertSame(['A', 'Moved', 'B'], Task::where('status', 'in_progress')->orderBy('position')->pluck('title')->all());
    }

    public function test_board_filters_by_period(): void
    {
        Task::create(['title' => 'Due today', 'due_at' => now()->setTime(18, 0)]);
        Task::create(['title' => 'Due next year', 'due_at' => now()->addYear()]);

        Livewire::actingAs($this->manager)->test(Board::class)
            ->set('period', 'daily')
            ->assertSee('Due today')
            ->assertDontSee('Due next year');
    }

    public function test_archived_project_tasks_are_hidden_from_the_main_board(): void
    {
        $project = Project::create(['name' => 'Old', 'archived_at' => now()]);
        Task::create(['title' => 'Archived work', 'project_id' => $project->id]);

        Livewire::actingAs($this->manager)->test(Board::class)->assertDontSee('Archived work');
        Livewire::actingAs($this->manager)->test(Board::class, ['project' => $project])->assertSee('Archived work');
    }

    public function test_reminders_are_emailed_once_when_due(): void
    {
        $due = Task::create(['title' => 'Due soon', 'assignee_id' => $this->staff->id, 'due_at' => now()->addHour(), 'remind_at' => now()->subMinute()]);
        Task::create(['title' => 'Later', 'assignee_id' => $this->staff->id, 'due_at' => now()->addDays(3), 'remind_at' => now()->addDays(2)]);
        Task::create(['title' => 'Done already', 'status' => 'done', 'assignee_id' => $this->staff->id, 'remind_at' => now()->subMinute()]);

        $this->artisan('tasks:send-reminders')->assertSuccessful();
        $this->artisan('tasks:send-reminders')->assertSuccessful();

        Notification::assertSentToTimes($this->staff, TaskReminder::class, 1);
        $this->assertNotNull($due->fresh()->reminder_sent_at);
    }

    public function test_changing_the_reminder_time_rearms_it(): void
    {
        $task = Task::create(['title' => 'Rearm', 'assignee_id' => $this->staff->id, 'remind_at' => now()->subMinute()]);
        $this->artisan('tasks:send-reminders');
        $this->assertNotNull($task->fresh()->reminder_sent_at);

        $task->update(['remind_at' => now()->addDay()]);

        $this->assertNull($task->fresh()->reminder_sent_at);
    }
}
