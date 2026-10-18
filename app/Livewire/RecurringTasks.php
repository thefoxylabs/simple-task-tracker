<?php

namespace App\Livewire;

use App\Enums\Period;
use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\RecurringTask;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Recurring tasks')]
class RecurringTasks extends Component
{
    public bool $open = false;

    #[Locked]
    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $frequency = 'weekly';

    public string $priority = 'medium';

    public ?int $project_id = null;

    public ?int $assignee_id = null;

    public string $starts_on = '';

    public string $ends_on = '';

    public string $due_time = '17:00';

    public string $reminder = '60';

    #[Computed]
    public function schedules()
    {
        return RecurringTask::with(['assignee', 'project'])->withCount('tasks')->orderByDesc('is_active')->orderBy('title')->get();
    }

    #[Computed]
    public function options(): array
    {
        return [
            'projects' => Project::active()->orWhere('id', $this->project_id)->orderBy('name')->get(['id', 'name']),
            'people' => User::active()->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->starts_on = today()->toDateString();
        $this->open = true;
    }

    public function edit(int $id): void
    {
        $schedule = RecurringTask::findOrFail($id);

        $this->resetForm();
        $this->editingId = $schedule->id;
        $this->fill($schedule->only(['title', 'project_id', 'assignee_id']));
        $this->description = (string) $schedule->description;
        $this->frequency = $schedule->frequency->value;
        $this->priority = $schedule->priority->value;
        $this->starts_on = $schedule->next_run_on->toDateString();
        $this->ends_on = $schedule->ends_on?->toDateString() ?? '';
        $this->due_time = substr($schedule->due_time, 0, 5);
        $this->reminder = $schedule->remind_before_minutes === null ? '' : (string) $schedule->remind_before_minutes;
        $this->open = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'frequency' => ['required', Rule::enum(Period::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'project_id' => ['nullable', Rule::exists('projects', 'id')],
            'assignee_id' => ['nullable', Rule::exists('users', 'id')],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'due_time' => ['required', 'date_format:H:i'],
            'reminder' => ['nullable', Rule::in(array_keys(TaskForm::REMINDERS))],
        ]);

        $attributes = [
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'frequency' => $data['frequency'],
            'priority' => $data['priority'],
            'project_id' => $data['project_id'] ?: null,
            'assignee_id' => $data['assignee_id'] ?: null,
            'ends_on' => $data['ends_on'] ?: null,
            'due_time' => $data['due_time'],
            'remind_before_minutes' => $data['reminder'] === '' || $data['reminder'] === null ? null : (int) $data['reminder'],
            'next_run_on' => $data['starts_on'],
        ];

        if ($this->editingId) {
            RecurringTask::findOrFail($this->editingId)->update($attributes);
        } else {
            $schedule = RecurringTask::create($attributes + ['starts_on' => $data['starts_on'], 'created_by' => auth()->id()]);
            // Create today's task straight away if the schedule starts today.
            $schedule->generateDueTask();
        }

        $this->dispatch('notify', message: $this->editingId ? 'Schedule updated' : 'Recurring task created');
        $this->close();
    }

    public function toggle(int $id): void
    {
        $schedule = RecurringTask::findOrFail($id);
        $schedule->update(['is_active' => ! $schedule->is_active]);
        unset($this->schedules);
    }

    public function delete(int $id): void
    {
        RecurringTask::findOrFail($id)->delete();
        unset($this->schedules);
        $this->dispatch('notify', message: 'Schedule deleted. Tasks already created were kept.');
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetForm();
        unset($this->schedules);
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'description', 'frequency', 'priority', 'project_id', 'assignee_id', 'starts_on', 'ends_on', 'due_time', 'reminder']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.recurring-tasks');
    }
}
