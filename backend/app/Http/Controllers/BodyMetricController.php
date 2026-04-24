<?php

namespace App\Http\Controllers;

use App\Http\Requests\BodyMetric\StoreBodyMetricRequest;
use App\Http\Resources\BodyMetricResource;
use App\Models\BodyMetric;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;

class BodyMetricController extends BaseController
{
    public function index(): JsonResponse
    {
        $paginator = QueryBuilder::for(BodyMetric::where('user_id', auth()->id()))
            ->allowedSorts(['logged_at', 'created_at'])
            ->defaultSort('-logged_at')
            ->paginate(request()->integer('per_page', 15))
            ->withQueryString();

        return $this->paginated($paginator, BodyMetricResource::class);
    }

    public function store(StoreBodyMetricRequest $request): JsonResponse
    {
        $metric = BodyMetric::updateOrCreate(
            ['user_id' => auth()->id(), 'logged_at' => $request->logged_at],
            $request->validated()
        );

        return $this->success(new BodyMetricResource($metric), 201, 'Body metric logged.');
    }

    public function destroy(BodyMetric $bodyMetric): JsonResponse
    {
        abort_unless($bodyMetric->user_id === auth()->id(), 403);
        $bodyMetric->delete();

        return $this->success(null, 200, 'Body metric deleted.');
    }
}
