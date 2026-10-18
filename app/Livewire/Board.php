<?php

namespace App\Livewire;

use App\Enums\Period;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\StaffGroup;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The Kanban board. Shows every task (or only a project's tasks when opened
 * from a project) with filters by person, group, priority and time period.
 */
class Board extends Component
{
    public ?Project $project = null;

    #[Url]
    public string $view = 'board';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $assignee = '';

    #[Url]
    public string $group = '';

    #[Url(as: 'project')]
    public string $projectFilter = '';

    #[Url]
    public string $priority = '';

    #[Url]
    public string $period = '';

    public function mount(?Project $project = null): void
    {
        $this->project = $project?->exists ? $project : null;
    }

    #[On('task-saved')]
    #[On('project-saved')]
    public function refresh(): void
    {
        unset($this->columns, $this->stats);

        if ($this->project) {
            $this->project->refresh();
        }
    }

    protected function query(): Builder
    {
        $range = Period::tryFrom($this->period)?->range(now());

        return Task::query()
            ->visibleTo(auth()->user())
            ->with(['assignee', 'project:id,name,color', 'recurringTask:id,frequency'])
            ->withCount('attachments')
            ->when($this->project, fn ($q) => $q->where('project_id', $this->project->id))
            ->when(! $this->project && $this->projectFilter !== '', fn ($q) => $this->projectFilter === 'none'
                ? $q->whereNull('project_id')
                : $q->where('project_id', $this->projectFilter))
            ->when(! $this->project && $this->projectFilter === '', fn ($q) => $q->where(fn ($q) => $q
                ->whereNull('project_id')
                ->orWhereHas('project', fn ($q) => $q->active())))
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")))
            ->when($this->assignee === 'none', fn ($q) => $q->whereNull('assignee_id'))
            ->when($this->assignee !== '' && $this->assignee !== 'none', fn ($q) => $q->where('assignee_id', $this->assignee))
            ->when($this->group, fn ($q) => $q->whereHas('assignee', fn ($q) => $q->where('staff_group_id', $this->group)))
            ->when($this->priority, fn ($q) => $q->where('priority', $this->priority))
            ->when($range, fn ($q) => $q->inRange(...$range));
    }

    #[Computed]
    public function columns(): array
    {
        $tasks = $this->query()->orderBy('position')->orderByDesc('id')->get()->groupBy(fn (Task $task) => $task->status->value);

        return collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $status) => [$status->value => $tasks->get($status->value, collect())])
            ->all();
    }

    #[Computed]
    public function stats(): array
    {
        $tasks = collect($this->columns)->flatten();

        return [
            'total' => $tasks->count(),
            'done' => $tasks->where('status', TaskStatus::Done)->count(),
            'overdue' => $tasks->filter->isOverdue()->count(),
        ];
    }

    #[Computed]
    public function people()
    {
        return User::active()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function groups()
    {
        return StaffGroup::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function projects()
    {
        return Project::active()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Called by wire:sort when a card is dropped in a column.
     */
    public function moveTask(int $id, int $position, string $status): void
    {
        $task = Task::findOrFail($id);
        Gate::authorize('updateStatus', $task);

        $status = TaskStatus::from($status);

        $ids = $this->query()
            ->where('status', $status)
            ->whereKeyNot($task->id)
            ->orderBy('position')
            ->orderByDesc('id')
            ->pluck('id');

        $ids->splice(max(0, min($position, $ids->count())), 0, [$task->id]);

        if ($task->status !== $status) {
            $task->update(['status' => $status]);
        }

        foreach ($ids->values() as $index => $taskId) {
            Task::whereKey($taskId)->update(['position' => $index]);
        }

        unset($this->columns, $this->stats);
    }

    public function archiveProject(): void
    {
        Gate::authorize('manage');

        $this->project->update(['archived_at' => $this->project->isArchived() ? null : now()]);
        $this->dispatch('notify', message: $this->project->isArchived() ? 'Project archived' : 'Project restored');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'assignee', 'group', 'projectFilter', 'priority', 'period']);
    }

    public function render()
    {
        return view('livewire.board', [
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
            'periods' => Period::cases(),
        ])->title($this->project?->name ?? 'Board');
    }
}
