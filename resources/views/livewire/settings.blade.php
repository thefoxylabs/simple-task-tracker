<div>
    <x-page-header title="Settings" description="Make this workspace your own." />

    <div class="segmented mb-6 sm:inline-flex">
        @foreach (['branding' => ['Branding', 'paint'], 'general' => ['General', 'cog'], 'mail' => ['Email (SMTP)', 'mail']] as $key => [$label, $icon])
            <button type="button" wire:click="$set('tab', '{{ $key }}')" class="{{ $tab === $key ? 'tab tab-active' : 'tab' }} flex items-center gap-1.5">
                <x-icon :name="$icon" class="size-4" /> {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'branding')
        <form wire:submit="saveBranding" class="card max-w-3xl">
            <div class="space-y-6 p-6">
                <div>
                    <h2 class="font-semibold text-slate-900">Branding</h2>
                    <p class="mt-1 text-sm text-slate-500">Your name, logo and colour are used across the app, the sign-in page and emails.</p>
                </div>

                <div class="flex flex-wrap items-center gap-5">
                    <div class="flex size-20 items-center justify-center overflow-hidden rounded-xl bg-slate-50 ring-1 ring-slate-200">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" class="max-h-16 max-w-16 object-contain" alt="">
                        @elseif ($url = settings()->logoUrl())
                            <img src="{{ $url }}" class="max-h-16 max-w-16 object-contain" alt="">
                        @else
                            <x-icon name="paint" class="size-8 text-slate-300" />
                        @endif
                    </div>
                    <div>
                        <label class="btn btn-secondary cursor-pointer">
                            <span wire:loading.remove wire:target="logo">Upload logo</span>
                            <span wire:loading wire:target="logo">Uploading…</span>
                            <input type="file" wire:model="logo" accept="image/*" class="sr-only">
                        </label>
                        @if (settings('logo_path'))
                            <button type="button" wire:click="removeLogo" class="btn btn-ghost text-red-600">Remove</button>
                        @endif
                        <p class="mt-2 text-xs text-slate-500">PNG, JPG or WebP, up to 2 MB. Also used as the browser icon.</p>
                        @error('logo') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="app_name" class="label">App name</label>
                        <input wire:model="app_name" id="app_name" type="text" class="input">
                        @error('app_name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="company_name" class="label">Company name</label>
                        <input wire:model="company_name" id="company_name" type="text" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="tagline" class="label">Sign-in page tagline</label>
                        <input wire:model="tagline" id="tagline" type="text" class="input">
                        @error('tagline') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="primary_color" class="label">Brand colour</label>
                        <div class="flex items-center gap-2" x-data>
                            <input type="color" wire:model.live="primary_color" class="h-9 w-12 cursor-pointer rounded-lg border-0 bg-transparent p-0">
                            <input wire:model.live.debounce.400ms="primary_color" id="primary_color" type="text" class="input font-mono" maxlength="7">
                        </div>
                        @error('primary_color') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <p class="label">Preview</p>
                        <div class="flex items-center gap-2" style="--brand: {{ preg_match('/^#[0-9a-fA-F]{6}$/', $primary_color) ? $primary_color : '#4f46e5' }}">
                            <span class="btn btn-primary pointer-events-none">Button</span>
                            <span class="badge bg-brand-50 text-brand-700 ring-brand-600/20">Badge</span>
                            <span class="h-1.5 w-16 rounded-full bg-brand-600"></span>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="footer_text" class="label">Footer text</label>
                        <input wire:model="footer_text" id="footer_text" type="text" class="input" placeholder="© {{ date('Y') }} Your Company. Internal use only.">
                    </div>
                </div>
            </div>
            <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Save branding</button>
            </div>
        </form>
    @elseif ($tab === 'general')
        <form wire:submit="saveGeneral" class="card max-w-3xl">
            <div class="space-y-6 p-6">
                <div>
                    <h2 class="font-semibold text-slate-900">General</h2>
                    <p class="mt-1 text-sm text-slate-500">Defaults for deadlines and notifications.</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="timezone" class="label">Time zone</label>
                        <select wire:model="timezone" id="timezone" class="input">
                            @foreach (timezone_identifiers_list() as $tz)<option value="{{ $tz }}">{{ $tz }}</option>@endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-slate-500">Deadlines and reminders use this time zone.</p>
                        @error('timezone') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="default_reminder" class="label">Default reminder for new tasks</label>
                        <select wire:model="default_reminder_minutes" id="default_reminder" class="input">
                            @foreach (\App\Livewire\TaskForm::REMINDERS as $minutes => $label)<option value="{{ $minutes }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input wire:model="notify_on_assignment" type="checkbox" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                    Email staff when a task is assigned to them
                </label>
            </div>
            <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                <button type="submit" class="btn btn-primary">Save settings</button>
            </div>
        </form>
    @else
        <div class="grid max-w-5xl gap-6 lg:grid-cols-3">
            <form wire:submit="saveMail" class="card lg:col-span-2">
                <div class="space-y-6 p-6">
                    <div>
                        <h2 class="font-semibold text-slate-900">Email (SMTP)</h2>
                        <p class="mt-1 text-sm text-slate-500">Used for task assignments, reminders, invitations and password resets.</p>
                    </div>
                    <label class="flex items-start gap-3 rounded-lg bg-slate-50 p-4 ring-1 ring-slate-200">
                        <input wire:model.live="mail_enabled" type="checkbox" class="mt-0.5 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                        <span class="text-sm">
                            <span class="font-medium text-slate-900">Use these SMTP settings</span>
                            <span class="block text-slate-500">When off, the mail settings from the <code>.env</code> file are used (currently <code>{{ $envMailer }}</code>).</span>
                        </span>
                    </label>
                    <div class="grid gap-5 sm:grid-cols-6 {{ $mail_enabled ? '' : 'opacity-60' }}">
                        <div class="sm:col-span-4">
                            <label for="mail_host" class="label">SMTP host</label>
                            <input wire:model="mail_host" id="mail_host" type="text" class="input" placeholder="smtp.example.com">
                            @error('mail_host') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="mail_port" class="label">Port</label>
                            <input wire:model="mail_port" id="mail_port" type="number" class="input" placeholder="587">
                            @error('mail_port') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-3">
                            <label for="mail_username" class="label">Username</label>
                            <input wire:model="mail_username" id="mail_username" type="text" class="input" autocomplete="off">
                        </div>
                        <div class="sm:col-span-3">
                            <label for="mail_password" class="label">Password</label>
                            <input wire:model="mail_password" id="mail_password" type="password" class="input" autocomplete="new-password" placeholder="{{ $hasStoredPassword ? '•••••••• (saved)' : '' }}">
                            <p class="mt-1 text-xs text-slate-500">Stored encrypted. Leave blank to keep the current one.</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="mail_encryption" class="label">Encryption</label>
                            <select wire:model="mail_encryption" id="mail_encryption" class="input">
                                <option value="tls">STARTTLS (587)</option>
                                <option value="ssl">SSL/TLS (465)</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="mail_from_address" class="label">From address</label>
                            <input wire:model="mail_from_address" id="mail_from_address" type="email" class="input" placeholder="tasks@company.com">
                            @error('mail_from_address') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="mail_from_name" class="label">From name</label>
                            <input wire:model="mail_from_name" id="mail_from_name" type="text" class="input" placeholder="{{ $app_name }}">
                        </div>
                    </div>
                </div>
                <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                    <button type="submit" class="btn btn-primary">Save mail settings</button>
                </div>
            </form>

            <form wire:submit="sendTestMail" class="card h-fit p-6">
                <h2 class="font-semibold text-slate-900">Send a test email</h2>
                <p class="mt-1 text-sm text-slate-500">Save your settings first, then check they work.</p>
                <label for="test_email" class="label mt-4">Send to</label>
                <input wire:model="test_email" id="test_email" type="email" class="input">
                @error('test_email') <p class="error break-words">{{ $message }}</p> @enderror
                <button type="submit" class="btn btn-secondary mt-4 w-full" wire:loading.attr="disabled" wire:target="sendTestMail">
                    <x-icon name="mail" class="size-4" />
                    <span wire:loading.remove wire:target="sendTestMail">Send test email</span>
                    <span wire:loading wire:target="sendTestMail">Sending…</span>
                </button>
                <div class="mt-6 rounded-lg bg-amber-50 p-3 text-xs text-amber-800 ring-1 ring-amber-600/20">
                    Emails are queued and sent by the scheduler, together with reminders. Add this cron entry on your server:
                    <code class="mt-1 block break-all">* * * * * php {{ base_path('artisan') }} schedule:run</code>
                </div>
            </form>
        </div>
    @endif
</div>
