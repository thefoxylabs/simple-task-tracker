<div>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Choose a new password</h1>

    <form wire:submit="save" class="mt-8 space-y-5">
        <div>
            <label for="email" class="label">Email address</label>
            <input wire:model="email" id="email" type="email" class="input" autocomplete="email" required>
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="label">New password</label>
            <input wire:model="password" id="password" type="password" class="input" autocomplete="new-password" required>
            @error('password') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="label">Confirm new password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" class="input" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary w-full py-2.5" wire:loading.attr="disabled">Reset password</button>
    </form>
</div>
