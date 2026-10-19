<div>
    @if ($open)
        <x-modal :title="$taskId ? ($this->canEdit ? 'Edit task' : 'Task') : 'New task'" width="max-w-3xl" close="close">
            <form wire:submit="save">
                <div class="grid gap-0 md:grid-cols-[1fr_16rem]">
                    {{-- Main column --}}
                    <div class="space-y-5 p-6">
                        @if ($this->canEdit)
                            <div>
                                <label for="task-title" class="label">Title</label>
                                <input wire:model="title" id="task-title" type="text" class="input text-base font-medium" placeholder="What needs to be done?" autofocus>
                                @error('title') <p class="error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="task-description" class="label">Description</label>
                                <textarea wire:model="description" id="task-description" rows="5" class="input" placeholder="Add details, links or acceptance criteria…"></textarea>
                                @error('description') <p class="error">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">{{ $this->task->title }}</h3>
                                <p class="mt-1 text-xs text-slate-500">Set by {{ $this->task->creator?->name ?? 'someone' }} · {{ $this->task->created_at->diffForHumans() }}</p>
                                @if ($this->task->description)
                                    <p class="mt-4 text-sm whitespace-pre-line text-slate-700">{{ $this->task->description }}</p>
                                @endif
                            </div>
                        @endif

                        {{-- Deadline & reminder --}}
                        @if ($this->canEdit)
                            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="task-due" class="label flex items-center gap-1.5"><x-icon name="clock" class="size-4 text-slate-400" /> Deadline</label>
                                        <x-date-picker wire:model.live="due_at" id="task-due" mode="datetime" placeholder="No deadline" />
                                        @error('due_at') <p class="error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="task-reminder" class="label flex items-center gap-1.5"><x-icon name="bell" class="size-4 text-slate-400" /> Email reminder</label>
                                        <select wire:model.live="reminder" id="task-reminder" class="input">
                                            <option value="">No reminder</option>
                                            @foreach (\App\Livewire\TaskForm::REMINDERS as $minutes => $label)
                                                <option value="{{ $minutes }}">{{ $label }}</option>
                                            @endforeach
                                            <option value="custom">Custom date & time…</option>
                                        </select>
                                        @error('reminder') <p class="error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                @if ($reminder === 'custom')
                                    <div class="mt-4">
                                        <label for="task-remind-at" class="label">Send reminder at</label>
                                        <x-date-picker wire:model="remind_at" id="task-remind-at" mode="datetime" default-time="09:00" />
                                        @error('remind_at') <p class="error">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                                <p class="mt-3 text-xs text-slate-500">The assignee is emailed when the task is assigned and again at the reminder time.</p>
                            </div>
                        @elseif ($this->task->due_at)
                            <div class="flex flex-wrap gap-4 rounded-xl bg-slate-50 p-4 text-sm ring-1 ring-slate-200">
                                <span class="flex items-center gap-1.5 {{ $this->task->isOverdue() ? 'font-medium text-red-600' : 'text-slate-700' }}">
                                    <x-icon name="clock" class="size-4" /> Due {{ $this->task->due_at->format('D, j M Y · g:i A') }}
                                </span>
                                @if ($this->task->remind_at)
                                    <span class="flex items-center gap-1.5 text-slate-500"><x-icon name="bell" class="size-4" /> Reminder {{ $this->task->remind_at->format('j M, g:i A') }}</span>
                                @endif
                            </div>
                        @endif

                        {{-- Attachments --}}
                        <div>
                            <p class="label">Attachments</p>
                            @if ($this->task?->attachments->isNotEmpty())
                                <ul class="mb-3 divide-y divide-slate-100 rounded-lg ring-1 ring-slate-200">
                                    @foreach ($this->task->attachments as $file)
                                        <li class="flex items-center gap-3 px-3 py-2 text-sm" wire:key="att-{{ $file->id }}">
                                            <x-icon name="{{ $file->isImage() ? 'paint' : 'folder' }}" class="size-4 text-slate-400" />
                                            <a href="{{ route('attachments.download', $file) }}" class="min-w-0 flex-1 truncate font-medium text-brand-700 hover:underline">{{ $file->name }}</a>
                                            <span class="text-xs text-slate-400">{{ $file->humanSize() }} · {{ $file->uploader?->name }}</span>
                                            @if ($file->user_id === auth()->id() || $this->canEdit)
                                                <button type="button" wire:click="deleteAttachment({{ $file->id }})" wire:confirm="Delete this file?" class="text-slate-400 hover:text-red-600">
                                                    <x-icon name="trash" class="size-4" />
                                                </button>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @foreach ($uploads as $i => $upload)
                                <div class="mb-2 flex items-center gap-2 rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-800" wire:key="upload-{{ $i }}">
                                    <x-icon name="check" class="size-4" />
                                    <span class="min-w-0 flex-1 truncate">{{ $upload->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="removeUpload({{ $i }})" class="text-brand-600 hover:text-brand-800"><x-icon name="x" class="size-4" /></button>
                                </div>
                                @error('uploads.'.$i) <p class="error mb-2">{{ $message }}</p> @enderror
                            @endforeach

                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500 transition hover:border-brand-500 hover:text-brand-700">
                                <x-icon name="plus" class="size-4" />
                                <span wire:loading.remove wire:target="uploads">Add files (e.g. proof of completion)</span>
                                <span wire:loading wire:target="uploads">Uploading…</span>
                                <input type="file" wire:model="uploads" multiple class="sr-only">
                            </label>
                            <p class="mt-1.5 text-xs text-slate-400">Images, PDFs, Office documents, CSV or ZIP · up to 20 MB each</p>
                        </div>
                    </div>

                    {{-- Side column --}}
                    <div class="space-y-4 border-t border-slate-100 bg-slate-50/60 p-6 md:border-t-0 md:border-l">
                        <div>
                            <label for="task-status" class="label">Status</label>
                            <select wire:model="status" id="task-status" class="input">
                                @foreach (\App\Enums\TaskStatus::cases() as $case)
                                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($this->canEdit)
                            <div>
                                <label for="task-priority" class="label">Priority</label>
                                <select wire:model="priority" id="task-priority" class="input">
                                    @foreach (\App\Enums\TaskPriority::cases() as $case)
                                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="task-assignee" class="label">Assigned to</label>
                                @if (auth()->user()->canManage())
                                    <select wire:model="assignee_id" id="task-assignee" class="input">
                                        <option value="">Unassigned</option>
                                        @foreach ($this->staff as $member)
                                            <option value="{{ $member->id }}">{{ $member->name }}{{ $member->job_title ? ' – '.$member->job_title : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error('assignee_id') <p class="error">{{ $message }}</p> @enderror
                                @else
                                    <p class="text-sm text-slate-700">{{ $this->task?->assignee?->name ?? auth()->user()->name }}</p>
                                @endif
                            </div>
                            <div>
                                <label for="task-project" class="label">Project</label>
                                <select wire:model.live="project_id" id="task-project" class="input">
                                    <option value="">No project</option>
                                    @foreach ($this->projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="task-plan" class="label">Plan / goal</label>
                                <select wire:model="plan_id" id="task-plan" class="input">
                                    <option value="">Not part of a plan</option>
                                    @foreach ($this->plans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->title }} ({{ $plan->period->label() }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <dl class="space-y-3 text-sm">
                                <div><dt class="text-xs text-slate-500">Priority</dt><dd class="mt-1"><span class="badge {{ $this->task->priority->badgeClasses() }}">{{ $this->task->priority->label() }}</span></dd></div>
                                <div><dt class="text-xs text-slate-500">Project</dt><dd class="mt-1 text-slate-800">{{ $this->task->project?->name ?? '—' }}</dd></div>
                                <div><dt class="text-xs text-slate-500">Plan</dt><dd class="mt-1 text-slate-800">{{ $this->task->plan?->title ?? '—' }}</dd></div>
                            </dl>
                        @endif

                        @if ($this->task?->recurringTask)
                            <p class="flex items-center gap-1.5 text-xs text-slate-500"><x-icon name="repeat" class="size-4" /> Repeats {{ strtolower($this->task->recurringTask->frequency->label()) }}</p>
                        @endif
                        @if ($this->task?->completed_at)
                            <p class="flex items-center gap-1.5 text-xs text-emerald-700"><x-icon name="check-circle" class="size-4" /> Completed {{ $this->task->completed_at->format('j M Y, g:i A') }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-6 py-4">
                    <div>
                        @if ($taskId && $this->canEdit)
                            <button type="button" wire:click="delete" wire:confirm="Delete this task? This cannot be undone." class="btn btn-ghost text-red-600 hover:bg-red-50 hover:text-red-700">
                                <x-icon name="trash" class="size-4" /> Delete
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="close" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            {{ $taskId ? 'Save changes' : 'Create task' }}
                        </button>
                    </div>
                </div>
            </form>
        </x-modal>
    @endif
</div>
