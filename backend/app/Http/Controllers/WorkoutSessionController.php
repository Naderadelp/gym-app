<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkoutSession\StoreWorkoutSessionRequest;
use App\Http\Resources\WorkoutSessionResource;
use App\Models\WorkoutSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutSessionController extends BaseController
{
    public function index(): JsonResponse
    {
        $paginator = WorkoutSession::where('user_id', auth()->id())
            ->latest('started_at')
            ->paginate(request()->integer('per_page', 15));

        return $this->paginated($paginator, WorkoutSessionResource::class);
    }

    public function store(StoreWorkoutSessionRequest $request): JsonResponse
    {
        $session = WorkoutSession::create([
            'user_id'    => auth()->id(),
            'routine_id' => $request->routine_id,
            'started_at' => $request->started_at ?? now(),
            'notes'      => $request->notes,
        ]);

        return $this->success(new WorkoutSessionResource($session), 201, 'Session started.');
    }

    public function show(WorkoutSession $workoutSession): JsonResponse
    {
        $this->authorize('view', $workoutSession);

        $workoutSession->load('workoutLogs.exercise', 'routine');

        $grouped = $workoutSession->workoutLogs
            ->groupBy('exercise_id')
            ->map(fn ($logs) => [
                'exercise' => $logs->first()->exercise,
                'sets'     => $logs->values(),
            ])
            ->values();

        return $this->success([
            'session'   => new WorkoutSessionResource($workoutSession),
            'exercises' => $grouped,
        ]);
    }

    public function update(Request $request, WorkoutSession $workoutSession): JsonResponse
    {
        $this->authorize('update', $workoutSession);
        $request->validate([
            'notes'      => ['nullable', 'string'],
            'routine_id' => ['nullable', 'integer', 'exists:routines,id'],
        ]);

        $workoutSession->update($request->only('notes', 'routine_id'));

        return $this->success(new WorkoutSessionResource($workoutSession->fresh()));
    }

    public function destroy(WorkoutSession $workoutSession): JsonResponse
    {
        $this->authorize('delete', $workoutSession);
        $workoutSession->delete();

        return $this->success(null, 200, 'Session deleted.');
    }

    public function finish(WorkoutSession $workoutSession): JsonResponse
    {
        $this->authorize('update', $workoutSession);
        $workoutSession->update(['ended_at' => now()]);

        return $this->success(new WorkoutSessionResource($workoutSession->fresh()), 200, 'Session finished.');
    }
}
