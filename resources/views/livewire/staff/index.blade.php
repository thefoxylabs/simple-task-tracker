<div>
    <x-page-header title="Staff" description="Manage your team and see who is working on what.">
        <x-slot:actions>
            <a href="{{ route('groups.index') }}" wire:navigate class="btn btn-secondary"><x-icon name="tag" class="size-4" /> Groups</a>
            <button type="button" wire:click="$dispatch('create-staff')" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Add staff</button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <x-period-nav />
        <div class="relative w-full sm:ml-auto sm:w-56">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search staff…" class="input pl-9">
        </div>
        <select wire:model.live="group" class="input w-auto">
            <option value="">All groups</option>
            <option value="none">No group</option>
            @foreach ($this->groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input wire:model.live="inactive" type="checkbox" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600"> Inactive
        </label>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Group</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3 text-center">Tasks · {{ $this->periodLabel() }}</th>
                        <th class="px-5 py-3 text-center">Done</th>
                        <th class="px-5 py-3 text-center">Open now</th>
                        <th class="px-5 py-3 text-center">Overdue</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->staff as $member)
                        <tr wire:key="staff-{{ $member->id }}" class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <a href="{{ route('staff.show', ['user' => $member, 'period' => $period, 'date' => $date ?: null]) }}" wire:navigate class="flex items-center gap-3">
                                    <x-avatar :user="$member" size="size-9" class="ring-0" />
                                    <span>
                                        <span class="block font-medium text-slate-900 hover:text-brand-700">{{ $member->name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $member->job_title ?: $member->email }}</span>
                                    </span>
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                @if ($member->group)
                                    <span class="badge bg-white text-slate-700 ring-slate-200"><span class="size-1.5 rounded-full" style="background-color: {{ $member->group->color }}"></span>{{ $member->group->name }}</span>
                                @else <span class="text-slate-400">—</span> @endif
                            </td>
                            <td class="px-5 py-3"><span class="badge {{ $member->role->badgeClasses() }}">{{ $member->role->label() }}</span></td>
                            <td class="px-5 py-3 text-center font-medium text-slate-900">{{ $member->period_tasks_count }}</td>
                            <td class="px-5 py-3 text-center text-emerald-700">{{ $member->period_done_count }}</td>
                            <td class="px-5 py-3 text-center text-slate-700">{{ $member->open_count }}</td>
                            <td class="px-5 py-3 text-center {{ $member->overdue_count ? 'font-semibold text-red-600' : 'text-slate-400' }}">{{ $member->overdue_count }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                @can('manage-user', $member)
                                    <button type="button" wire:click="$dispatch('edit-staff', { id: {{ $member->id }} })" class="btn btn-ghost px-2 py-1"><x-icon name="pencil" class="size-4" /></button>
                                @endcan
                                <button type="button" wire:click="$dispatch('create-task', { assigneeId: {{ $member->id }} })" class="btn btn-ghost px-2 py-1" title="Assign a task"><x-icon name="plus" class="size-4" /></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">No staff found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <livewire:staff.staff-form />
</div>
