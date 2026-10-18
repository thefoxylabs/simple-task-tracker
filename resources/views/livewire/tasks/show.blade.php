<div class="max-w-4xl">
    <x-page-header :title="$task->title">
        <x-slot:before>
            <a href="{{ $task->project ? route('projects.show', $task->project) : route('board') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                <x-icon name="arrow-left" class="size-4" /> {{ $task->project?->name ?? 'Board' }}
            </a>
        </x-slot:before>
        <x-slot:actions>
            <button type="button" wire:click="$dispatch('edit-task', { id: {{ $task->id }} })" class="btn btn-primary">
                <x-icon name="pencil" class="size-4" /> {{ auth()->user()->can('update', $task) ? 'Edit task' : 'Update status' }}
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 md:grid-cols-3">
        <div class="card p-6 md:col-span-2">
            <h2 class="text-sm font-semibold text-slate-800">Description</h2>
            <p class="mt-2 text-sm whitespace-pre-line text-slate-700">{{ $task->description ?: 'No description.' }}</p>

            <h2 class="mt-8 text-sm font-semibold text-slate-800">Attachments</h2>
            <ul class="mt-2 space-y-2">
                @forelse ($task->attachments as $file)
                    <li class="flex items-center gap-2 text-sm">
                        <x-icon name="folder" class="size-4 text-slate-400" />
                        <a href="{{ route('attachments.download', $file) }}" class="text-brand-700 hover:underline">{{ $file->name }}</a>
                        <span class="text-xs text-slate-400">{{ $file->humanSize() }} · {{ $file->uploader?->name }} · {{ $file->created_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">No files.</li>
                @endforelse
            </ul>
        </div>
        <dl class="card space-y-4 p-6 text-sm">
            <div><dt class="text-xs text-slate-500">Status</dt><dd class="mt-1"><span class="badge {{ $task->status->badgeClasses() }}">{{ $task->status->label() }}</span></dd></div>
            <div><dt class="text-xs text-slate-500">Priority</dt><dd class="mt-1"><span class="badge {{ $task->priority->badgeClasses() }}">{{ $task->priority->label() }}</span></dd></div>
            <div><dt class="text-xs text-slate-500">Assigned to</dt><dd class="mt-1 flex items-center gap-2"><x-avatar :user="$task->assignee" size="size-6" /> {{ $task->assignee?->name ?? 'Unassigned' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Deadline</dt><dd class="mt-1 {{ $task->isOverdue() ? 'font-medium text-red-600' : 'text-slate-800' }}">{{ $task->due_at?->format('D, j M Y · g:i A') ?? '—' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Reminder</dt><dd class="mt-1 text-slate-800">{{ $task->remind_at?->format('D, j M Y · g:i A') ?? '—' }}{{ $task->reminder_sent_at ? ' (sent)' : '' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Plan</dt><dd class="mt-1 text-slate-800">@if ($task->plan)<a href="{{ route('plans.show', $task->plan) }}" wire:navigate class="text-brand-700 hover:underline">{{ $task->plan->title }}</a>@else — @endif</dd></div>
            <div><dt class="text-xs text-slate-500">Set by</dt><dd class="mt-1 text-slate-800">{{ $task->creator?->name ?? '—' }} · {{ $task->created_at->format('j M Y') }}</dd></div>
            @if ($task->completed_at)
                <div><dt class="text-xs text-slate-500">Completed</dt><dd class="mt-1 text-emerald-700">{{ $task->completed_at->format('D, j M Y · g:i A') }}</dd></div>
            @endif
        </dl>
    </div>
</div>
