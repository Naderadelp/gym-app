<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected array $allowedFilters = [];

    protected array $allowedFiltersExact = [];

    protected array $allowedFilterScopes = [];

    protected array $allowedSorts = [];

    protected array $allowedDefaultSorts = ['-id'];

    protected array $allowedIncludes = [];

    abstract public function model(): string;

    public function all(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for($this->model())
            ->allowedFilters(...$this->buildFilters())
            ->allowedSorts(...$this->allowedSorts)
            ->allowedIncludes(...$this->allowedIncludes)
            ->defaultSort(...$this->allowedDefaultSorts)
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();
    }

    public function find(int $id): Model
    {
        return QueryBuilder::for($this->model())
            ->allowedIncludes(...$this->allowedIncludes)
            ->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model()::create($data);
    }

    public function update(int $id, array $data): Model
    {
        $model = $this->model()::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model()::findOrFail($id)->delete();
    }

    protected function buildFilters(): array
    {
        $partial = array_map(fn ($f) => AllowedFilter::partial($f), $this->allowedFilters);
        $exact   = array_map(fn ($f) => AllowedFilter::exact($f), $this->allowedFiltersExact);
        $scopes  = array_map(fn ($f) => AllowedFilter::scope($f), $this->allowedFilterScopes);

        return array_merge($partial, $exact, $scopes);
    }
}
