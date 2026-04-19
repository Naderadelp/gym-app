<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'mobile'   => ['required', 'string', 'unique:users,mobile'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'age'      => ['nullable', 'integer', 'min:10', 'max:100'],
            'height'   => ['nullable', 'numeric', 'min:50', 'max:300'],
            'weight'   => ['nullable', 'numeric', 'min:20', 'max:500'],
        ];
    }
}
