<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /** Staff can see projects they lead or have tasks in. */
    public function view(User $user, Project $project): bool
    {
        return $user->canManage()
            || $project->lead_id === $user->id
            || $project->tasks()->visibleTo($user)->exists();
    }
}
