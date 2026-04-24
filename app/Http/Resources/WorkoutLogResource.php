<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WorkoutLogResource extends BaseResource
{
    protected function data(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'workout_session_id' => $this->workout_session_id,
            'exercise_id'        => $this->exercise_id,
            'exercise'           => $this->whenLoaded('exercise', fn () => new ExerciseResource($this->exercise)),
            'set_number'         => $this->set_number,
            'weight'             => $this->weight,
            'reps'               => $this->reps,
            'duration_seconds'   => $this->duration_seconds,
            'distance_km'        => $this->distance_km,
            'rpe'                => $this->rpe,
            'set_type'           => $this->set_type,
            'created_at'         => $this->created_at?->toISOString(),
        ];
    }
}
