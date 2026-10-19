<div>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Welcome! Let's get set up</h1>
    <p class="mt-2 text-sm text-slate-500">Name your workspace and create the first administrator account. You can change branding, mail and everything else later in Settings.</p>

    <form wire:submit="save" class="mt-8 space-y-5">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="app_name" class="label">App name</label>
                <input wire:model="app_name" id="app_name" type="text" class="input" placeholder="Acme Tasks" required>
                @error('app_name') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="company_name" class="label">Company name</label>
                <input wire:model="company_name" id="company_name" type="text" class="input" placeholder="Acme Inc.">
                @error('company_name') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="border-t border-slate-200 pt-5">
            <label for="name" class="label">Your name</label>
            <input wire:model="name" id="name" type="text" class="input" autocomplete="name" required>
            @error('name') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email" class="label">Email address</label>
            <input wire:model="email" id="email" type="email" class="input" autocomplete="email" required>
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="label">Password</label>
                <input wire:model="password" id="password" type="password" class="input" autocomplete="new-password" required>
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirm password</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" class="input" autocomplete="new-password" required>
            </div>
        </div>

        @if (\App\Support\SetupToken::required())
            <div>
                <label for="setup_token" class="label">Setup token</label>
                <input wire:model="setup_token" id="setup_token" type="password" class="input" autocomplete="off">
                @if (\App\Support\SetupToken::fromEnvironment())
                    <p class="mt-1.5 text-xs text-slate-500">The value of <code>SETUP_TOKEN</code> in your <code>.env</code> file.</p>
                @else
                    <p class="mt-1.5 text-xs text-slate-500">This keeps strangers from claiming your workspace. Get the token by running <code class="rounded bg-slate-100 px-1 py-0.5">php artisan app:setup-token</code> on your server.</p>
                @endif
                @error('setup_token') <p class="error">{{ $message }}</p> @enderror
            </div>
        @endif

        <button type="submit" class="btn btn-primary w-full py-2.5" wire:loading.attr="disabled">
            Create workspace
        </button>
    </form>
</div>
