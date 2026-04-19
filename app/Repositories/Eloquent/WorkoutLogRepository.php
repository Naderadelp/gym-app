<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\WorkoutLog;
use App\Repositories\Contracts\WorkoutLogRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;

class WorkoutLogRepository extends BaseRepository implements WorkoutLogRepositoryInterface
{
    protected array $allowedFiltersExact = ['exercise_id', 'workout_plan_id'];
    protected array $allowedSorts        = ['id', 'logged_at', 'created_at'];
    protected array $allowedDefaultSorts = ['-logged_at'];
    protected array $allowedIncludes     = ['exercise', 'exercise.media', 'workoutPlan', 'member'];

    public function model(): string
    {
        return WorkoutLog::class;
    }

    public function allForUser(Request $request, User $user): LengthAwarePaginator
    {
        return $this->scopedQuery($request, WorkoutLog::where('member_id', $user->id));
    }

    public function allForMember(Request $request, int $memberId): LengthAwarePaginator
    {
        return $this->scopedQuery($request, WorkoutLog::where('member_id', $memberId));
    }

    public function progressSummary(int $memberId): array
    {
        $logs = WorkoutLog::query()
            ->where('member_id', $memberId)
            ->with('exercise')
            ->get();

        $byExercise = $logs->groupBy('exercise_id')
            ->map(function ($group) {
                $exercise = $group->first()->exercise;

                return [
                    'exercise_id'   => $exercise?->id,
                    'exercise_name' => $exercise?->name,
                    'sessions'      => $group->count(),
                    'total_sets'    => $group->sum('sets_done'),
                    'total_reps'    => $group->sum('reps_done'),
                    'max_weight'    => $group->max('weight'),
                    'last_logged'   => $group->max('logged_at'),
                ];
            })
            ->values();

        return [
            'total_sessions'   => $logs->count(),
            'total_sets'       => $logs->sum('sets_done'),
            'total_reps'       => $logs->sum('reps_done'),
            'exercises_logged' => $byExercise->count(),
            'by_exercise'      => $byExercise,
        ];
    }

    private function scopedQuery(Request $request, $base): LengthAwarePaginator
    {
        return QueryBuilder::for($base)
            ->allowedFilters(...$this->buildFilters())
            ->allowedSorts(...$this->allowedSorts)
            ->allowedIncludes(...$this->allowedIncludes)
            ->defaultSort(...$this->allowedDefaultSorts)
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();
    }
}
