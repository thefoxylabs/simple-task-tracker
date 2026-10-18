<div>
    @if ($project)
        <x-page-header :title="$project->name" :description="$project->description">
            <x-slot:before>
                <a href="{{ route('projects.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                    <x-icon name="arrow-left" class="size-4" /> Projects
                </a>
            </x-slot:before>
            <x-slot:actions>
                @can('manage')
                    <button type="button" wire:click="$dispatch('edit-project', { id: {{ $project->id }} })" class="btn btn-secondary">
                        <x-icon name="pencil" class="size-4" /> Edit
                    </button>
                    <button type="button" wire:click="archiveProject" class="btn btn-secondary"
                            wire:confirm="{{ $project->isArchived() ? 'Restore this project?' : 'Archive this project? It will be hidden from the board and lists.' }}">
                        <x-icon name="archive" class="size-4" /> {{ $project->isArchived() ? 'Restore' : 'Archive' }}
                    </button>
                @endcan
                <button type="button" wire:click="$dispatch('create-task', { projectId: {{ $project->id }} })" class="btn btn-primary">
                    <x-icon name="plus" class="size-4" /> Add task
                </button>
            </x-slot:actions>
        </x-page-header>

        @if ($project->isArchived())
            <div class="mb-5 flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-600/20">
                <x-icon name="archive" class="size-4" /> This project was archived {{ $project->archived_at->diffForHumans() }}.
            </div>
        @endif

        <div class="mb-6 grid gap-4 lg:grid-cols-3">
            <div class="card p-5 lg:col-span-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-slate-700">Progress</span>
                    <span class="text-slate-500">{{ $this->stats['done'] }} of {{ $this->stats['total'] }} tasks done</span>
                </div>
                <x-progress class="mt-3 h-2" :value="$this->stats['total'] ? round($this->stats['done'] / $this->stats['total'] * 100) : 0" :color="$project->color" />
                <dl class="mt-4 flex flex-wrap gap-x-8 gap-y-2 text-sm">
                    <div class="flex items-center gap-2"><dt class="text-slate-500">Lead</dt><dd class="font-medium text-slate-800">{{ $project->lead?->name ?? '—' }}</dd></div>
                    @if ($project->starts_on)<div class="flex items-center gap-2"><dt class="text-slate-500">Start</dt><dd class="font-medium text-slate-800">{{ $project->starts_on->format('j M Y') }}</dd></div>@endif
                    @if ($project->due_on)<div class="flex items-center gap-2"><dt class="text-slate-500">Due</dt><dd class="font-medium {{ $project->due_on->isPast() && ! $project->isArchived() ? 'text-red-600' : 'text-slate-800' }}">{{ $project->due_on->format('j M Y') }}</dd></div>@endif
                    <div class="flex items-center gap-2"><dt class="text-slate-500">Overdue</dt><dd class="font-medium {{ $this->stats['overdue'] ? 'text-red-600' : 'text-slate-800' }}">{{ $this->stats['overdue'] }}</dd></div>
                </dl>
            </div>
            <div class="card p-5">
                <p class="text-sm font-medium text-slate-700">Project files</p>
                <ul class="mt-3 space-y-2">
                    @forelse ($project->attachments as $file)
                        <li class="flex items-center gap-2 text-sm">
                            <x-icon name="folder" class="size-4 text-slate-400" />
                            <a href="{{ route('attachments.download', $file) }}" class="min-w-0 flex-1 truncate text-brand-700 hover:underline">{{ $file->name }}</a>
                            <span class="text-xs text-slate-400">{{ $file->humanSize() }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">No files yet.@can('manage') Add briefs or specs via Edit.@endcan</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @else
        <x-page-header title="Board" :description="auth()->user()->canManage() ? 'Every task across the company, at a glance.' : 'Tasks assigned to you or created by you.'">
            <x-slot:actions>
                <button type="button" wire:click="$dispatch('create-task')" class="btn btn-primary">
                    <x-icon name="plus" class="size-4" /> New task
                </button>
            </x-slot:actions>
        </x-page-header>
    @endif

    {{-- Filters --}}
    <div class="mb-5 flex flex-wrap items-center gap-2">
        <div class="relative w-full sm:w-64">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search tasks…" class="input pl-9">
        </div>

        <div class="flex rounded-lg bg-slate-100 p-1">
            <button type="button" wire:click="$set('period', '')" class="{{ $period === '' ? 'tab tab-active' : 'tab' }}">All</button>
            @foreach ($periods as $p)
                <button type="button" wire:click="$set('period', '{{ $p->value }}')" class="{{ $period === $p->value ? 'tab tab-active' : 'tab' }}">
                    {{ $p === \App\Enums\Period::Daily ? 'Today' : 'This '.$p->noun() }}
                </button>
            @endforeach
        </div>

        @can('manage')
            <select wire:model.live="assignee" class="input w-auto">
                <option value="">Everyone</option>
                <option value="none">Unassigned</option>
                @foreach ($this->people as $person)
                    <option value="{{ $person->id }}">{{ $person->name }}</option>
                @endforeach
            </select>
            @if ($this->groups->isNotEmpty())
                <select wire:model.live="group" class="input w-auto">
                    <option value="">All groups</option>
                    @foreach ($this->groups as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
            @endif
        @endcan

        @unless ($project)
            <select wire:model.live="projectFilter" class="input w-auto">
                <option value="">All projects</option>
                <option value="none">No project</option>
                @foreach ($this->projects as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        @endunless

        <select wire:model.live="priority" class="input w-auto">
            <option value="">Any priority</option>
            @foreach ($priorities as $p)
                <option value="{{ $p->value }}">{{ $p->label() }}</option>
            @endforeach
        </select>

        @if ($search || $assignee || $group || $projectFilter || $priority || $period)
            <button type="button" wire:click="clearFilters" class="btn btn-ghost">Clear</button>
        @endif

        <div class="ml-auto flex rounded-lg bg-slate-100 p-1">
            <button type="button" wire:click="$set('view', 'board')" class="{{ $view === 'board' ? 'tab tab-active' : 'tab' }} flex items-center gap-1.5"><x-icon name="board" class="size-4" /> Board</button>
            <button type="button" wire:click="$set('view', 'list')" class="{{ $view === 'list' ? 'tab tab-active' : 'tab' }} flex items-center gap-1.5"><x-icon name="list" class="size-4" /> List</button>
        </div>
    </div>

    @if ($view === 'list')
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        <tr>
                            <th class="px-5 py-3">Task</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Priority</th>
                            <th class="px-5 py-3">Assigned to</th>
                            @unless ($project)<th class="px-5 py-3">Project</th>@endunless
                            <th class="px-5 py-3">Deadline</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse (collect($this->columns)->flatten()->sortBy([fn ($a, $b) => $b->priority->weight() <=> $a->priority->weight()]) as $task)
                            <tr wire:key="row-{{ $task->id }}" wire:click="$dispatch('edit-task', { id: {{ $task->id }} })" class="cursor-pointer hover:bg-slate-50">
                                <td class="max-w-xs px-5 py-3 font-medium text-slate-900"><span class="block truncate">{{ $task->title }}</span></td>
                                <td class="px-5 py-3"><span class="badge {{ $task->status->badgeClasses() }}">{{ $task->status->label() }}</span></td>
                                <td class="px-5 py-3"><span class="badge {{ $task->priority->badgeClasses() }}">{{ $task->priority->label() }}</span></td>
                                <td class="px-5 py-3">
                                    <span class="flex items-center gap-2"><x-avatar :user="$task->assignee" size="size-6" /> <span class="text-slate-700">{{ $task->assignee?->name ?? 'Unassigned' }}</span></span>
                                </td>
                                @unless ($project)
                                    <td class="px-5 py-3 text-slate-600">
                                        @if ($task->project)<span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full" style="background-color: {{ $task->project->color }}"></span>{{ $task->project->name }}</span>@else — @endif
                                    </td>
                                @endunless
                                <td class="px-5 py-3 whitespace-nowrap {{ $task->isOverdue() ? 'font-medium text-red-600' : 'text-slate-600' }}">{{ $task->due_at?->format('j M Y, g:i A') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No tasks match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="-mx-4 overflow-x-auto px-4 pb-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="grid min-w-[64rem] grid-cols-4 gap-4">
                @foreach ($statuses as $status)
                    @php $tasks = $this->columns[$status->value]; @endphp
                    <section class="flex flex-col rounded-xl bg-slate-100/80 p-2" wire:key="col-{{ $status->value }}">
                        <header class="flex items-center justify-between px-2 py-2">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span class="size-2 rounded-full {{ $status->dotClasses() }}"></span>
                                {{ $status->label() }}
                                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-slate-200">{{ $tasks->count() }}</span>
                            </h2>
                            <button type="button" title="Add task" class="rounded-md p-1 text-slate-400 hover:bg-white hover:text-slate-700"
                                    wire:click="$dispatch('create-task', { status: '{{ $status->value }}', projectId: {{ $project?->id ?? 'null' }} })">
                                <x-icon name="plus" class="size-4" />
                            </button>
                        </header>

                        <ul class="flex min-h-24 flex-1 flex-col gap-2" wire:sort="moveTask" wire:sort:group="tasks" wire:sort:group-id="{{ $status->value }}">
                            @foreach ($tasks as $task)
                                <li wire:key="task-{{ $task->id }}" wire:sort:item="{{ $task->id }}"
                                    wire:click="$dispatch('edit-task', { id: {{ $task->id }} })"
                                    class="group relative cursor-pointer overflow-hidden rounded-lg bg-white p-3 pl-4 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md hover:ring-slate-300">
                                    <span class="absolute inset-y-0 left-0 w-1 {{ $task->priority->barClasses() }}"></span>

                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-medium {{ $task->status === \App\Enums\TaskStatus::Done ? 'text-slate-400 line-through' : 'text-slate-900' }}">{{ $task->title }}</p>
                                        <span class="badge {{ $task->priority->badgeClasses() }}">{{ $task->priority->label() }}</span>
                                    </div>

                                    @if (! $project && $task->project)
                                        <p class="mt-1.5 flex items-center gap-1.5 text-xs text-slate-500">
                                            <span class="size-1.5 rounded-full" style="background-color: {{ $task->project->color }}"></span>{{ $task->project->name }}
                                        </p>
                                    @endif

                                    <div class="mt-3 flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2.5 text-xs text-slate-500">
                                            @if ($task->due_at)
                                                <span @class(['flex items-center gap-1 whitespace-nowrap', 'font-medium text-red-600' => $task->isOverdue(), 'font-medium text-amber-600' => $task->isDueSoon()])>
                                                    <x-icon name="clock" class="size-3.5" /> {{ $task->due_at->format('j M') }}
                                                </span>
                                            @endif
                                            @if ($task->remind_at && ! $task->reminder_sent_at && $task->status !== \App\Enums\TaskStatus::Done)
                                                <x-icon name="bell" class="size-3.5" title="Reminder set" />
                                            @endif
                                            @if ($task->recurring_task_id)
                                                <x-icon name="repeat" class="size-3.5" />
                                            @endif
                                            @if ($task->attachments_count)
                                                <span class="flex items-center gap-0.5"><x-icon name="folder" class="size-3.5" />{{ $task->attachments_count }}</span>
                                            @endif
                                        </div>
                                        <span class="flex items-center gap-1.5">
                                            <span class="hidden max-w-[7rem] truncate text-xs text-slate-500 xl:inline">{{ $task->assignee?->name }}</span>
                                            <x-avatar :user="$task->assignee" size="size-6" />
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        </div>
    @endif

    @if ($project)
        <livewire:project-form />
    @endif
</div>
