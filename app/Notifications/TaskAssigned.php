<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Messages\MailMessage;

class TaskAssigned extends QueuedNotification
{
    public function __construct(public Task $task)
    {
        parent::__construct();
    }

    public function via(object $notifiable): array
    {
        return settings('notify_on_assignment') ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $task = $this->task;

        $message = (new MailMessage)
            ->subject('New task: '.$task->title)
            ->greeting('Hi '.$notifiable->name.',')
            ->line(($task->creator?->name ?? settings('app_name')).' assigned you a new task.')
            ->line('**'.$task->title.'**');

        return TaskReminder::withDetails($message, $task)
            ->action('View task', route('tasks.show', $task));
    }
}
