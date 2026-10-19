<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Company settings stored in the database: branding, timezone and SMTP.
 */
class Settings
{
    public const DEFAULTS = [
        // Branding
        'app_name' => 'Task Tracker',
        'company_name' => '',
        'tagline' => 'Plan, assign and track your team\'s work.',
        'primary_color' => '#4f46e5',
        'logo_path' => null,
        'logo_disk' => null,
        'footer_text' => '',

        // General
        'timezone' => 'UTC',
        'default_reminder_minutes' => 60,
        'notify_on_assignment' => true,

        // Security
        'two_factor_required' => false,
        'two_factor_trusted_devices' => true,

        // Mail (SMTP)
        'mail_enabled' => false,
        'mail_host' => '',
        'mail_port' => 587,
        'mail_username' => '',
        'mail_password' => '',
        'mail_encryption' => 'tls',
        'mail_from_address' => '',
        'mail_from_name' => '',
    ];

    protected const ENCRYPTED = ['mail_password'];

    protected const CACHE_KEY = 'app.settings';

    protected ?array $values = null;

    public function all(): array
    {
        return $this->values ??= array_merge(self::DEFAULTS, ['timezone' => config('app.timezone')], $this->load());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            if (in_array($key, self::ENCRYPTED, true) && filled($value)) {
                $value = Crypt::encryptString($value);
            }

            Setting::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        }

        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    public function logoUrl(): ?string
    {
        $path = $this->get('logo_path');

        return $path ? route('branding.logo', ['v' => substr(md5($path), 0, 8)]) : null;
    }

    protected function load(): array
    {
        try {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::pluck('value', 'key')
                ->map(fn ($value) => json_decode($value, true))
                ->all());
        } catch (Throwable) {
            // The database isn't ready yet (e.g. before installation).
            return [];
        }

        foreach (self::ENCRYPTED as $key) {
            if (filled($stored[$key] ?? null)) {
                try {
                    $stored[$key] = Crypt::decryptString($stored[$key]);
                } catch (DecryptException) {
                    $stored[$key] = '';
                }
            }
        }

        return $stored;
    }
}
