<div>
    <x-page-header title="Recurring tasks" description="Tasks that are created automatically every day, week, month, quarter or year.">
        <x-slot:actions>
            <button type="button" wire:click="create" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New schedule</button>
        </x-slot:actions>
    </x-page-header>

    @if ($this->schedules->isEmpty())
        <x-empty-state icon="repeat" title="No recurring tasks" description="Set up routine work like weekly reports or monthly invoicing once, and tasks will be created and assigned automatically.">
            <button type="button" wire:click="create" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New schedule</button>
        </x-empty-state>
    @else
        {{-- Phones: one card per schedule --}}
        <ul class="space-y-2 md:hidden">
            @foreach ($this->schedules as $schedule)
                <li class="card p-4 {{ $schedule->is_active ? '' : 'opacity-60' }}" wire:key="m-rt-{{ $schedule->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900">{{ $schedule->title }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                                <span class="badge {{ $schedule->priority->badgeClasses() }}">{{ $schedule->priority->label() }}</span>
                                <span class="inline-flex items-center gap-1"><x-icon name="repeat" class="size-3.5" /> {{ $schedule->frequency->label() }} at {{ \Carbon\Carbon::parse($schedule->due_time)->format('g:i A') }}</span>
                            </p>
                        </div>
                        <x-avatar :user="$schedule->assignee" size="size-7" />
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
                        <span>
                            @if (! $schedule->is_active) Paused
                            @elseif ($schedule->isFinished()) Finished
                            @else Next: {{ $schedule->next_run_on->format('D, j M') }}
                            @endif
                            · {{ $schedule->tasks_count }} {{ \Illuminate\Support\Str::plural('task', $schedule->tasks_count) }}
                        </span>
                        <span class="flex gap-1">
                            <button type="button" wire:click="toggle({{ $schedule->id }})" class="btn btn-ghost px-2 py-1 text-xs">{{ $schedule->is_active ? 'Pause' : 'Resume' }}</button>
                            <button type="button" wire:click="edit({{ $schedule->id }})" class="btn btn-ghost px-2 py-1"><x-icon name="pencil" class="size-4" /></button>
                            <button type="button" wire:click="delete({{ $schedule->id }})" wire:confirm="Delete this schedule? Tasks already created are kept." class="btn btn-ghost px-2 py-1 text-red-600"><x-icon name="trash" class="size-4" /></button>
                        </span>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="card hidden overflow-hidden md:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        <tr>
                            <th class="px-5 py-3">Task</th>
                            <th class="px-5 py-3">Repeats</th>
                            <th class="px-5 py-3">Assigned to</th>
                            <th class="px-5 py-3">Next task</th>
                            <th class="px-5 py-3">Created</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($this->schedules as $schedule)
                            <tr wire:key="rt-{{ $schedule->id }}" class="{{ $schedule->is_active ? '' : 'opacity-60' }}">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-900">{{ $schedule->title }}</p>
                                    <p class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                                        <span class="badge {{ $schedule->priority->badgeClasses() }}">{{ $schedule->priority->label() }}</span>
                                        {{ $schedule->project?->name }}
                                    </p>
                                </td>
                                <td class="px-5 py-3 text-slate-700">
                                    <span class="flex items-center gap-1.5"><x-icon name="repeat" class="size-4 text-slate-400" /> {{ $schedule->frequency->label() }}</span>
                                    <span class="text-xs text-slate-500">Due at {{ \Carbon\Carbon::parse($schedule->due_time)->format('g:i A') }}</span>
                                </td>
                                <td class="px-5 py-3"><span class="flex items-center gap-2"><x-avatar :user="$schedule->assignee" size="size-6" /> {{ $schedule->assignee?->name ?? 'Unassigned' }}</span></td>
                                <td class="px-5 py-3 text-slate-700">
                                    @if (! $schedule->is_active) Paused
                                    @elseif ($schedule->isFinished()) Finished
                                    @else {{ $schedule->next_run_on->format('D, j M Y') }}
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-500">{{ $schedule->tasks_count }} {{ \Illuminate\Support\Str::plural('task', $schedule->tasks_count) }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        <button type="button" wire:click="toggle({{ $schedule->id }})" class="btn btn-ghost px-2 py-1 text-xs">{{ $schedule->is_active ? 'Pause' : 'Resume' }}</button>
                                        <button type="button" wire:click="edit({{ $schedule->id }})" class="btn btn-ghost px-2 py-1"><x-icon name="pencil" class="size-4" /></button>
                                        <button type="button" wire:click="delete({{ $schedule->id }})" wire:confirm="Delete this schedule? Tasks already created are kept." class="btn btn-ghost px-2 py-1 text-red-600"><x-icon name="trash" class="size-4" /></button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($open)
        <x-modal :title="$editingId ? 'Edit recurring task' : 'New recurring task'" width="max-w-xl" close="close">
            <form wire:submit="save">
                <div class="space-y-5 p-6">
                    <div>
                        <label for="rt-title" class="label">Title</label>
                        <input wire:model="title" id="rt-title" type="text" class="input" placeholder="e.g. Send weekly sales report">
                        @error('title') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="rt-description" class="label">Description</label>
                        <textarea wire:model="description" id="rt-description" rows="3" class="input"></textarea>
                    </div>
                    <div>
                        <p class="label">Repeats</p>
                        <div class="segmented sm:grid sm:grid-cols-5">
                            @foreach (\App\Enums\Period::cases() as $p)
                                <button type="button" wire:click="$set('frequency', '{{ $p->value }}')" class="{{ $frequency === $p->value ? 'tab tab-active' : 'tab' }} text-center">{{ $p->label() }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="rt-assignee" class="label">Assign to</label>
                            <select wire:model="assignee_id" id="rt-assignee" class="input">
                                <option value="">Unassigned</option>
                                @foreach ($this->options['people'] as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="rt-project" class="label">Project</label>
                            <select wire:model="project_id" id="rt-project" class="input">
                                <option value="">No project</option>
                                @foreach ($this->options['projects'] as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="rt-start" class="label">{{ $editingId ? 'Next task on' : 'First task on' }}</label>
                            <x-date-picker wire:model="starts_on" id="rt-start" :clearable="false" />
                            @error('starts_on') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="rt-end" class="label">Stop after <span class="font-normal text-slate-400">(optional)</span></label>
                            <x-date-picker wire:model="ends_on" id="rt-end" placeholder="Never" />
                            @error('ends_on') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="rt-time" class="label">Deadline time</label>
                            <x-date-picker wire:model="due_time" id="rt-time" mode="time" :clearable="false" />
                            @error('due_time') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="rt-reminder" class="label">Email reminder</label>
                            <select wire:model="reminder" id="rt-reminder" class="input">
                                <option value="">No reminder</option>
                                @foreach (\App\Livewire\TaskForm::REMINDERS as $minutes => $label)<option value="{{ $minutes }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="rt-priority" class="label">Priority</label>
                            <select wire:model="priority" id="rt-priority" class="input">
                                @foreach (\App\Enums\TaskPriority::cases() as $p)<option value="{{ $p->value }}">{{ $p->label() }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button type="button" wire:click="close" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $editingId ? 'Save changes' : 'Create schedule' }}</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
