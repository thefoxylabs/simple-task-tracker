<?php

namespace App\Livewire\Staff;

use App\Enums\TaskStatus;
use App\Livewire\Concerns\NavigatesPeriods;
use App\Models\Plan;
use App\Models\Task;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * One person's tasks for a day, week, month, quarter or year.
 */
class Show extends Component
{
    use NavigatesPeriods;

    public User $user;

    public function mount(): void
    {
        $this->authorizeViewer();
    }

    public function hydrate(): void
    {
        $this->authorizeViewer();
    }

    /** Staff can only see their own work; managers can see everyone's. */
    protected function authorizeViewer(): void
    {
        $viewer = auth()->user();

        abort_unless($viewer->canManage() || $viewer->id === $this->user->id, 403);
    }

    #[On('task-saved')]
    #[On('staff-saved')]
    public function refresh(): void
    {
        $this->user->refresh();
        unset($this->tasks, $this->plans);
    }

    #[Computed]
    public function tasks()
    {
        [$start, $end] = $this->periodRange();

        return Task::query()
            ->where('assignee_id', $this->user->id)
            ->inRange($start, $end)
            ->with(['project', 'assignee'])
            ->orderBy('due_at')
            ->get();
    }

    #[Computed]
    public function plans()
    {
        [$start, $end] = $this->periodRange();

        return Plan::query()
            ->where(fn ($q) => $q->where('user_id', $this->user->id)->orWhere(fn ($q) => $q->whereNotNull('staff_group_id')->where('staff_group_id', $this->user->staff_group_id)))
            ->whereDate('starts_on', '<=', $end)
            ->whereDate('ends_on', '>=', $start)
            ->withProgress()
            ->orderBy('starts_on')
            ->get();
    }

    public function render()
    {
        $tasks = $this->tasks;

        return view('livewire.staff.show', [
            'stats' => [
                'total' => $tasks->count(),
                'done' => $tasks->where('status', TaskStatus::Done)->count(),
                'open' => $tasks->where('status', '!=', TaskStatus::Done)->count(),
                'overdue' => $tasks->filter->isOverdue()->count(),
            ],
            'grouped' => $tasks->groupBy(fn (Task $task) => $task->status->value),
        ])->title($this->user->name);
    }
}
