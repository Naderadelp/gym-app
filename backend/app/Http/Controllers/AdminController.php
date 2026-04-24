<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\AssignRoleRequest;
use App\Http\Resources\WorkoutLogResource;
use App\Models\Exercise;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Models\WorkoutPlan;
use Illuminate\Http\JsonResponse;

class AdminController extends BaseController
{
    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        abort_if(!auth()->user()->hasRole('admin'), 403, 'Access denied.');

        $user->syncRoles([$request->role]);

        return $this->success([
            'user'        => $user->only('id', 'name', 'email'),
            'role'        => $request->role,
            'permissions' => $user->getPermissionsViaRoles()->pluck('name'),
        ], message: 'Role assigned successfully.');
    }

    public function stats(): JsonResponse
    {
        abort_if(
            !auth()->user()->hasPermissionTo('view-admin-stats'),
            403,
            'Access denied.'
        );

        return $this->success([
            'total_members'   => User::role('member')->count(),
            'total_trainers'  => User::role('trainer')->count(),
            'total_exercises' => Exercise::count(),
            'active_plans'    => WorkoutPlan::where('status', 'active')->count(),
            'recent_logs'     => WorkoutLogResource::collection(
                WorkoutLog::with(['exercise', 'member'])
                    ->latest('logged_at')
                    ->limit(10)
                    ->get()
            ),
        ]);
    }
}
