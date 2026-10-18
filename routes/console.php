<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('tasks:send-reminders')->everyMinute()->withoutOverlapping();
Schedule::command('tasks:generate-recurring')->everyFifteenMinutes()->withoutOverlapping();
