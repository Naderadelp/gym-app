<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkoutLog\StoreWorkoutLogRequest;
use App\Http\Resources\WorkoutLogResource;
use App\Models\WorkoutLog;
use App\Models\WorkoutSession;
use Illuminate\Http\JsonResponse;

class WorkoutLogController extends BaseController
{
    public function store(StoreWorkoutLogRequest $request, WorkoutSession $workoutSession): JsonResponse
    {
        $this->authorize('update', $workoutSession);

        $log = $workoutSession->workoutLogs()->create($request->validated());

        return $this->success(
            new WorkoutLogResource($log->load('exercise')),
            201,
            'Set logged.'
        );
    }

    public function destroy(WorkoutLog $workoutLog): JsonResponse
    {
        $this->authorize('delete', $workoutLog);
        $workoutLog->delete();

        return $this->success(null, 200, 'Set deleted.');
    }
}
