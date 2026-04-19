<?php

namespace App\Repositories\Eloquent;

use App\Models\Exercise;
use App\Repositories\Contracts\ExerciseRepositoryInterface;
use App\Repositories\Traits\HandleMediaEloquent;

class ExerciseRepository extends BaseRepository implements ExerciseRepositoryInterface
{
    use HandleMediaEloquent;

    protected array $allowedFilters      = ['name', 'description'];
    protected array $allowedFiltersExact = ['category', 'muscle_group'];
    protected array $allowedSorts        = ['id', 'name', 'category', 'muscle_group', 'created_at'];
    protected array $allowedDefaultSorts = ['-created_at'];
    protected array $allowedIncludes     = ['media'];

    public function model(): string
    {
        return Exercise::class;
    }
}
