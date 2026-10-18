<div>
    <x-page-header title="Projects" description="Group tasks by project and track progress.">
        <x-slot:actions>
            @can('manage')
                <button type="button" wire:click="$dispatch('create-project')" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New project</button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <div class="flex rounded-lg bg-slate-100 p-1">
            <button type="button" wire:click="$set('archived', false)" class="{{ ! $archived ? 'tab tab-active' : 'tab' }}">Active</button>
            <button type="button" wire:click="$set('archived', true)" class="{{ $archived ? 'tab tab-active' : 'tab' }}">Archived</button>
        </div>
        <div class="relative w-full sm:w-64">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search projects…" class="input pl-9">
        </div>
    </div>

    @if ($this->projects->isEmpty())
        <x-empty-state icon="folder" :title="$archived ? 'No archived projects' : 'No projects yet'"
                       :description="$archived ? 'Archived projects will appear here.' : 'Create a project to group related tasks and follow their progress.'">
            @if (! $archived && auth()->user()->canManage())
                <button type="button" wire:click="$dispatch('create-project')" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New project</button>
            @endif
        </x-empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->projects as $project)
                <div class="card group relative flex flex-col p-5 transition hover:shadow-md" wire:key="project-{{ $project->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <a href="{{ route('projects.show', $project) }}" wire:navigate class="flex min-w-0 items-center gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg text-sm font-bold text-white" style="background-color: {{ $project->color }}">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($project->name, 0, 2)) }}
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate font-semibold text-slate-900 group-hover:text-brand-700">{{ $project->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $project->lead ? 'Led by '.$project->lead->name : 'No lead' }}</span>
                            </span>
                        </a>
                        @can('manage')
                            <div class="flex shrink-0 items-center" x-data="{ open: false }" @click.outside="open = false">
                                <div class="relative">
                                    <button type="button" @click="open = ! open" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"><x-icon name="chevron-down" class="size-4" /></button>
                                    <div x-cloak x-show="open" x-transition class="absolute right-0 z-10 mt-1 w-40 rounded-lg bg-white p-1 shadow-lg ring-1 ring-slate-200">
                                        <button type="button" wire:click="$dispatch('edit-project', { id: {{ $project->id }} })" @click="open = false" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-icon name="pencil" class="size-4 text-slate-400" /> Edit</button>
                                        <button type="button" wire:click="toggleArchive({{ $project->id }})" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-icon name="archive" class="size-4 text-slate-400" /> {{ $project->isArchived() ? 'Restore' : 'Archive' }}</button>
                                        @can('admin')
                                            <button type="button" wire:click="delete({{ $project->id }})" wire:confirm="Delete '{{ $project->name }}' and all of its tasks? This cannot be undone." class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-red-600 hover:bg-red-50"><x-icon name="trash" class="size-4" /> Delete</button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endcan
                    </div>

                    @if ($project->description)
                        <p class="mt-3 line-clamp-2 text-sm text-slate-500">{{ $project->description }}</p>
                    @endif

                    <div class="mt-auto pt-5">
                        <div class="mb-2 flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-700">{{ $project->progress() }}% complete</span>
                            <span class="text-slate-500">{{ $project->done_tasks_count }}/{{ $project->tasks_count }} tasks</span>
                        </div>
                        <x-progress :value="$project->progress()" :color="$project->color" />
                        <div class="mt-3 flex items-center gap-3 text-xs text-slate-500">
                            @if ($project->overdue_tasks_count)
                                <span class="flex items-center gap-1 font-medium text-red-600"><x-icon name="alert" class="size-3.5" /> {{ $project->overdue_tasks_count }} overdue</span>
                            @endif
                            @if ($project->due_on)
                                <span class="flex items-center gap-1"><x-icon name="calendar" class="size-3.5" /> Due {{ $project->due_on->format('j M Y') }}</span>
                            @endif
                            @if ($project->isArchived())
                                <span class="flex items-center gap-1"><x-icon name="archive" class="size-3.5" /> Archived {{ $project->archived_at->format('j M Y') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <livewire:project-form />
</div>
