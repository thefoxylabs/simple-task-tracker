<?php

namespace App\Livewire;

use App\Notifications\TestMail;
use App\Support\Mailer;
use App\Support\Settings as SettingsStore;
use App\Support\Uploads;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Title('Settings')]
class Settings extends Component
{
    use WithFileUploads;

    #[Url]
    public string $tab = 'branding';

    // Branding
    public string $app_name = '';

    public string $company_name = '';

    public string $tagline = '';

    public string $primary_color = '';

    public string $footer_text = '';

    public $logo;

    // General
    public string $timezone = '';

    public string $default_reminder_minutes = '';

    public bool $notify_on_assignment = true;

    // Mail
    public bool $mail_enabled = false;

    public string $mail_host = '';

    public string $mail_port = '';

    public string $mail_username = '';

    public string $mail_password = '';

    public string $mail_encryption = '';

    public string $mail_from_address = '';

    public string $mail_from_name = '';

    public string $test_email = '';

    public function boot(): void
    {
        Gate::authorize('admin');
    }

    public function mount(SettingsStore $settings): void
    {
        foreach (['app_name', 'company_name', 'tagline', 'primary_color', 'footer_text', 'timezone', 'mail_host', 'mail_username', 'mail_encryption', 'mail_from_address', 'mail_from_name'] as $key) {
            $this->{$key} = (string) $settings->get($key);
        }

        $this->default_reminder_minutes = (string) $settings->get('default_reminder_minutes');
        $this->mail_port = (string) $settings->get('mail_port');
        $this->notify_on_assignment = (bool) $settings->get('notify_on_assignment');
        $this->mail_enabled = (bool) $settings->get('mail_enabled');
        $this->test_email = auth()->user()->email;
    }

    public function saveBranding(SettingsStore $settings): void
    {
        $data = $this->validate([
            'app_name' => ['required', 'string', 'max:60'],
            'company_name' => ['nullable', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'footer_text' => ['nullable', 'string', 'max:200'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        unset($data['logo']);

        if ($this->logo) {
            $disk = Uploads::disk();
            $path = $this->logo->store('branding', $disk);

            if (! $path) {
                $this->addError('logo', 'The logo could not be uploaded. Check your storage (R2) settings in .env.');

                return;
            }

            $this->deleteLogoFile($settings);
            $data += ['logo_path' => $path, 'logo_disk' => $disk];
            $this->logo = null;
        }

        $settings->set(array_map(fn ($v) => $v ?? '', $data));
        $this->reloadWorkers();
        $this->redirectRoute('settings', ['tab' => 'branding']);
        session()->flash('status', 'Branding saved');
    }

    public function removeLogo(SettingsStore $settings): void
    {
        $this->deleteLogoFile($settings);
        $settings->set(['logo_path' => null, 'logo_disk' => null]);
        $this->reloadWorkers();
        $this->redirectRoute('settings', ['tab' => 'branding']);
    }

    public function saveGeneral(SettingsStore $settings): void
    {
        $this->validate([
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'default_reminder_minutes' => ['required', Rule::in(array_keys(TaskForm::REMINDERS))],
            'notify_on_assignment' => ['boolean'],
        ]);

        $settings->set([
            'timezone' => $this->timezone,
            'default_reminder_minutes' => (int) $this->default_reminder_minutes,
            'notify_on_assignment' => $this->notify_on_assignment,
        ]);
        $this->reloadWorkers();

        $this->dispatch('notify', message: 'Settings saved');
    }

    public function saveMail(SettingsStore $settings): void
    {
        $this->validate([
            'mail_enabled' => ['boolean'],
            'mail_host' => [$this->mail_enabled ? 'required' : 'nullable', 'string', 'max:255'],
            'mail_port' => [$this->mail_enabled ? 'required' : 'nullable', 'integer', 'between:1,65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
            'mail_from_address' => [$this->mail_enabled ? 'required' : 'nullable', 'email'],
            'mail_from_name' => ['nullable', 'string', 'max:100'],
        ]);

        $values = [
            'mail_enabled' => $this->mail_enabled,
            'mail_host' => $this->mail_host,
            'mail_port' => (int) $this->mail_port ?: 587,
            'mail_username' => $this->mail_username,
            'mail_encryption' => $this->mail_encryption,
            'mail_from_address' => $this->mail_from_address,
            'mail_from_name' => $this->mail_from_name,
        ];

        // Leave the stored password alone unless a new one was typed.
        if (filled($this->mail_password)) {
            $values['mail_password'] = $this->mail_password;
        }

        $settings->set($values);
        Mailer::configure($settings);
        $this->reloadWorkers();
        $this->mail_password = '';

        $this->dispatch('notify', message: 'Mail settings saved');
    }

    public function sendTestMail(SettingsStore $settings): void
    {
        $this->validate(['test_email' => ['required', 'email']]);

        Mailer::configure($settings);

        try {
            Notification::route('mail', $this->test_email)->notifyNow(new TestMail);
            $this->dispatch('notify', message: 'Test email sent to '.$this->test_email);
        } catch (Throwable $e) {
            $this->addError('test_email', 'Sending failed: '.$e->getMessage());
        }
    }

    /**
     * Long-running queue workers load settings once at start-up; ask them to
     * restart so queued emails use the new branding and SMTP details.
     */
    protected function reloadWorkers(): void
    {
        Artisan::call('queue:restart');
    }

    protected function deleteLogoFile(SettingsStore $settings): void
    {
        if ($path = $settings->get('logo_path')) {
            Storage::disk($settings->get('logo_disk') ?: Uploads::disk())->delete($path);
        }
    }

    public function render()
    {
        $this->tab = in_array($this->tab, ['branding', 'general', 'mail'], true) ? $this->tab : 'branding';

        return view('livewire.settings', [
            'hasStoredPassword' => filled(settings('mail_password')),
            'envMailer' => config('mail.default'),
        ]);
    }
}
