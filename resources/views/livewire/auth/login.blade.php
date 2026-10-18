<div>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Sign in to your account</h1>
    <p class="mt-2 text-sm text-slate-500">Welcome back to {{ settings('app_name') }}.</p>

    @if (session('status'))
        <div class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-600/20">{{ session('status') }}</div>
    @endif

    <form wire:submit="login" class="mt-8 space-y-5">
        <div>
            <label for="email" class="label">Email address</label>
            <input wire:model="email" id="email" type="email" class="input" autocomplete="email" autofocus required>
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="label">Password</label>
                <a href="{{ route('password.request') }}" wire:navigate class="mb-1.5 text-sm font-medium text-brand-600 hover:text-brand-700">Forgot password?</a>
            </div>
            <input wire:model="password" id="password" type="password" class="input" autocomplete="current-password" required>
            @error('password') <p class="error">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input wire:model="remember" type="checkbox" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
            Keep me signed in
        </label>
        <button type="submit" class="btn btn-primary w-full py-2.5" wire:loading.attr="disabled">Sign in</button>
    </form>

    <p class="mt-8 text-center text-xs text-slate-400">Accounts are created by your administrator or manager.</p>
</div>
