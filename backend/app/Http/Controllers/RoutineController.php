<?php

namespace App\Http\Controllers;

use App\Http\Requests\Routine\StoreRoutineRequest;
use App\Http\Requests\Routine\UpdateRoutineRequest;
use App\Http\Resources\RoutineResource;
use App\Models\Routine;
use App\Models\RoutineExercise;
use Illuminate\Http\JsonResponse;

class RoutineController extends BaseController
{
    public function index(): JsonResponse
    {
        $paginator = auth()->user()->routines()
            ->paginate(request()->integer('per_page', 15));

        return $this->paginated($paginator, RoutineResource::class);
    }

    public function store(StoreRoutineRequest $request): JsonResponse
    {
        $routine = Routine::create(array_merge(
            $request->safe()->except('exercises'),
            ['user_id' => auth()->id()]
        ));

        $this->syncExercises($routine, $request->exercises);

        return $this->success(
            new RoutineResource($routine->load('routineExercises.exercise')),
            201,
            'Routine created.'
        );
    }

    public function show(Routine $routine): JsonResponse
    {
        $this->authorize('view', $routine);

        return $this->success(
            new RoutineResource($routine->load('routineExercises.exercise'))
        );
    }

    public function update(UpdateRoutineRequest $request, Routine $routine): JsonResponse
    {
        $this->authorize('update', $routine);
        $routine->update($request->safe()->except('exercises'));

        if ($request->has('exercises')) {
            $this->syncExercises($routine, $request->exercises);
        }

        return $this->success(
            new RoutineResource($routine->fresh()->load('routineExercises.exercise'))
        );
    }

    public function destroy(Routine $routine): JsonResponse
    {
        $this->authorize('delete', $routine);
        $routine->delete();

        return $this->success(null, 200, 'Routine deleted.');
    }

    private function syncExercises(Routine $routine, array $exercises): void
    {
        $routine->routineExercises()->delete();

        $rows = array_map(fn ($ex) => [
            'routine_id'          => $routine->id,
            'exercise_id'         => $ex['exercise_id'],
            'order'               => $ex['order'],
            'target_sets'         => $ex['target_sets'],
            'target_reps'         => $ex['target_reps'] ?? null,
            'target_rest_seconds' => $ex['target_rest_seconds'] ?? null,
        ], $exercises);

        RoutineExercise::insert($rows);
    }
}
