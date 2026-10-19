<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    /** Staff can see company-wide plans, their group's plans and their own. */
    public function view(User $user, Plan $plan): bool
    {
        return $user->canManage() || Plan::whereKey($plan->id)->visibleTo($user)->exists();
    }
}
