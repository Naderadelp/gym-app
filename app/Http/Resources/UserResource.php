<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    protected function data(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'display_name'    => $this->display_name,
            'unit_preference' => $this->unit_preference,
            'email'           => $this->email,
            'roles'           => $this->whenLoaded('roles', fn () => $this->getRoleNames()),
            'avatar'          => $this->whenLoaded('media', function () {
                $media = $this->getFirstMedia('avatar');
                return $media ? new MediaResource($media) : null;
            }),
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
