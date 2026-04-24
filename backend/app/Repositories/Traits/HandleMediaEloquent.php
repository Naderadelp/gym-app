<?php

namespace App\Repositories\Traits;

use Spatie\MediaLibrary\HasMedia;

trait HandleMediaEloquent
{
    public function createWithMedia(array $data, string $requestKey, string $collection = 'default'): HasMedia
    {
        $model = $this->create($data);

        if (request()->hasFile($requestKey)) {
            $model->addMediaFromRequest($requestKey)
                ->usingFileName(md5(time()) . '.' . request()->file($requestKey)->extension())
                ->toMediaCollection($collection);
        }

        return $model->load('media');
    }

    public function updateWithMedia(int $id, array $data, string $requestKey, string $collection = 'default', bool $sync = false): HasMedia
    {
        $model = $this->update($id, $data);

        if (request()->hasFile($requestKey)) {
            if ($sync) {
                $model->clearMediaCollection($collection);
            }

            $model->addMediaFromRequest($requestKey)
                ->usingFileName(md5(time()) . '.' . request()->file($requestKey)->extension())
                ->toMediaCollection($collection);
        }

        return $model->load('media');
    }
}
