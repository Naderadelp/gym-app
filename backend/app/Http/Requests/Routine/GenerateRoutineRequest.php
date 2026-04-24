<?php

namespace App\Http\Requests\Routine;

use App\Http\Requests\BaseRequest;

class GenerateRoutineRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'primary_muscle'  => ['required', 'string'],
            'difficulty_level' => ['required', 'integer', 'in:1,2,3'],
        ];
    }
}
