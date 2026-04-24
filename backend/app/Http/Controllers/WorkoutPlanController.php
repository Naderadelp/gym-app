<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkoutPlan\AttachExerciseRequest;
use App\Http\Requests\WorkoutPlan\StoreWorkoutPlanRequest;
use App\Http\Requests\WorkoutPlan\UpdateWorkoutPlanRequest;
use App\Http\Resources\WorkoutPlanResource;
use App\Models\WorkoutPlan;
use App\Repositories\Contracts\WorkoutPlanRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutPlanController extends BaseController
{
    public function __construct(private WorkoutPlanRepositoryInterface $plans) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkoutPlan::class);

        return $this->paginated(
            $this->plans->allForUser($request, auth()->user()),
            WorkoutPlanResource::class
        );
    }

    public function store(StoreWorkoutPlanRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (auth()->user()->hasRole('trainer')) {
            $data['trainer_id'] = auth()->id();
        }

        $plan = $this->plans->create($data);

        return $this->success(
            new WorkoutPlanResource($plan->load(['trainer', 'member'])),
            201,
            'Workout plan created.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('view', WorkoutPlan::class);

        return $this->success(new WorkoutPlanResource($this->plans->find($id)));
    }

    public function update(UpdateWorkoutPlanRequest $request, int $id): JsonResponse
    {
        $plan = $this->plans->update($id, $request->validated());

        return $this->success(
            new WorkoutPlanResource($plan->load(['trainer', 'member'])),
            200,
            'Workout plan updated.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', WorkoutPlan::class);

        $this->plans->delete($id);

        return $this->success(null, 200, 'Workout plan deleted.');
    }

    public function attachExercise(AttachExerciseRequest $request, int $id): JsonResponse
    {
        $plan = $this->plans->find($id);
        $this->plans->attachExercise($plan, $request->validated());

        return $this->success(
            new WorkoutPlanResource($plan->load(['exercises', 'trainer', 'member'])),
            200,
            'Exercise attached.'
        );
    }

    public function detachExercise(int $planId, int $exerciseId): JsonResponse
    {
        $this->authorize('update', WorkoutPlan::class);

        $plan = $this->plans->find($planId);
        $this->plans->detachExercise($plan, $exerciseId);

        return $this->success(null, 200, 'Exercise detached.');
    }
}
