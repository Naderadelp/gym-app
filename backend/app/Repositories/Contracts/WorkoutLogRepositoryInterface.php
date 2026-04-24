<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface WorkoutLogRepositoryInterface extends BaseRepositoryInterface
{
    public function allForUser(Request $request, User $user): LengthAwarePaginator;

    public function allForMember(Request $request, int $memberId): LengthAwarePaginator;

    public function progressSummary(int $memberId): array;
}
