<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends BaseController
{
    public function show(): JsonResponse
    {
        $user = auth()->user()->load('media');
        $user->latestBodyMetric = $user->bodyMetrics()->latest('logged_at')->first();

        return $this->success(new UserResource($user));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $user->update($request->safe()->except('avatar'));

        return $this->success(new UserResource($user->fresh()->load('media')));
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'max:2048']]);

        $user = auth()->user();
        $user->clearMediaCollection('avatar');
        $user->addMediaFromRequest('avatar')
            ->usingFileName(md5(time()) . '.' . $request->file('avatar')->extension())
            ->toMediaCollection('avatar');

        return $this->success(new UserResource($user->fresh()->load('media')), 200, 'Avatar uploaded.');
    }
}
