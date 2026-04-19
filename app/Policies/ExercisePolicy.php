<?php

namespace App\Policies;

use App\Models\User;

class ExercisePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('index-exercise');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('show-exercise');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-exercise');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('edit-exercise');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('delete-exercise');
    }
}
