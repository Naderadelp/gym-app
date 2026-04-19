<?php

namespace App\Policies;

use App\Models\User;

class WorkoutPlanPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('index-workout-plan');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('show-workout-plan');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-workout-plan');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('edit-workout-plan');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('delete-workout-plan');
    }
}
