<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\WorkoutPlan;
use App\Repositories\Contracts\WorkoutPlanRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;

class WorkoutPlanRepository extends BaseRepository implements WorkoutPlanRepositoryInterface
{
    protected array $allowedFilters      = ['name', 'description'];
    protected array $allowedFiltersExact = ['status', 'trainer_id', 'member_id'];
    protected array $allowedSorts        = ['id', 'name', 'start_date', 'end_date', 'status', 'created_at'];
    protected array $allowedDefaultSorts = ['-created_at'];
    protected array $allowedIncludes     = ['trainer', 'member', 'exercises', 'exercises.media'];

    public function model(): string
    {
        return WorkoutPlan::class;
    }

    public function allForUser(Request $request, User $user): LengthAwarePaginator
    {
        $base = WorkoutPlan::query();

        if ($user->hasRole('trainer')) {
            $base->where('trainer_id', $user->id);
        } elseif ($user->hasRole('member')) {
            $base->where('member_id', $user->id);
        }

        return QueryBuilder::for($base)
            ->allowedFilters(...$this->buildFilters())
            ->allowedSorts(...$this->allowedSorts)
            ->allowedIncludes(...$this->allowedIncludes)
            ->defaultSort(...$this->allowedDefaultSorts)
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();
    }

    public function attachExercise(WorkoutPlan $plan, array $data): void
    {
        $plan->exercises()->attach($data['exercise_id'], [
            'sets'             => $data['sets'],
            'reps'             => $data['reps'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'rest_seconds'     => $data['rest_seconds'],
            'notes'            => $data['notes'] ?? null,
            'order'            => $data['order'] ?? $plan->exercises()->count(),
        ]);
    }

    public function detachExercise(WorkoutPlan $plan, int $exerciseId): void
    {
        $plan->exercises()->detach($exerciseId);
    }
}
