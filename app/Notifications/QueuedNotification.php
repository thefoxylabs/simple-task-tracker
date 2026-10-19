<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Base for emails that are sent in the background by the queue worker,
 * so a slow or failing mail server never slows the app down.
 */
abstract class QueuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Attempts before the job is recorded in failed_jobs. */
    public int $tries = 4;

    /** Skip the email if the task (or user) was deleted before it was sent. */
    public bool $deleteWhenMissingModels = true;

    public function __construct()
    {
        // Wait for the database transaction that created the task to commit.
        $this->afterCommit();
    }

    /** Seconds to wait between retries. */
    public function backoff(): array
    {
        return [60, 300, 900];
    }
}
