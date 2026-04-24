<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutLog;

class WorkoutLogPolicy
{
    public function delete(User $user, WorkoutLog $log): bool
    {
        return $log->workoutSession->user_id === $user->id;
    }
}
