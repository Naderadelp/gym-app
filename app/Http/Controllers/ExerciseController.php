<?php

namespace App\Http\Controllers;

use App\Http\Requests\Exercise\StoreExerciseRequest;
use App\Http\Requests\Exercise\UpdateExerciseRequest;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExerciseController extends BaseController
{
    public function index(): JsonResponse
    {
        $paginator = QueryBuilder::for(Exercise::availableTo(auth()->id()))
            ->allowedFilters([
                AllowedFilter::exact('primary_muscle'),
                AllowedFilter::exact('difficulty_level'),
                AllowedFilter::partial('equipment_required'),
                AllowedFilter::partial('name'),
            ])
            ->allowedSorts(['name', 'difficulty_level', 'created_at'])
            ->defaultSort('name')
            ->with('media')
            ->paginate(request()->integer('per_page', 15))
            ->withQueryString();

        return $this->paginated($paginator, ExerciseResource::class);
    }

    public function store(StoreExerciseRequest $request): JsonResponse
    {
        $exercise = Exercise::create(
            array_merge($request->safe()->except('demonstration'), ['user_id' => auth()->id()])
        );

        if ($request->hasFile('demonstration')) {
            $exercise->addMediaFromRequest('demonstration')
                ->usingFileName(md5(time()) . '.' . $request->file('demonstration')->extension())
                ->toMediaCollection('demonstration');
        }

        return $this->success(new ExerciseResource($exercise->load('media')), 201, 'Exercise created.');
    }

    public function show(Exercise $exercise): JsonResponse
    {
        $this->authorize('view', $exercise);

        return $this->success(new ExerciseResource($exercise->load('media')));
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('update', $exercise);
        $exercise->update($request->safe()->except('demonstration'));

        if ($request->hasFile('demonstration')) {
            $exercise->clearMediaCollection('demonstration');
            $exercise->addMediaFromRequest('demonstration')
                ->usingFileName(md5(time()) . '.' . $request->file('demonstration')->extension())
                ->toMediaCollection('demonstration');
        }

        return $this->success(new ExerciseResource($exercise->fresh()->load('media')));
    }

    public function destroy(Exercise $exercise): JsonResponse
    {
        $this->authorize('delete', $exercise);
        $exercise->delete();

        return $this->success(null, 200, 'Exercise deleted.');
    }
}
