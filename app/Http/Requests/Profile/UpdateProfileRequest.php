<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseRequest;

class UpdateProfileRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'display_name'    => ['nullable', 'string', 'max:255'],
            'unit_preference' => ['nullable', 'in:metric,imperial'],
            'avatar'          => ['nullable', 'image', 'max:2048'],
        ];
    }
}
