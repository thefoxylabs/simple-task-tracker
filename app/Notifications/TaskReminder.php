<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReminder extends Notification
{
    public function __construct(public Task $task) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $task = $this->task;

        $when = $task->due_at
            ? ($task->due_at->isPast() ? 'was due '.$task->due_at->diffForHumans() : 'is due '.$task->due_at->diffForHumans())
            : 'needs your attention';

        $message = (new MailMessage)
            ->subject('Reminder: '.$task->title)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('This is a reminder that **'.$task->title.'** '.$when.'.');

        return self::withDetails($message, $task)
            ->action('Open task', route('tasks.show', $task));
    }

    public static function withDetails(MailMessage $message, Task $task): MailMessage
    {
        if ($task->project) {
            $message->line('Project: '.$task->project->name);
        }

        $message->line('Priority: '.$task->priority->label());

        if ($task->due_at) {
            $message->line('Deadline: '.$task->due_at->format('D, j M Y \a\t g:i A'));
        }

        return $message;
    }
}
