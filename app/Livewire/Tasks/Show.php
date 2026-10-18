<?php

namespace App\Livewire\Tasks;

use App\Models\Task;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * A shareable page for a single task, linked from emails.
 */
class Show extends Component
{
    public Task $task;

    public function mount(): void
    {
        Gate::authorize('view', $this->task);
    }

    #[On('task-saved')]
    public function refresh()
    {
        if (! Task::whereKey($this->task->id)->exists()) {
            return $this->redirectRoute('board', navigate: true);
        }

        $this->task->refresh();
    }

    public function render()
    {
        $this->task->load(['project', 'plan', 'assignee', 'creator', 'attachments.uploader']);

        return view('livewire.tasks.show')->title($this->task->title);
    }
}
