<?php

namespace App\Models;

use App\Enums\Period;
use App\Enums\TaskPriority;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringTask extends Model
{
    protected $fillable = [
        'title',
        'description',
        'frequency',
        'priority',
        'project_id',
        'assignee_id',
        'starts_on',
        'ends_on',
        'due_time',
        'remind_before_minutes',
        'next_run_on',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => Period::class,
            'priority' => TaskPriority::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'next_run_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isFinished(): bool
    {
        return $this->ends_on !== null && $this->next_run_on->gt($this->ends_on);
    }

    /**
     * Create the task for the latest occurrence that is due, if any.
     *
     * Missed occurrences (e.g. the scheduler was down) are skipped so staff
     * don't get flooded with a backlog of identical tasks.
     */
    public function generateDueTask(?CarbonImmutable $today = null): ?Task
    {
        $today ??= CarbonImmutable::today();

        if (! $this->is_active || $this->isFinished() || $this->next_run_on->gt($today)) {
            return null;
        }

        $occurrence = CarbonImmutable::instance($this->next_run_on);

        while ($this->frequency->shift($occurrence)->lte($today)) {
            $occurrence = $this->frequency->shift($occurrence);
        }

        if ($this->ends_on && $occurrence->gt($this->ends_on)) {
            return null;
        }

        [$hour, $minute] = array_map('intval', explode(':', $this->due_time));
        $dueAt = $occurrence->setTime($hour, $minute);

        $task = $this->tasks()->create([
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'project_id' => $this->project_id,
            'assignee_id' => $this->assignee_id,
            'created_by' => $this->created_by,
            'due_at' => $dueAt,
            'remind_at' => $this->remind_before_minutes !== null ? $dueAt->subMinutes($this->remind_before_minutes) : null,
            'position' => 0,
        ]);

        $this->update(['next_run_on' => $this->frequency->shift($occurrence)]);

        return $task;
    }
}
