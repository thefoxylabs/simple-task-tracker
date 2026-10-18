<?php

namespace App\Livewire;

use App\Enums\Period;
use App\Enums\TaskStatus;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    #[On('task-saved')]
    public function refresh(): void
    {
        // Re-render with fresh numbers.
    }

    public function render()
    {
        $user = auth()->user();
        $visible = fn () => Task::query()->visibleTo($user);
        [$weekStart, $weekEnd] = Period::Weekly->range(now());

        $stats = [
            'open' => $visible()->open()->count(),
            'today' => $visible()->open()->whereBetween('due_at', [today(), today()->endOfDay()])->count(),
            'overdue' => $visible()->overdue()->count(),
            'done_week' => $visible()->where('status', TaskStatus::Done)->whereBetween('completed_at', [$weekStart, $weekEnd])->count(),
        ];

        $myTasks = Task::query()
            ->where('assignee_id', $user->id)
            ->open()
            ->with(['project', 'assignee'])
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->limit(8)
            ->get();

        $overdue = $user->canManage()
            ? Task::overdue()->with(['project', 'assignee'])->orderBy('due_at')->limit(6)->get()
            : collect();

        $projects = Project::active()
            ->unless($user->canManage(), fn ($q) => $q->whereHas('tasks', fn ($q) => $q->visibleTo($user)))
            ->withProgress()
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $plans = Plan::query()
            ->whereDate('starts_on', '<=', today())
            ->whereDate('ends_on', '>=', today())
            ->unless($user->canManage(), fn ($q) => $q->where(fn ($q) => $q
                ->where('user_id', $user->id)
                ->orWhere(fn ($q) => $q->whereNotNull('staff_group_id')->where('staff_group_id', $user->staff_group_id))
                ->orWhere(fn ($q) => $q->whereNull('user_id')->whereNull('staff_group_id'))))
            ->withProgress()
            ->orderBy('ends_on')
            ->limit(5)
            ->get();

        $team = $user->canManage()
            ? User::active()
                ->withCount([
                    'assignedTasks as open_count' => fn (Builder $q) => $q->open(),
                    'assignedTasks as overdue_count' => fn (Builder $q) => $q->overdue(),
                    'assignedTasks as done_week_count' => fn (Builder $q) => $q->where('status', TaskStatus::Done)->whereBetween('completed_at', [$weekStart, $weekEnd]),
                ])
                ->orderByDesc('open_count')
                ->limit(6)
                ->get()
            : collect();

        return view('livewire.dashboard', compact('stats', 'myTasks', 'overdue', 'projects', 'plans', 'team'));
    }
}
