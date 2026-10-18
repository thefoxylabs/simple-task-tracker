<div>
    <x-page-header title="Staff groups" description="Organise staff into departments or teams, then filter the board and plans by group.">
        <x-slot:actions>
            <button type="button" wire:click="create" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New group</button>
        </x-slot:actions>
    </x-page-header>

    @if ($this->groups->isEmpty())
        <x-empty-state icon="tag" title="No groups yet" description="Create groups like Sales, Operations or Design, then add staff to them.">
            <button type="button" wire:click="create" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New group</button>
        </x-empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->groups as $group)
                <div class="card flex flex-col p-5" wire:key="group-{{ $group->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <span class="size-3 rounded-full" style="background-color: {{ $group->color }}"></span>
                            <h3 class="font-semibold text-slate-900">{{ $group->name }}</h3>
                        </div>
                        <div class="flex">
                            <button type="button" wire:click="edit({{ $group->id }})" class="btn btn-ghost px-2 py-1"><x-icon name="pencil" class="size-4" /></button>
                            <button type="button" wire:click="delete({{ $group->id }})" wire:confirm="Delete the '{{ $group->name }}' group? Members will be kept." class="btn btn-ghost px-2 py-1 text-red-600"><x-icon name="trash" class="size-4" /></button>
                        </div>
                    </div>
                    @if ($group->description)
                        <p class="mt-2 text-sm text-slate-500">{{ $group->description }}</p>
                    @endif
                    <div class="mt-auto flex items-center justify-between pt-5">
                        <div class="flex -space-x-2">
                            @foreach ($group->members->take(6) as $member)
                                <x-avatar :user="$member" />
                            @endforeach
                        </div>
                        <a href="{{ route('staff.index', ['group' => $group->id]) }}" wire:navigate class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ $group->members_count }} {{ \Illuminate\Support\Str::plural('member', $group->members_count) }} →</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($open)
        <x-modal :title="$editingId ? 'Edit group' : 'New group'" close="$set('open', false)">
            <form wire:submit="save">
                <div class="space-y-5 p-6">
                    <div>
                        <label for="g-name" class="label">Name</label>
                        <input wire:model="name" id="g-name" type="text" class="input" placeholder="e.g. Sales team" autofocus>
                        @error('name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="g-description" class="label">Description</label>
                        <textarea wire:model="description" id="g-description" rows="2" class="input"></textarea>
                    </div>
                    <div>
                        <p class="label">Colour</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach (\App\Livewire\ProjectForm::COLORS as $c)
                                <button type="button" wire:click="$set('color', '{{ $c }}')" style="background-color: {{ $c }}" class="size-7 rounded-full ring-offset-2 {{ $color === $c ? 'ring-2 ring-slate-900' : '' }}"></button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button type="button" wire:click="$set('open', false)" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $editingId ? 'Save changes' : 'Create group' }}</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
