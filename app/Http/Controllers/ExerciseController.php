<?php

namespace App\Http\Controllers;

use App\Http\Requests\Exercise\StoreExerciseRequest;
use App\Http\Requests\Exercise\UpdateExerciseRequest;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use App\Repositories\Contracts\ExerciseRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ExerciseController extends BaseController
{
    public function __construct(private ExerciseRepositoryInterface $exercises) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Exercise::class);

        return $this->paginated($this->exercises->all($request), ExerciseResource::class);
    }

    public function store(StoreExerciseRequest $request): JsonResponse
    {
        $exercise = $this->exercises->createWithMedia(
            $request->safe()->except('image'),
            'image',
            'cover'
        );

        return $this->success(new ExerciseResource($exercise), 201, 'Exercise created.');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorize('view', Exercise::class);

        return $this->success(new ExerciseResource($this->exercises->find($id)));
    }

    public function update(UpdateExerciseRequest $request, int $id): JsonResponse
    {
        $exercise = $this->exercises->updateWithMedia(
            $id,
            $request->safe()->except('image'),
            'image',
            'cover',
            sync: true
        );

        return $this->success(new ExerciseResource($exercise), 200, 'Exercise updated.');
    }

    public function destroy(int $id): JsonResponse
    {
        $exercise = $this->exercises->find($id);
        $this->authorize('delete', $exercise);

        $this->exercises->delete($id);

        return $this->success(null, 200, 'Exercise deleted.');
    }
}
