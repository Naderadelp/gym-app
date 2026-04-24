<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ExerciseResource extends BaseResource
{
    protected function data(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'user_id'            => $this->user_id,
            'name'               => $this->name,
            'description'        => $this->description,
            'primary_muscle'     => $this->primary_muscle,
            'sub_muscle_target'  => $this->sub_muscle_target,
            'difficulty_level'   => $this->difficulty_level,
            'equipment_required' => $this->equipment_required,
            'demonstration'      => $this->whenLoaded('media', function () {
                $media = $this->getFirstMedia('demonstration');
                return $media ? new MediaResource($media) : null;
            }),
            'created_at'         => $this->created_at?->toISOString(),
        ];
    }
}
