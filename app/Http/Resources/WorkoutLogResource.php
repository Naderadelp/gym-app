<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WorkoutLogResource extends BaseResource
{
    protected function data(Request $request): array
    {
        return [
            'id'               => $this->id,
            'sets_done'        => $this->sets_done,
            'reps_done'        => $this->reps_done,
            'weight'           => $this->weight,
            'duration_seconds' => $this->duration_seconds,
            'notes'            => $this->notes,
            'logged_at'        => $this->logged_at?->toISOString(),
            'exercise'         => new ExerciseResource($this->whenLoaded('exercise')),
            'workout_plan'     => new WorkoutPlanResource($this->whenLoaded('workoutPlan')),
            'member'           => new UserResource($this->whenLoaded('member')),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
