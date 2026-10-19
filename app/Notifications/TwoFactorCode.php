<?php

namespace App\Notifications;

use App\Support\TwoFactor;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * The sign-in code. Not queued: the person is waiting for it.
 */
class TwoFactorCode extends Notification
{
    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your '.settings('app_name').' sign-in code')
            ->greeting('Your sign-in code')
            ->line('Enter this code to finish signing in:')
            ->line(new HtmlString('<div style="font-size: 30px; font-weight: 700; letter-spacing: 8px; text-align: center; padding: 12px 0; color: #0f172a;">'.e($this->code).'</div>'))
            ->line('It expires in '.TwoFactor::CODE_MINUTES.' minutes.')
            ->line("If you didn't try to sign in, someone may know your password. Change it from your profile as soon as you can.");
    }
}
