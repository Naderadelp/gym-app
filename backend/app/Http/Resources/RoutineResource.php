<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class RoutineResource extends BaseResource
{
    protected function data(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'description'      => $this->description,
            'exercises'        => $this->whenLoaded('routineExercises', function () {
                return $this->routineExercises->map(fn ($re) => [
                    'id'                  => $re->id,
                    'order'               => $re->order,
                    'target_sets'         => $re->target_sets,
                    'target_reps'         => $re->target_reps,
                    'target_rest_seconds' => $re->target_rest_seconds,
                    'exercise'            => new ExerciseResource($re->exercise),
                ]);
            }),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
