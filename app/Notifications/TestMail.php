<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TestMail extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(settings('app_name').' – test email')
            ->greeting('It works!')
            ->line('Your SMTP settings are configured correctly. Task assignments and reminders will be delivered to your team.');
    }
}
