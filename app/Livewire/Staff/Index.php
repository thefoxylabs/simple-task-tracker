<?php

namespace App\Livewire\Staff;

use App\Enums\Role;
use App\Enums\TaskStatus;
use App\Livewire\Concerns\NavigatesPeriods;
use App\Models\StaffGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Staff')]
class Index extends Component
{
    use NavigatesPeriods;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $group = '';

    #[Url]
    public bool $inactive = false;

    public function boot(): void
    {
        Gate::authorize('manage');
    }

    #[On('staff-saved')]
    public function refresh(): void
    {
        unset($this->staff);
    }

    #[Computed]
    public function staff()
    {
        [$start, $end] = $this->periodRange();

        return User::query()
            ->where('is_active', ! $this->inactive)
            ->when($this->group === 'none', fn ($q) => $q->whereNull('staff_group_id'))
            ->when($this->group !== '' && $this->group !== 'none', fn ($q) => $q->where('staff_group_id', $this->group))
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('job_title', 'like', "%{$this->search}%")))
            ->with('group')
            ->withCount([
                'assignedTasks as period_tasks_count' => fn (Builder $q) => $q->inRange($start, $end),
                'assignedTasks as period_done_count' => fn (Builder $q) => $q->where('status', TaskStatus::Done)->whereBetween('completed_at', [$start, $end]),
                'assignedTasks as open_count' => fn (Builder $q) => $q->open(),
                'assignedTasks as overdue_count' => fn (Builder $q) => $q->overdue(),
            ])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function groups()
    {
        return StaffGroup::orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.staff.index', ['roles' => Role::cases()]);
    }
}
