<?php

namespace App\Livewire\Plans;

use App\Enums\TaskStatus;
use App\Models\Plan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Show extends Component
{
    public Plan $plan;

    public function mount(): void
    {
        Gate::authorize('view', $this->plan);
    }

    public function hydrate(): void
    {
        Gate::authorize('view', $this->plan);
    }

    #[On('plan-saved')]
    #[On('task-saved')]
    public function refresh(): void
    {
        $this->plan->refresh();
        unset($this->tasks);
    }

    #[Computed]
    public function tasks()
    {
        return $this->plan->tasks()
            ->visibleTo(auth()->user())
            ->with(['assignee', 'project'])
            ->orderByRaw('case when status = ? then 1 else 0 end', [TaskStatus::Done->value])
            ->orderBy('due_at')
            ->get();
    }

    public function delete()
    {
        Gate::authorize('manage');

        $this->plan->delete();
        session()->flash('status', 'Plan deleted');

        return $this->redirectRoute('plans.index', navigate: true);
    }

    public function render()
    {
        $tasks = $this->tasks;
        $done = $tasks->where('status', TaskStatus::Done)->count();

        return view('livewire.plans.show', [
            'done' => $done,
            'progress' => $tasks->count() ? (int) round($done / $tasks->count() * 100) : 0,
            'overdue' => $tasks->filter->isOverdue()->count(),
        ])->title($this->plan->title);
    }
}
