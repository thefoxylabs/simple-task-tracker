<div>
    <div class="flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
        <x-icon :name="$this->method === \App\Enums\TwoFactorMethod::Email ? 'mail' : 'check-circle'" class="size-6" />
    </div>

    <h1 class="mt-6 text-2xl font-bold tracking-tight text-slate-900">Verify it's you</h1>

    <p class="mt-2 text-sm text-slate-500">
        @if ($recovery)
            Enter one of the recovery codes you saved when you set up your authenticator app.
        @elseif ($this->method === \App\Enums\TwoFactorMethod::Email)
            We emailed a 6-digit code to <span class="font-medium text-slate-700">{{ $this->user?->maskedEmail() }}</span>. It expires in {{ \App\Support\TwoFactor::CODE_MINUTES }} minutes.
        @else
            Open your authenticator app and enter the 6-digit code for {{ settings('app_name') }}.
        @endif
    </p>

    @if ($notice)
        <div class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-600/20">{{ $notice }}</div>
    @endif

    <form wire:submit="verify" class="mt-8 space-y-5">
        <div>
            <label for="code" class="label">{{ $recovery ? 'Recovery code' : 'Verification code' }}</label>
            @if ($recovery)
                <input wire:model="code" id="code" type="text" class="input font-mono" placeholder="abcde-12345" autocomplete="off" autofocus>
            @else
                <input wire:model="code" id="code" type="text" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" autofocus
                       class="input py-3 text-center font-mono text-2xl tracking-[0.5em]" placeholder="••••••">
            @endif
            @error('code') <p class="error">{{ $message }}</p> @enderror
        </div>

        @if (settings('two_factor_trusted_devices'))
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input wire:model="trust" type="checkbox" class="size-4 rounded border-slate-300">
                Trust this device for {{ \App\Support\TwoFactor::TRUST_DAYS }} days
            </label>
        @endif

        <button type="submit" class="btn btn-primary w-full py-2.5" wire:loading.attr="disabled">Verify and sign in</button>
    </form>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm">
        @if ($this->method === \App\Enums\TwoFactorMethod::Email)
            <button type="button" wire:click="resend" class="font-medium text-brand-600 hover:text-brand-700">Send a new code</button>
        @else
            <button type="button" wire:click="toggleRecovery" class="font-medium text-brand-600 hover:text-brand-700">
                {{ $recovery ? 'Use my authenticator app' : 'Use a recovery code' }}
            </button>
        @endif
        <button type="button" wire:click="cancel" class="text-slate-500 hover:text-slate-700">Back to sign in</button>
    </div>
</div>
