<?php

namespace App\Console\Commands;

use App\Models\RecurringTask;
use Illuminate\Console\Command;

class GenerateRecurringTasks extends Command
{
    protected $signature = 'tasks:generate-recurring';

    protected $description = 'Create tasks for recurring schedules that are due';

    public function handle(): int
    {
        $created = 0;

        RecurringTask::query()
            ->where('is_active', true)
            ->whereDate('next_run_on', '<=', today())
            ->each(function (RecurringTask $recurring) use (&$created) {
                if ($recurring->generateDueTask()) {
                    $created++;
                }
            });

        $this->info("Created {$created} task(s).");

        return self::SUCCESS;
    }
}
