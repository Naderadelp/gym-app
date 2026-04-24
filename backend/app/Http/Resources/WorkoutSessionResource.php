<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WorkoutSessionResource extends BaseResource
{
    protected function data(Request $request): array
    {
        return [
            'id'          => $this->id,
            'routine_id'  => $this->routine_id,
            'routine'     => $this->whenLoaded('routine', fn () => new RoutineResource($this->routine)),
            'started_at'  => $this->started_at?->toISOString(),
            'ended_at'    => $this->ended_at?->toISOString(),
            'notes'       => $this->notes,
            'logs'        => $this->whenLoaded('workoutLogs', fn () => WorkoutLogResource::collection($this->workoutLogs)),
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
