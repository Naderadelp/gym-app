<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkoutLog\StoreWorkoutLogRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\WorkoutLogResource;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Repositories\Contracts\WorkoutLogRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutLogController extends BaseController
{
    public function __construct(private WorkoutLogRepositoryInterface $logs) {}

    public function myLogs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkoutLog::class);

        return $this->paginated(
            $this->logs->allForUser($request, auth()->user()),
            WorkoutLogResource::class
        );
    }

    public function store(StoreWorkoutLogRequest $request): JsonResponse
    {
        $data              = $request->validated();
        $data['member_id'] = auth()->id();
        $data['logged_at'] ??= now();

        $log = $this->logs->create($data);

        return $this->success(
            new WorkoutLogResource($log->load('exercise')),
            201,
            'Workout logged.'
        );
    }

    public function memberLogs(Request $request, int $memberId): JsonResponse
    {
        abort_if(
            auth()->user()->hasRole('member'),
            403,
            'You cannot view other members\' logs.'
        );

        User::findOrFail($memberId);

        return $this->paginated(
            $this->logs->allForMember($request, $memberId),
            WorkoutLogResource::class
        );
    }

    public function memberProgress(int $memberId): JsonResponse
    {
        abort_if(
            auth()->user()->hasRole('member'),
            403,
            'You cannot view other members\' progress.'
        );

        $member = User::findOrFail($memberId);

        return $this->success([
            'member'  => new UserResource($member),
            'summary' => $this->logs->progressSummary($memberId),
        ]);
    }
}
