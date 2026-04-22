<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email'         => ['required', 'email', 'exists:users,email'],
            'token'         => ['sometimes', 'required_with:old_password,password', 'string'],
            'old_password'  => ['required_with:token', 'string'],
            'password'      => ['required_with:token', 'confirmed', Password::min(8)],
        ];
    }
}
