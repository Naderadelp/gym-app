<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ExerciseResource extends BaseResource
{
    protected function data(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'description'  => $this->description,
            'category'     => $this->category,
            'muscle_group' => $this->muscle_group,
            'cover'        => $this->whenLoaded('media', function () {
                $media = $this->getFirstMedia('cover');
                return $media ? new MediaResource($media) : null;
            }),
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
