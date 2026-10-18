@props(['task', 'showProject' => true])
<li {{ $attributes }}>
    <button type="button" wire:click="$dispatch('edit-task', { id: {{ $task->id }} })"
            class="flex w-full items-center gap-3 px-5 py-3 text-left transition hover:bg-slate-50">
        <span class="size-2 shrink-0 rounded-full {{ $task->status->dotClasses() }}" title="{{ $task->status->label() }}"></span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-medium {{ $task->status === \App\Enums\TaskStatus::Done ? 'text-slate-400 line-through' : 'text-slate-900' }}">{{ $task->title }}</span>
            <span class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-slate-500">
                @if ($showProject && $task->project)
                    <span class="inline-flex items-center gap-1"><span class="size-1.5 rounded-full" style="background-color: {{ $task->project->color }}"></span>{{ $task->project->name }}</span>
                @endif
                @if ($task->due_at)
                    <span @class(['font-medium text-red-600' => $task->isOverdue(), 'text-amber-600' => $task->isDueSoon()])>
                        Due {{ $task->due_at->format('j M, g:i A') }}
                    </span>
                @endif
            </span>
        </span>
        <span class="badge {{ $task->priority->badgeClasses() }}">{{ $task->priority->label() }}</span>
        <x-avatar :user="$task->assignee" />
    </button>
</li>
