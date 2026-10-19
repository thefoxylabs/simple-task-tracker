<div>
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    @endphp
    <x-page-header :title="$greeting.', '.\Illuminate\Support\Str::before(auth()->user()->name, ' ')" :description="now()->format('l, j F Y').(auth()->user()->canManage() ? ' · Company overview' : ' · Your overview')">
        <x-slot:actions>
            <a href="{{ route('board') }}" wire:navigate class="btn btn-secondary"><x-icon name="board" class="size-4" /> Open board</a>
            <button type="button" wire:click="$dispatch('create-task')" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New task</button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Open tasks" :value="$stats['open']" icon="list" />
        <x-stat label="Due today" :value="$stats['today']" icon="clock" tone="amber" />
        <x-stat label="Overdue" :value="$stats['overdue']" icon="alert" tone="red" />
        <x-stat label="Completed this week" :value="$stats['done_week']" icon="check-circle" tone="emerald" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-900">My tasks</h2>
                    <a href="{{ route('staff.show', auth()->user()) }}" wire:navigate class="text-sm font-medium text-brand-600 hover:text-brand-700">View all</a>
                </div>
                @if ($myTasks->isEmpty())
                    <div class="p-6"><x-empty-state title="You're all caught up" description="Nothing is assigned to you right now." /></div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($myTasks as $task)
                            <x-task-row :task="$task" wire:key="my-{{ $task->id }}" />
                        @endforeach
                    </ul>
                @endif
            </div>

            @if (auth()->user()->canManage())
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="alert" class="size-5 text-red-500" /> Overdue across the team</h2>
                    </div>
                    @if ($overdue->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-slate-500">Nothing overdue. Nice work, team!</p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($overdue as $task)
                                <x-task-row :task="$task" wire:key="od-{{ $task->id }}" />
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-900">Current plans</h2>
                    <a href="{{ route('plans.index') }}" wire:navigate class="text-sm font-medium text-brand-600 hover:text-brand-700">All plans</a>
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($plans as $plan)
                        <li class="px-5 py-3">
                            <a href="{{ route('plans.show', $plan) }}" wire:navigate class="block">
                                <span class="flex items-center justify-between gap-2 text-sm">
                                    <span class="truncate font-medium text-slate-900 hover:text-brand-700">{{ $plan->title }}</span>
                                    <span class="badge bg-slate-50 text-slate-600 ring-slate-500/20">{{ $plan->period->label() }}</span>
                                </span>
                                <x-progress class="mt-2" :value="$plan->progress()" />
                                <span class="mt-1 block text-xs text-slate-500">{{ $plan->progress() }}% · {{ $plan->done_tasks_count }}/{{ $plan->tasks_count }} tasks · ends {{ $plan->ends_on->format('j M') }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-slate-400">No active plans.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-900">Projects</h2>
                    <a href="{{ route('projects.index') }}" wire:navigate class="text-sm font-medium text-brand-600 hover:text-brand-700">All projects</a>
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($projects as $project)
                        <li class="px-5 py-3">
                            <a href="{{ route('projects.show', $project) }}" wire:navigate class="block">
                                <span class="flex items-center justify-between gap-2 text-sm">
                                    <span class="flex min-w-0 items-center gap-2"><span class="size-2 shrink-0 rounded-full" style="background-color: {{ $project->color }}"></span><span class="truncate font-medium text-slate-900 hover:text-brand-700">{{ $project->name }}</span></span>
                                    <span class="text-xs text-slate-500">{{ $project->progress() }}%</span>
                                </span>
                                <x-progress class="mt-2" :value="$project->progress()" :color="$project->color" />
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-slate-400">No active projects.</li>
                    @endforelse
                </ul>
            </div>

            @if ($team->isNotEmpty())
                <div class="card">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="font-semibold text-slate-900">Team workload</h2>
                        <a href="{{ route('staff.index') }}" wire:navigate class="text-sm font-medium text-brand-600 hover:text-brand-700">All staff</a>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($team as $member)
                            <li>
                                <a href="{{ route('staff.show', $member) }}" wire:navigate class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50">
                                    <x-avatar :user="$member" class="ring-0" />
                                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-900">{{ $member->name }}</span>
                                    <span class="text-xs text-slate-500">{{ $member->open_count }} open</span>
                                    @if ($member->overdue_count)<span class="text-xs font-semibold text-red-600">{{ $member->overdue_count }} late</span>@endif
                                    <span class="text-xs text-emerald-700">{{ $member->done_week_count }} done</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
