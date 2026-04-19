<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WorkoutPlanResource extends BaseResource
{
    protected function data(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
            'start_date'  => $this->start_date?->toDateString(),
            'end_date'    => $this->end_date?->toDateString(),
            'trainer'     => new UserResource($this->whenLoaded('trainer')),
            'member'      => new UserResource($this->whenLoaded('member')),
            'exercises'   => WorkoutPlanExerciseResource::collection($this->whenLoaded('exercises')),
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
