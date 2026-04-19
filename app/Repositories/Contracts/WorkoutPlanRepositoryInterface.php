<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface WorkoutPlanRepositoryInterface extends BaseRepositoryInterface
{
    public function allForUser(Request $request, User $user): LengthAwarePaginator;

    public function attachExercise(WorkoutPlan $plan, array $data): void;

    public function detachExercise(WorkoutPlan $plan, int $exerciseId): void;
}
