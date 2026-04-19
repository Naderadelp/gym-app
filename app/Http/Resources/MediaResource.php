<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @mixin Media */
class MediaResource extends BaseResource
{
    protected function data(Request $request): array
    {
        return [
            'id'              => $this->id,
            'collection_name' => $this->collection_name,
            'name'            => $this->name,
            'file_name'       => $this->file_name,
            'mime_type'       => $this->mime_type,
            'size'            => $this->size,
            'url'             => $this->getFullUrl(),
            'webp'            => $this->hasGeneratedConversion('webp') ? $this->getUrl('webp') : null,
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
