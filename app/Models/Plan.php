<?php

namespace App\Models;

use App\Enums\Period;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = ['title', 'description', 'period', 'starts_on', 'ends_on', 'project_id', 'staff_group_id', 'user_id', 'created_by'];

    protected function casts(): array
    {
        return [
            'period' => Period::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(StaffGroup::class, 'staff_group_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Staff see company-wide plans, their group's plans and their own. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->canManage()) {
            return;
        }

        $query->where(fn (Builder $q) => $q
            ->where('user_id', $user->id)
            ->orWhere(fn (Builder $q) => $q->whereNotNull('staff_group_id')->where('staff_group_id', $user->staff_group_id))
            ->orWhere(fn (Builder $q) => $q->whereNull('user_id')->whereNull('staff_group_id')));
    }

    public function scopeWithProgress(Builder $query): void
    {
        $query->withCount([
            'tasks',
            'tasks as done_tasks_count' => fn (Builder $q) => $q->where('status', TaskStatus::Done),
        ]);
    }

    public function progress(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        $done = $this->done_tasks_count ?? $this->tasks()->where('status', TaskStatus::Done)->count();

        return $total ? (int) round($done / $total * 100) : 0;
    }

    public function isCurrent(): bool
    {
        return now()->between($this->starts_on->startOfDay(), $this->ends_on->endOfDay());
    }

    /** Who the plan is for, e.g. "Jane Doe", "Sales team" or "Everyone". */
    public function audience(): string
    {
        return $this->owner?->name ?? $this->group?->name ?? 'Everyone';
    }
}
