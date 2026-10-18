<div class="max-w-3xl">
    <x-page-header title="Your profile" description="Update your details and password." />

    <form wire:submit="saveProfile" class="card mb-6">
        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <div>
                <label for="p-name" class="label">Name</label>
                <input wire:model="name" id="p-name" type="text" class="input">
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="p-email" class="label">Email</label>
                <input wire:model="email" id="p-email" type="email" class="input">
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="p-job" class="label">Job title</label>
                <input wire:model="job_title" id="p-job" type="text" class="input">
            </div>
            <div>
                <label for="p-phone" class="label">Phone</label>
                <input wire:model="phone" id="p-phone" type="text" class="input">
            </div>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-6 py-4">
            <button type="submit" class="btn btn-primary">Save profile</button>
        </div>
    </form>

    <form wire:submit="savePassword" class="card">
        <div class="grid gap-5 p-6 sm:grid-cols-3">
            <div>
                <label for="p-current" class="label">Current password</label>
                <input wire:model="current_password" id="p-current" type="password" class="input" autocomplete="current-password">
                @error('current_password') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="p-password" class="label">New password</label>
                <input wire:model="password" id="p-password" type="password" class="input" autocomplete="new-password">
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="p-confirm" class="label">Confirm</label>
                <input wire:model="password_confirmation" id="p-confirm" type="password" class="input" autocomplete="new-password">
            </div>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-6 py-4">
            <button type="submit" class="btn btn-primary">Change password</button>
        </div>
    </form>
</div>
