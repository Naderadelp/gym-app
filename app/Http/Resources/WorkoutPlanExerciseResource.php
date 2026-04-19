<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WorkoutPlanExerciseResource extends BaseResource
{
    protected function data(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'category'     => $this->category,
            'muscle_group' => $this->muscle_group,
            'cover'        => $this->whenLoaded('media', function () {
                $media = $this->getFirstMedia('cover');
                return $media ? new MediaResource($media) : null;
            }),
            'pivot'        => [
                'sets'             => $this->pivot->sets,
                'reps'             => $this->pivot->reps,
                'duration_seconds' => $this->pivot->duration_seconds,
                'rest_seconds'     => $this->pivot->rest_seconds,
                'notes'            => $this->pivot->notes,
                'order'            => $this->pivot->order,
            ],
        ];
    }
}
