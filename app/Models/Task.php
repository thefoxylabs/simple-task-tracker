<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Concerns\HasAttachments;
use App\Notifications\TaskAssigned;
use App\Support\Mailer;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasAttachments;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'project_id',
        'plan_id',
        'recurring_task_id',
        'assignee_id',
        'created_by',
        'due_at',
        'remind_at',
        'position',
    ];

    protected $attributes = [
        'status' => 'todo',
        'priority' => 'medium',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_at' => 'datetime',
            'remind_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            if ($task->isDirty('status')) {
                $task->completed_at = $task->status === TaskStatus::Done ? now() : null;
            }

            // A new reminder time means the reminder should be sent again.
            if ($task->isDirty('remind_at')) {
                $task->reminder_sent_at = null;
            }
        });

        static::updated(function (Task $task) {
            // Make sure cleared timestamps are cleared in the database even if
            // this model instance was loaded before they were set elsewhere.
            $cleared = array_keys(array_filter([
                'completed_at' => $task->wasChanged('status') && $task->status !== TaskStatus::Done,
                'reminder_sent_at' => $task->wasChanged('remind_at'),
            ]));

            if ($cleared) {
                static::whereKey($task->id)->update(array_fill_keys($cleared, null));
            }
        });

        static::saved(function (Task $task) {

            $assigneeChanged = $task->wasRecentlyCreated || $task->wasChanged('assignee_id');

            if ($assigneeChanged && $task->assignee && $task->assignee_id !== auth()->id()) {
                Mailer::notify($task->assignee, new TaskAssigned($task));
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recurringTask(): BelongsTo
    {
        return $this->belongsTo(RecurringTask::class);
    }

    /** Staff only see tasks assigned to them or created by them. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->canManage()) {
            return;
        }

        $query->where(fn (Builder $q) => $q
            ->where('assignee_id', $user->id)
            ->orWhere('created_by', $user->id));
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', TaskStatus::Done);
    }

    public function scopeOverdue(Builder $query): void
    {
        $query->open()->whereNotNull('due_at')->where('due_at', '<', now());
    }

    /** Tasks due in the range, or completed in it. */
    public function scopeInRange(Builder $query, CarbonInterface $start, CarbonInterface $end): void
    {
        $query->where(fn (Builder $q) => $q
            ->whereBetween('due_at', [$start, $end])
            ->orWhereBetween('completed_at', [$start, $end]));
    }

    public function isOverdue(): bool
    {
        return $this->status !== TaskStatus::Done && $this->due_at?->isPast();
    }

    public function isDueSoon(): bool
    {
        return $this->status !== TaskStatus::Done
            && $this->due_at
            && ! $this->due_at->isPast()
            && $this->due_at->lte(now()->addDay());
    }
}
