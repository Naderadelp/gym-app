<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends BaseController
{
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        if (! $request->filled('token')) {
            $status = Password::sendResetLink($request->only('email'));

            if ($status !== Password::RESET_LINK_SENT) {
                return $this->error(__($status), 422);
            }

            return $this->success(null, 200, __($status));
        }

        $user = User::where('email', $request->email)->first();

        if (! Hash::check($request->old_password, $user->password)) {
            return $this->error('Old password is incorrect.', 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error(__($status), 422);
        }

        return $this->success(null, 200, __($status));
    }
}
