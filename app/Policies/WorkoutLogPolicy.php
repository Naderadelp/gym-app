<?php

namespace App\Policies;

use App\Models\User;

class WorkoutLogPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('index-workout-log');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('show-workout-log');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-workout-log');
    }
}
