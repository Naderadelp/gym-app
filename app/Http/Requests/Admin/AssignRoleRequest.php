<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;
use Spatie\Permission\Models\Role;

class AssignRoleRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:' . Role::pluck('name')->implode(',')],
        ];
    }
}
