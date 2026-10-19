<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Throwable;

class Mailer
{
    /**
     * Send a notification without letting a mail failure break the request.
     */
    public static function notify(User $user, Notification $notification): bool
    {
        if (! $user->is_active) {
            return false;
        }

        try {
            $user->notify($notification);

            return true;
        } catch (Throwable $e) {
            report($e);
            SystemStatus::recordMailFailure($e);

            return false;
        }
    }

    /**
     * Point Laravel's mailer at the SMTP details saved in settings.
     */
    public static function configure(Settings $settings): void
    {
        $name = $settings->get('mail_from_name') ?: $settings->get('app_name');

        if (filled($settings->get('mail_from_address'))) {
            config(['mail.from' => ['address' => $settings->get('mail_from_address'), 'name' => $name]]);
        } else {
            config(['mail.from.name' => $name]);
        }

        if (! $settings->get('mail_enabled') || blank($settings->get('mail_host'))) {
            return;
        }

        $encryption = $settings->get('mail_encryption');

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.host' => $settings->get('mail_host'),
            'mail.mailers.smtp.port' => (int) $settings->get('mail_port'),
            'mail.mailers.smtp.username' => $settings->get('mail_username') ?: null,
            'mail.mailers.smtp.password' => $settings->get('mail_password') ?: null,
            'mail.mailers.smtp.auto_tls' => $encryption !== 'none',
            'mail.mailers.smtp.require_tls' => $encryption === 'tls',
            'mail.mailers.smtp.timeout' => 15,
        ]);
    }
}
