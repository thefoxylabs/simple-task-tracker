<div class="card mt-6">
    <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex gap-4">
            <div class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $this->method ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6Z"/>@if ($this->method)<path d="M9 12l2 2 4-4"/>@endif</svg>
            </div>
            <div>
                <h2 class="font-semibold text-slate-900">Two-factor authentication</h2>
                <p class="mt-1 text-sm text-slate-500">A second step when you sign in, so a stolen password isn't enough to get into your account.</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if ($this->method)
                        <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-600/20">On · {{ $this->method->label() }}</span>
                    @else
                        <span class="badge bg-slate-50 text-slate-600 ring-slate-500/20">Off</span>
                    @endif
                    @if ($this->required)
                        <span class="badge bg-brand-50 text-brand-700 ring-brand-600/20">Required by your organisation</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-100 p-6">
        {{-- Password confirmation --}}
        @if ($pendingAction)
            <form wire:submit="confirmPassword" class="max-w-md">
                <label for="tf-password" class="label">Confirm your password to continue</label>
                <div class="flex gap-2">
                    <input wire:model="password" id="tf-password" type="password" class="input" autocomplete="current-password" autofocus>
                    <button type="submit" class="btn btn-primary">Continue</button>
                    <button type="button" wire:click="cancel" class="btn btn-ghost">Cancel</button>
                </div>
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </form>

        {{-- Authenticator app set-up --}}
        @elseif ($settingUpApp)
            <div class="grid gap-6 md:grid-cols-[auto_1fr]">
                <div class="mx-auto rounded-xl bg-white p-3 ring-1 ring-slate-200 [&_svg]:size-44">{!! $this->qrCode !!}</div>
                <div>
                    <ol class="space-y-2 text-sm text-slate-600">
                        <li><b class="text-slate-900">1.</b> Open Google Authenticator, Microsoft Authenticator, 1Password or a similar app.</li>
                        <li><b class="text-slate-900">2.</b> Scan the QR code, or enter this key: <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-800 select-all">{{ $this->setupKey }}</code></li>
                        <li><b class="text-slate-900">3.</b> Enter the 6-digit code the app shows.</li>
                    </ol>
                    <form wire:submit="confirmApp" class="mt-4 flex max-w-md flex-wrap gap-2 sm:flex-nowrap">
                        <input wire:model="code" type="text" inputmode="numeric" maxlength="7" autocomplete="one-time-code" placeholder="123456" class="input text-center font-mono tracking-[0.3em]" autofocus>
                        <button type="submit" class="btn btn-primary shrink-0 whitespace-nowrap">Turn on</button>
                        <button type="button" wire:click="cancel" class="btn btn-ghost">Cancel</button>
                    </form>
                    @error('code') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>

        {{-- Recovery codes --}}
        @elseif ($recoveryCodes)
            <div class="rounded-xl bg-amber-50 p-5 ring-1 ring-amber-600/20">
                <p class="text-sm font-semibold text-amber-900">Save your recovery codes</p>
                <p class="mt-1 text-sm text-amber-800">If you lose your phone, each code lets you sign in once. Keep them somewhere safe, like a password manager.</p>
                <div class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm sm:grid-cols-4" x-data>
                    @foreach ($recoveryCodes as $recoveryCode)
                        <span class="rounded-md bg-white px-2 py-1.5 text-center text-slate-800 ring-1 ring-amber-200">{{ $recoveryCode }}</span>
                    @endforeach
                </div>
                <div class="mt-4 flex flex-wrap gap-2" x-data="{ copied: false }">
                    <button type="button" class="btn btn-secondary" x-on:click="navigator.clipboard.writeText(@js(implode("\n", $recoveryCodes))); copied = true; setTimeout(() => copied = false, 1500)">
                        <span x-text="copied ? 'Copied!' : 'Copy codes'">Copy codes</span>
                    </button>
                    <a class="btn btn-secondary" download="{{ \Illuminate\Support\Str::slug(settings('app_name')) }}-recovery-codes.txt"
                       href="data:text/plain;charset=utf-8,{{ rawurlencode(settings('app_name')." recovery codes\n\n".implode("\n", $recoveryCodes)) }}">Download</a>
                    <button type="button" wire:click="hideRecoveryCodes" class="btn btn-primary">I've saved them</button>
                </div>
            </div>

        {{-- Options --}}
        @else
            <div class="grid gap-3 md:grid-cols-2">
                <div @class(['rounded-xl p-4 ring-1', 'bg-brand-50/50 ring-brand-600/30' => $this->method === \App\Enums\TwoFactorMethod::Email, 'ring-slate-200' => $this->method !== \App\Enums\TwoFactorMethod::Email])>
                    <p class="flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="mail" class="size-4 text-slate-400" /> Email codes</p>
                    <p class="mt-1 text-sm text-slate-500">We email a 6-digit code to {{ $this->user->maskedEmail() }} each time you sign in.</p>
                    <div class="mt-3">
                        @if ($this->method === \App\Enums\TwoFactorMethod::Email)
                            <span class="text-sm font-medium text-brand-700">In use</span>
                        @elseif ($this->method === \App\Enums\TwoFactorMethod::App)
                            <button type="button" wire:click="ask('switchToEmail')" class="btn btn-secondary">Use email codes instead</button>
                        @else
                            <button type="button" wire:click="ask('enableEmail')" class="btn btn-primary">Turn on</button>
                        @endif
                    </div>
                </div>

                <div @class(['rounded-xl p-4 ring-1', 'bg-brand-50/50 ring-brand-600/30' => $this->method === \App\Enums\TwoFactorMethod::App, 'ring-slate-200' => $this->method !== \App\Enums\TwoFactorMethod::App])>
                    <p class="flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="check-circle" class="size-4 text-slate-400" /> Authenticator app <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-600/20">More secure</span></p>
                    <p class="mt-1 text-sm text-slate-500">Get codes from Google Authenticator or a similar app, even without signal.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($this->method === \App\Enums\TwoFactorMethod::App)
                            <button type="button" wire:click="ask('showRecoveryCodes')" class="btn btn-secondary">Recovery codes</button>
                            <button type="button" wire:click="ask('regenerateRecoveryCodes')" class="btn btn-ghost">New codes</button>
                        @else
                            <button type="button" wire:click="ask('startAppSetup')" class="btn btn-secondary">Set up app</button>
                        @endif
                    </div>
                </div>
            </div>

            @if ($this->method && ! $this->required)
                <button type="button" wire:click="ask('disable')" class="mt-4 text-sm font-medium text-red-600 hover:text-red-700">Turn off two-factor authentication</button>
            @endif
        @endif
    </div>
</div>
