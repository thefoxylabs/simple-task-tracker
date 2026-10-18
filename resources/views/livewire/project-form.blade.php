<div>
    @if ($open)
        <x-modal :title="$projectId ? 'Edit project' : 'New project'" width="max-w-xl" close="close">
            <form wire:submit="save">
                <div class="space-y-5 p-6">
                    <div>
                        <label for="project-name" class="label">Project name</label>
                        <input wire:model="name" id="project-name" type="text" class="input" placeholder="Website redesign" autofocus>
                        @error('name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="project-description" class="label">Description</label>
                        <textarea wire:model="description" id="project-description" rows="4" class="input" placeholder="Goals, scope and anything the team should know…"></textarea>
                        @error('description') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <p class="label">Colour</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach (\App\Livewire\ProjectForm::COLORS as $c)
                                <button type="button" wire:click="$set('color', '{{ $c }}')" style="background-color: {{ $c }}"
                                        class="size-7 rounded-full ring-offset-2 transition {{ $color === $c ? 'ring-2 ring-slate-900' : 'hover:scale-110' }}">
                                    <span class="sr-only">{{ $c }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="project-lead" class="label">Project lead</label>
                            <select wire:model="lead_id" id="project-lead" class="input">
                                <option value="">None</option>
                                @foreach ($this->people as $person)
                                    <option value="{{ $person->id }}">{{ $person->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="project-start" class="label">Start date</label>
                            <input wire:model="starts_on" id="project-start" type="date" class="input">
                        </div>
                        <div>
                            <label for="project-due" class="label">Due date</label>
                            <input wire:model="due_on" id="project-due" type="date" class="input">
                            @error('due_on') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <p class="label">Files</p>
                        @if ($this->project?->attachments->isNotEmpty())
                            <ul class="mb-3 divide-y divide-slate-100 rounded-lg ring-1 ring-slate-200">
                                @foreach ($this->project->attachments as $file)
                                    <li class="flex items-center gap-3 px-3 py-2 text-sm" wire:key="patt-{{ $file->id }}">
                                        <a href="{{ route('attachments.download', $file) }}" class="min-w-0 flex-1 truncate text-brand-700 hover:underline">{{ $file->name }}</a>
                                        <span class="text-xs text-slate-400">{{ $file->humanSize() }}</span>
                                        <button type="button" wire:click="deleteAttachment({{ $file->id }})" wire:confirm="Delete this file?" class="text-slate-400 hover:text-red-600"><x-icon name="trash" class="size-4" /></button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @foreach ($uploads as $i => $upload)
                            <p class="mb-2 flex items-center gap-2 rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-800"><x-icon name="check" class="size-4" /> {{ $upload->getClientOriginalName() }}</p>
                            @error('uploads.'.$i) <p class="error mb-2">{{ $message }}</p> @enderror
                        @endforeach
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500 hover:border-brand-500 hover:text-brand-700">
                            <x-icon name="plus" class="size-4" />
                            <span wire:loading.remove wire:target="uploads">Attach briefs, specs or designs</span>
                            <span wire:loading wire:target="uploads">Uploading…</span>
                            <input type="file" wire:model="uploads" multiple class="sr-only">
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button type="button" wire:click="close" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">{{ $projectId ? 'Save changes' : 'Create project' }}</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
