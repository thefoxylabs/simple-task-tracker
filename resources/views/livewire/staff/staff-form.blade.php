<div>
    @if ($open)
        <x-modal :title="$userId ? 'Edit staff member' : 'Add staff member'" width="max-w-xl" close="close">
            <form wire:submit="save">
                <div class="grid gap-5 p-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="s-name" class="label">Full name</label>
                        <input wire:model="name" id="s-name" type="text" class="input" autofocus>
                        @error('name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="s-email" class="label">Email</label>
                        <input wire:model="email" id="s-email" type="email" class="input">
                        @error('email') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="s-job" class="label">Job title</label>
                        <input wire:model="job_title" id="s-job" type="text" class="input" placeholder="e.g. Sales executive">
                    </div>
                    <div>
                        <label for="s-phone" class="label">Phone</label>
                        <input wire:model="phone" id="s-phone" type="text" class="input">
                    </div>
                    <div>
                        <label for="s-group" class="label">Staff group</label>
                        <select wire:model="staff_group_id" id="s-group" class="input">
                            <option value="">No group</option>
                            @foreach ($this->groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="s-role" class="label">Role</label>
                        <select wire:model="role" id="s-role" class="input">
                            @foreach ($this->roles as $r)<option value="{{ $r->value }}">{{ $r->label() }}</option>@endforeach
                        </select>
                        @error('role') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2 rounded-lg bg-slate-50 p-4 text-xs text-slate-600 ring-1 ring-slate-200">
                        <b>Admin</b> – everything, including settings and branding. <b>Manager</b> – manage staff, projects and plans, and assign tasks to anyone. <b>Staff</b> – see and update their own tasks.
                    </div>

                    @unless ($userId)
                        <label class="flex items-center gap-2 text-sm text-slate-700 sm:col-span-2">
                            <input wire:model.live="invite" type="checkbox" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                            Email an invitation so they can set their own password
                        </label>
                    @endunless
                    <div class="sm:col-span-2">
                        <label for="s-password" class="label">{{ $userId ? 'New password' : 'Password' }} <span class="font-normal text-slate-400">{{ $userId || $invite ? '(optional)' : '' }}</span></label>
                        <input wire:model="password" id="s-password" type="password" class="input" autocomplete="new-password">
                        @error('password') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    @if ($userId && $this->editingUser)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-4 text-sm ring-1 ring-slate-200 sm:col-span-2">
                            <span class="flex items-center gap-2 text-slate-700">
                                <x-icon name="shield" class="size-4 text-slate-400" />
                                Two-factor: <b class="font-semibold">{{ $this->editingUser->twoFactorMethod()?->label() ?? 'Off' }}</b>
                            </span>
                            @if ($this->editingUser->two_factor_method)
                                <button type="button" wire:click="resetTwoFactor" wire:confirm="Reset {{ $this->editingUser->name }}'s two-factor sign-in? Use this if they lost their phone." class="btn btn-ghost px-2 py-1 text-red-600">Reset</button>
                            @endif
                        </div>
                    @endif
                    @if ($userId)
                        <label class="flex items-center gap-2 text-sm text-slate-700 sm:col-span-2">
                            <input wire:model="is_active" type="checkbox" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                            Active (inactive staff can't sign in and won't receive emails)
                        </label>
                    @endif
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button type="button" wire:click="close" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $userId ? 'Save changes' : 'Add staff member' }}</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
