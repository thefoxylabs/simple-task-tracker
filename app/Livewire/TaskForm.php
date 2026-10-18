<?php

namespace App\Livewire;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Attachment;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Uploads;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The global "create / edit task" modal, opened from anywhere with
 * $dispatch('create-task', {...defaults}) or $dispatch('edit-task', { id }).
 */
class TaskForm extends Component
{
    use WithFileUploads;

    /** Reminder presets in minutes before the deadline. */
    public const REMINDERS = ['0' => 'At the deadline', '15' => '15 minutes before', '60' => '1 hour before', '180' => '3 hours before', '1440' => '1 day before', '2880' => '2 days before', '10080' => '1 week before'];

    public bool $open = false;

    #[Locked]
    public ?int $taskId = null;

    public string $title = '';

    public string $description = '';

    public string $status = 'todo';

    public string $priority = 'medium';

    public ?int $project_id = null;

    public ?int $plan_id = null;

    public ?int $assignee_id = null;

    public string $due_at = '';

    public string $reminder = '';

    public string $remind_at = '';

    public array $uploads = [];

    #[On('create-task')]
    public function create(?int $projectId = null, ?string $status = null, ?int $planId = null, ?int $assigneeId = null): void
    {
        $this->resetForm();

        $user = auth()->user();
        $this->project_id = $projectId;
        $this->plan_id = $planId;
        $this->status = TaskStatus::tryFrom((string) $status)?->value ?? 'todo';
        $this->assignee_id = $user->canManage() ? $assigneeId : $user->id;
        $this->reminder = array_key_exists((string) settings('default_reminder_minutes'), self::REMINDERS)
            ? (string) settings('default_reminder_minutes')
            : '60';

        if ($planId && ! $projectId) {
            $this->project_id = Plan::find($planId)?->project_id;
        }

        $this->open = true;
    }

    #[On('edit-task')]
    public function edit(int $id): void
    {
        $task = Task::findOrFail($id);
        Gate::authorize('view', $task);

        $this->resetForm();
        $this->taskId = $task->id;
        $this->title = $task->title;
        $this->description = (string) $task->description;
        $this->status = $task->status->value;
        $this->priority = $task->priority->value;
        $this->project_id = $task->project_id;
        $this->plan_id = $task->plan_id;
        $this->assignee_id = $task->assignee_id;
        $this->due_at = $task->due_at?->format('Y-m-d\TH:i') ?? '';

        if ($task->remind_at) {
            $minutes = $task->due_at ? (string) (int) $task->remind_at->diffInMinutes($task->due_at) : null;
            $this->reminder = $minutes !== null && array_key_exists($minutes, self::REMINDERS) ? $minutes : 'custom';
            $this->remind_at = $task->remind_at->format('Y-m-d\TH:i');
        }

        $this->open = true;
    }

    #[Computed]
    public function task(): ?Task
    {
        return $this->taskId ? Task::with(['attachments.uploader', 'creator', 'recurringTask'])->find($this->taskId) : null;
    }

    /** Whether the current user may change the task's details (not just its status). */
    #[Computed]
    public function canEdit(): bool
    {
        return ! $this->task || Gate::allows('update', $this->task);
    }

    #[Computed]
    public function projects()
    {
        return Project::active()->orWhere('id', $this->project_id)->orderBy('name')->get(['id', 'name', 'color']);
    }

    #[Computed]
    public function plans()
    {
        return Plan::query()
            ->where(fn ($q) => $q->whereDate('ends_on', '>=', today())->orWhere('id', $this->plan_id))
            ->when($this->project_id, fn ($q) => $q->where(fn ($q) => $q->whereNull('project_id')->orWhere('project_id', $this->project_id)))
            ->orderBy('starts_on')
            ->get(['id', 'title', 'period', 'starts_on']);
    }

    #[Computed]
    public function staff()
    {
        return User::active()->orderBy('name')->get(['id', 'name', 'job_title']);
    }

    public function save(): void
    {
        $user = auth()->user();
        $task = $this->task;

        if ($task && ! $this->canEdit) {
            $this->saveStatusOnly($task);

            return;
        }

        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'project_id' => ['nullable', Rule::exists('projects', 'id')],
            'plan_id' => ['nullable', Rule::exists('plans', 'id')],
            'assignee_id' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'due_at' => ['nullable', 'date'],
            'reminder' => ['nullable', Rule::in([...array_keys(self::REMINDERS), 'custom'])],
            'remind_at' => ['nullable', 'required_if:reminder,custom', 'date'],
            'uploads.*' => Uploads::RULES,
        ], [
            'remind_at.required_if' => 'Choose when the reminder should be sent.',
        ]);

        // Staff can only assign tasks to themselves.
        if (! $user->canManage()) {
            $data['assignee_id'] = $task ? $task->assignee_id : $user->id;
        }

        $dueAt = $this->due_at ? Carbon::parse($this->due_at) : null;

        $remindAt = match (true) {
            $this->reminder === '' => null,
            $this->reminder === 'custom' => Carbon::parse($this->remind_at),
            $dueAt !== null => $dueAt->copy()->subMinutes((int) $this->reminder),
            default => null,
        };

        if ($this->reminder !== '' && $this->reminder !== 'custom' && ! $dueAt) {
            $this->addError('due_at', 'Set a deadline so the reminder knows when to go out.');

            return;
        }

        $attributes = [
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'status' => $data['status'],
            'priority' => $data['priority'],
            'project_id' => $data['project_id'] ?: null,
            'plan_id' => $data['plan_id'] ?: null,
            'assignee_id' => $data['assignee_id'] ?: null,
            'due_at' => $dueAt,
            'remind_at' => $remindAt,
        ];

        if ($task) {
            Gate::authorize('update', $task);

            if ($task->status->value !== $attributes['status']) {
                $attributes['position'] = $this->nextPosition($attributes['status']);
            }

            $task->update($attributes);
        } else {
            $task = Task::create($attributes + [
                'created_by' => $user->id,
                'position' => $this->nextPosition($attributes['status']),
            ]);
        }

        $this->storeUploads($task);
        $this->finish($this->taskId ? 'Task updated' : 'Task created');
    }

    /** Assignees who didn't create the task can move it and add files. */
    protected function saveStatusOnly(Task $task): void
    {
        Gate::authorize('updateStatus', $task);

        $this->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'uploads.*' => Uploads::RULES,
        ]);

        if ($task->status->value !== $this->status) {
            $task->update(['status' => $this->status, 'position' => $this->nextPosition($this->status)]);
        }

        $this->storeUploads($task);
        $this->finish('Task updated');
    }

    public function deleteAttachment(int $id): void
    {
        $attachment = Attachment::whereMorphedTo('attachable', $this->task)->findOrFail($id);

        abort_unless($attachment->user_id === auth()->id() || Gate::allows('update', $this->task), 403);

        $attachment->delete();
        unset($this->task);
        $this->dispatch('task-saved');
    }

    public function removeUpload(int $index): void
    {
        array_splice($this->uploads, $index, 1);
    }

    public function delete(): void
    {
        Gate::authorize('delete', $this->task);

        $this->task->delete();
        $this->finish('Task deleted');
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetForm();
    }

    protected function storeUploads(Task $task): void
    {
        foreach ($this->uploads as $file) {
            if (! $task->attach($file, auth()->user())) {
                $this->dispatch('notify', message: 'A file could not be uploaded. Check your storage settings.');
            }
        }
    }

    protected function nextPosition(string $status): int
    {
        return (int) Task::where('status', $status)->max('position') + 1;
    }

    protected function finish(string $message): void
    {
        $this->close();
        $this->dispatch('task-saved');
        $this->dispatch('notify', message: $message);
    }

    protected function resetForm(): void
    {
        $this->reset(['taskId', 'title', 'description', 'status', 'priority', 'project_id', 'plan_id', 'assignee_id', 'due_at', 'reminder', 'remind_at', 'uploads']);
        $this->resetValidation();
        unset($this->task, $this->canEdit);
    }

    public function render()
    {
        return view('livewire.task-form');
    }
}
