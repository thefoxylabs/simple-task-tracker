<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class StaffInvitation extends QueuedNotification
{
    public function __construct(public string $token, public string $invitedBy)
    {
        parent::__construct();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject('You\'ve been invited to '.settings('app_name'))
            ->greeting('Welcome, '.$notifiable->name.'!')
            ->line($this->invitedBy.' created an account for you on '.settings('app_name').'.')
            ->line('Choose a password to get started. Your email address is '.$notifiable->email.'.')
            ->action('Set your password', $url)
            ->line('This link expires in '.config('auth.passwords.users.expire').' minutes. If it expires, use "Forgot password" on the sign-in page.');
    }
}
