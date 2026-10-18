<?php

namespace Database\Seeders;

use App\Enums\Period;
use App\Enums\Role;
use App\Models\Plan;
use App\Models\Project;
use App\Models\RecurringTask;
use App\Models\StaffGroup;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Sample company data for trying the app out.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Sign in as admin@example.com / password (or manager@ / staff@).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        settings()->set([
            'app_name' => 'Acme Tasks',
            'company_name' => 'Acme Inc.',
            'tagline' => 'Plan, assign and track your team\'s work.',
        ]);

        $groups = collect([
            ['name' => 'Sales', 'color' => '#0ea5e9', 'description' => 'Account executives and business development.'],
            ['name' => 'Operations', 'color' => '#10b981', 'description' => 'Finance, HR and office operations.'],
            ['name' => 'Design', 'color' => '#8b5cf6', 'description' => 'Brand, product and web design.'],
            ['name' => 'Engineering', 'color' => '#f59e0b', 'description' => 'Web and mobile development.'],
        ])->mapWithKeys(fn ($g) => [$g['name'] => StaffGroup::create($g)]);

        $people = [
            ['Ada Okafor', 'admin@example.com', Role::Admin, null, 'Operations director'],
            ['Marcus Bell', 'manager@example.com', Role::Manager, 'Sales', 'Head of sales'],
            ['Priya Nair', 'priya@example.com', Role::Manager, 'Engineering', 'Engineering lead'],
            ['Sam Carter', 'staff@example.com', Role::Staff, 'Sales', 'Account executive'],
            ['Lena Fischer', 'lena@example.com', Role::Staff, 'Design', 'Product designer'],
            ['Tomás Ruiz', 'tomas@example.com', Role::Staff, 'Engineering', 'Developer'],
            ['Grace Kim', 'grace@example.com', Role::Staff, 'Operations', 'Finance officer'],
            ['Daniel Mensah', 'daniel@example.com', Role::Staff, 'Sales', 'Sales associate'],
        ];

        $users = collect($people)->mapWithKeys(fn ($p) => [$p[1] => User::factory()->create([
            'name' => $p[0],
            'email' => $p[1],
            'role' => $p[2],
            'staff_group_id' => $p[3] ? $groups[$p[3]]->id : null,
            'job_title' => $p[4],
        ])]);

        $u = fn (string $email) => $users[$email]->id;
        $admin = $u('admin@example.com');
        $now = CarbonImmutable::now();

        $website = Project::create(['name' => 'Website redesign', 'description' => 'Refresh the marketing site with the new brand and faster pages.', 'color' => '#8b5cf6', 'lead_id' => $u('lena@example.com'), 'starts_on' => $now->subWeeks(3), 'due_on' => $now->addWeeks(5), 'created_by' => $admin]);
        $q4sales = Project::create(['name' => 'Q4 sales push', 'description' => 'Hit the quarterly revenue target with the enterprise pipeline.', 'color' => '#0ea5e9', 'lead_id' => $u('manager@example.com'), 'starts_on' => $now->startOfQuarter(), 'due_on' => $now->endOfQuarter(), 'created_by' => $admin]);
        $mobile = Project::create(['name' => 'Mobile app v2', 'description' => 'Offline mode, push notifications and a new onboarding flow.', 'color' => '#f59e0b', 'lead_id' => $u('priya@example.com'), 'starts_on' => $now->subMonth(), 'due_on' => $now->addMonths(2), 'created_by' => $admin]);
        $office = Project::create(['name' => 'Office move', 'description' => 'Relocate to the new office.', 'color' => '#10b981', 'lead_id' => $u('grace@example.com'), 'created_by' => $admin, 'archived_at' => $now->subWeeks(2)]);

        $quarterPlan = Plan::create(['title' => 'Close $250k in new business', 'period' => Period::Quarterly, 'starts_on' => $now->startOfQuarter(), 'ends_on' => $now->endOfQuarter(), 'staff_group_id' => $groups['Sales']->id, 'project_id' => $q4sales->id, 'created_by' => $admin]);
        $weekPlan = Plan::create(['title' => 'Ship the new homepage', 'period' => Period::Weekly, 'starts_on' => $now->startOfWeek(), 'ends_on' => $now->endOfWeek(), 'project_id' => $website->id, 'created_by' => $admin]);
        Plan::create(['title' => 'Release mobile app v2', 'period' => Period::Monthly, 'starts_on' => $now->startOfMonth(), 'ends_on' => $now->endOfMonth(), 'project_id' => $mobile->id, 'staff_group_id' => $groups['Engineering']->id, 'created_by' => $admin]);
        Plan::create(['title' => 'Grow revenue 30% year on year', 'period' => Period::Yearly, 'starts_on' => $now->startOfYear(), 'ends_on' => $now->endOfYear(), 'created_by' => $admin]);
        Plan::create(['title' => 'Clear the support inbox', 'period' => Period::Daily, 'starts_on' => $now->startOfDay(), 'ends_on' => $now->endOfDay(), 'user_id' => $u('daniel@example.com'), 'created_by' => $admin]);

        $tasks = [
            // [title, status, priority, project, plan, assignee, due (days from now), hour]
            ['Design new homepage hero', 'done', 'high', $website, $weekPlan, 'lena@example.com', -2, 17],
            ['Write homepage copy', 'review', 'medium', $website, $weekPlan, 'manager@example.com', 1, 12],
            ['Build responsive navigation', 'in_progress', 'high', $website, $weekPlan, 'tomas@example.com', 2, 17],
            ['Optimise images for web', 'todo', 'low', $website, null, 'tomas@example.com', 6, 17],
            ['Set up analytics events', 'todo', 'medium', $website, null, 'priya@example.com', 9, 17],
            ['Demo for Northwind Traders', 'in_progress', 'urgent', $q4sales, $quarterPlan, 'staff@example.com', 0, 15],
            ['Send proposal to Globex', 'todo', 'high', $q4sales, $quarterPlan, 'staff@example.com', -1, 12],
            ['Follow up with 10 warm leads', 'todo', 'medium', $q4sales, $quarterPlan, 'daniel@example.com', 3, 17],
            ['Renewal call with Initech', 'done', 'high', $q4sales, $quarterPlan, 'manager@example.com', -4, 10],
            ['Update CRM pipeline stages', 'review', 'low', $q4sales, null, 'daniel@example.com', 4, 17],
            ['Offline sync for task lists', 'in_progress', 'urgent', $mobile, null, 'tomas@example.com', 5, 17],
            ['Push notification service', 'todo', 'high', $mobile, null, 'priya@example.com', 12, 17],
            ['Onboarding screens', 'review', 'medium', $mobile, null, 'lena@example.com', 2, 17],
            ['App store screenshots', 'todo', 'low', $mobile, null, 'lena@example.com', 20, 17],
            ['Prepare monthly payroll', 'todo', 'urgent', null, null, 'grace@example.com', 1, 9],
            ['Approve expense reports', 'done', 'medium', null, null, 'grace@example.com', -1, 17],
            ['Plan team offsite', 'todo', 'low', null, null, 'admin@example.com', 30, 17],
            ['Reply to support tickets', 'in_progress', 'medium', null, null, 'daniel@example.com', 0, 18],
        ];

        foreach ($tasks as $i => [$title, $status, $priority, $project, $plan, $assignee, $days, $hour]) {
            $due = $now->addDays($days)->setTime($hour, 0);

            $task = Task::withoutEvents(fn () => Task::create([
                'title' => $title,
                'description' => 'Sample task created by the demo seeder.',
                'status' => $status,
                'priority' => $priority,
                'project_id' => $project?->id,
                'plan_id' => $plan?->id,
                'assignee_id' => $u($assignee),
                'created_by' => $admin,
                'due_at' => $due,
                'remind_at' => $due->subHour(),
                'position' => $i,
            ]));

            if ($status === 'done') {
                $task->forceFill(['completed_at' => $due->subHours(3)->min($now), 'reminder_sent_at' => $due->subHour()])->saveQuietly();
            }
        }

        RecurringTask::create(['title' => 'Send weekly sales report', 'frequency' => Period::Weekly, 'priority' => 'medium', 'project_id' => $q4sales->id, 'assignee_id' => $u('manager@example.com'), 'starts_on' => $now->next('Friday'), 'next_run_on' => $now->next('Friday'), 'due_time' => '16:00', 'remind_before_minutes' => 60, 'created_by' => $admin]);
        RecurringTask::create(['title' => 'Reconcile bank accounts', 'frequency' => Period::Monthly, 'priority' => 'high', 'assignee_id' => $u('grace@example.com'), 'starts_on' => $now->startOfMonth()->addMonth(), 'next_run_on' => $now->startOfMonth()->addMonth(), 'due_time' => '17:00', 'remind_before_minutes' => 1440, 'created_by' => $admin]);
        RecurringTask::create(['title' => 'Daily stand-up notes', 'frequency' => Period::Daily, 'priority' => 'low', 'project_id' => $mobile->id, 'assignee_id' => $u('tomas@example.com'), 'starts_on' => $now->addDay(), 'next_run_on' => $now->addDay(), 'due_time' => '10:00', 'remind_before_minutes' => 15, 'created_by' => $admin]);
        RecurringTask::create(['title' => 'Quarterly board report', 'frequency' => Period::Quarterly, 'priority' => 'urgent', 'assignee_id' => $admin, 'starts_on' => $now->endOfQuarter()->subDays(5), 'next_run_on' => $now->endOfQuarter()->subDays(5), 'due_time' => '12:00', 'remind_before_minutes' => 2880, 'created_by' => $admin]);

        Cache::forever('app.installed', true);
    }
}
