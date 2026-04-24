<?php

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;

class ExercisePolicy
{
    public function view(User $user, Exercise $exercise): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Exercise $exercise): bool
    {
        return ! is_null($exercise->user_id) && $exercise->user_id === $user->id;
    }

    public function delete(User $user, Exercise $exercise): bool
    {
        return ! is_null($exercise->user_id) && $exercise->user_id === $user->id;
    }
}
