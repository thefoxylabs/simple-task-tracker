<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $user->canManage()
            || $task->assignee_id === $user->id
            || $task->created_by === $user->id;
    }

    /** Whoever set the task controls its details, deadline and reminder. */
    public function update(User $user, Task $task): bool
    {
        return $user->canManage() || $task->created_by === $user->id;
    }

    /** Assignees can move their tasks across the board. */
    public function updateStatus(User $user, Task $task): bool
    {
        return $this->update($user, $task) || $task->assignee_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }
}
