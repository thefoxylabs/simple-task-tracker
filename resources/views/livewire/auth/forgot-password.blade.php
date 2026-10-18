<div>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Reset your password</h1>
    <p class="mt-2 text-sm text-slate-500">Enter your email and we'll send you a link to choose a new password.</p>

    @if ($sent)
        <div class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-600/20">
            If an account exists for that address, a reset link is on its way.
        </div>
    @else
        <form wire:submit="send" class="mt-8 space-y-5">
            <div>
                <label for="email" class="label">Email address</label>
                <input wire:model="email" id="email" type="email" class="input" autocomplete="email" autofocus required>
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-primary w-full py-2.5" wire:loading.attr="disabled">Email reset link</button>
        </form>
    @endif

    <p class="mt-8 text-center text-sm">
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-brand-600 hover:text-brand-700">Back to sign in</a>
    </p>
</div>
