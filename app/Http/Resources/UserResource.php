<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    protected function data(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'mobile'     => $this->mobile,
            'age'        => $this->age,
            'height'     => $this->height,
            'weight'     => $this->weight,
            'roles'      => $this->whenLoaded('roles', fn () => $this->getRoleNames()),
            'avatar'     => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
