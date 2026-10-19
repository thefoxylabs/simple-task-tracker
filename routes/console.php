<?php

use App\Support\SystemStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

// Lets Settings → System status confirm the cron entry is working.
Schedule::call(fn () => Cache::forever(SystemStatus::HEARTBEAT, now()->timestamp))
    ->name('heartbeat')
    ->everyMinute();

Schedule::command('tasks:send-reminders')->everyMinute()->withoutOverlapping();
Schedule::command('tasks:generate-recurring')->everyFifteenMinutes()->withoutOverlapping();

// Failed email jobs are kept for a week, then removed.
Schedule::command('queue:prune-failed', ['--hours=168'])->daily();

// Send queued emails from the same cron entry, so no separate worker is
// needed. Set QUEUE_WORKER_IN_SCHEDULER=false if you run `queue:work`
// yourself (e.g. with Supervisor).
if (config('queue.worker_in_scheduler')) {
    Schedule::command('queue:work', ['--stop-when-empty', '--max-time=50', '--tries=4'])
        ->name('queue-worker')
        ->everyMinute()
        ->withoutOverlapping(5)
        ->runInBackground();
}
