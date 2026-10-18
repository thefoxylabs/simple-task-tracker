<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            @can('manage')
                <a href="{{ route('staff.index') }}" wire:navigate class="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700"><x-icon name="arrow-left" class="size-4" /> Staff</a>
            @endcan
            <div class="flex items-center gap-4">
                <x-avatar :user="$user" size="size-14" class="text-lg ring-0" />
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $user->id === auth()->id() ? 'My work' : $user->name }}</h1>
                    <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                        @if ($user->id === auth()->id()) {{ $user->name }} · @endif
                        {{ $user->job_title ?: $user->role->label() }}
                        @if ($user->group) · {{ $user->group->name }} @endif
                        · {{ $user->email }}
                        @unless ($user->is_active) <span class="badge bg-slate-100 text-slate-600 ring-slate-300">Inactive</span> @endunless
                    </p>
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            @can('manage-user', $user)
                @if ($user->id !== auth()->id())
                    <button type="button" wire:click="$dispatch('edit-staff', { id: {{ $user->id }} })" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Edit</button>
                @endif
            @endcan
            <button type="button" wire:click="$dispatch('create-task', { assigneeId: {{ $user->id }} })" class="btn btn-primary"><x-icon name="plus" class="size-4" /> {{ $user->id === auth()->id() ? 'New task' : 'Assign task' }}</button>
        </div>
    </div>

    <x-period-nav class="mb-6" />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Tasks this {{ $this->periodEnum()->noun() }}" :value="$stats['total']" icon="list" />
        <x-stat label="Completed" :value="$stats['done']" icon="check-circle" tone="emerald" />
        <x-stat label="Still open" :value="$stats['open']" icon="clock" tone="sky" />
        <x-stat label="Overdue" :value="$stats['overdue']" icon="alert" tone="red" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($this->tasks->isEmpty())
                <x-empty-state icon="calendar" title="Nothing due {{ $this->periodEnum() === \App\Enums\Period::Daily ? 'on this day' : 'in this '.$this->periodEnum()->noun() }}" description="Tasks appear here by their deadline, or when they were completed." />
            @else
                @foreach (\App\Enums\TaskStatus::cases() as $status)
                    @continue(! $grouped->has($status->value))
                    <div class="card overflow-hidden" wire:key="sg-{{ $status->value }}">
                        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
                            <span class="size-2 rounded-full {{ $status->dotClasses() }}"></span>
                            <h2 class="text-sm font-semibold text-slate-800">{{ $status->label() }}</h2>
                            <span class="text-xs text-slate-500">{{ $grouped[$status->value]->count() }}</span>
                        </div>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($grouped[$status->value] as $task)
                                <x-task-row :task="$task" wire:key="st-{{ $task->id }}" />
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @endif
        </div>

        <div>
            <div class="card">
                <div class="border-b border-slate-100 px-5 py-3"><h2 class="text-sm font-semibold text-slate-800">Plans in this period</h2></div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($this->plans as $plan)
                        <li class="px-5 py-3">
                            <a href="{{ route('plans.show', $plan) }}" wire:navigate class="block">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="truncate text-sm font-medium text-slate-900 hover:text-brand-700">{{ $plan->title }}</span>
                                    <span class="text-xs text-slate-500">{{ $plan->progress() }}%</span>
                                </span>
                                <span class="mt-0.5 block text-xs text-slate-500">{{ $plan->period->label() }} · {{ $plan->period->describe($plan->starts_on) }}</span>
                                <x-progress class="mt-2" :value="$plan->progress()" />
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-slate-400">No plans for this period.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @can('manage')
        <livewire:staff.staff-form />
    @endcan
</div>
