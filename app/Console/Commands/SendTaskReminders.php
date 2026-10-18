<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskReminder;
use App\Support\Mailer;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    protected $signature = 'tasks:send-reminders';

    protected $description = 'Email staff about tasks whose reminder time has arrived';

    public function handle(): int
    {
        $sent = 0;

        Task::query()
            ->open()
            ->whereNotNull('assignee_id')
            ->whereNull('reminder_sent_at')
            ->where('remind_at', '<=', now())
            ->with(['assignee', 'project'])
            ->chunkById(100, function ($tasks) use (&$sent) {
                foreach ($tasks as $task) {
                    if (Mailer::notify($task->assignee, new TaskReminder($task))) {
                        $sent++;
                    }

                    // Mark as handled even on failure so a broken mailbox doesn't retry every minute.
                    $task->forceFill(['reminder_sent_at' => now()])->saveQuietly();
                }
            });

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
