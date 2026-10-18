<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasAttachments;

    protected static function booted(): void
    {
        // Tasks are removed by the database cascade; clean up their files first.
        static::deleting(fn (Project $project) => $project->tasks()->has('attachments')->get()->each(
            fn (Task $task) => $task->attachments->each->delete()
        ));
    }

    protected $fillable = ['name', 'description', 'color', 'lead_id', 'starts_on', 'due_on', 'archived_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'due_on' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** Eager-load task totals used for progress bars. */
    public function scopeWithProgress(Builder $query): void
    {
        $query->withCount([
            'tasks',
            'tasks as done_tasks_count' => fn (Builder $q) => $q->where('status', TaskStatus::Done),
            'tasks as overdue_tasks_count' => fn (Builder $q) => $q->overdue(),
        ]);
    }

    public function progress(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        $done = $this->done_tasks_count ?? $this->tasks()->where('status', TaskStatus::Done)->count();

        return $total ? (int) round($done / $total * 100) : 0;
    }
}
