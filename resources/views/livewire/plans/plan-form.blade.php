<div>
    @if ($open)
        <x-modal :title="$planId ? 'Edit plan' : 'New plan'" width="max-w-xl" close="close">
            <form wire:submit="save">
                <div class="space-y-5 p-6">
                    <div>
                        <p class="label">Planning period</p>
                        <div class="segmented sm:grid sm:grid-cols-5">
                            @foreach (\App\Enums\Period::cases() as $p)
                                <button type="button" wire:click="$set('period', '{{ $p->value }}')" class="{{ $period === $p->value ? 'tab tab-active' : 'tab' }} text-center">{{ $p->label() }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label for="plan-date" class="label">Which {{ \App\Enums\Period::tryFrom($period)?->noun() ?? 'period' }}?</label>
                        <x-date-picker wire:model.live="date" id="plan-date" :clearable="false" />
                        @if ($this->range)
                            <p class="mt-1.5 flex items-center gap-1.5 text-sm text-brand-700"><x-icon name="calendar" class="size-4" /> {{ $this->range }}</p>
                        @endif
                        @error('date') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="plan-title" class="label">Goal</label>
                        <input wire:model="title" id="plan-title" type="text" class="input" placeholder="e.g. Close 20 new deals">
                        @error('title') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="plan-description" class="label">Details</label>
                        <textarea wire:model="description" id="plan-description" rows="3" class="input" placeholder="What does success look like?"></textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="plan-user" class="label">For person</label>
                            <select wire:model="user_id" id="plan-user" class="input">
                                <option value="">—</option>
                                @foreach ($this->options['people'] as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="plan-group" class="label">For group</label>
                            <select wire:model="staff_group_id" id="plan-group" class="input">
                                <option value="">—</option>
                                @foreach ($this->options['groups'] as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="plan-project" class="label">Project</label>
                            <select wire:model="project_id" id="plan-project" class="input">
                                <option value="">—</option>
                                @foreach ($this->options['projects'] as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">Leave person and group empty for a company-wide plan. Link tasks to the plan to track its progress.</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button type="button" wire:click="close" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $planId ? 'Save changes' : 'Create plan' }}</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
