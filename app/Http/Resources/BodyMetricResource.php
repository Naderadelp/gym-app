<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class BodyMetricResource extends BaseResource
{
    protected function data(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'weight'              => $this->weight,
            'height'              => $this->height,
            'body_fat_percentage' => $this->body_fat_percentage,
            'logged_at'           => $this->logged_at?->toDateString(),
            'created_at'          => $this->created_at?->toISOString(),
        ];
    }
}
