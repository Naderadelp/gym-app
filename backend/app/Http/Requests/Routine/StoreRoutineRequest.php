<?php

namespace App\Http\Requests\Routine;

use App\Http\Requests\BaseRequest;

class StoreRoutineRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name'                              => ['required', 'string', 'max:255'],
            'description'                       => ['nullable', 'string'],
            'exercises'                         => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id'           => ['required', 'integer', 'exists:exercises,id'],
            'exercises.*.order'                 => ['required', 'integer', 'min:1'],
            'exercises.*.target_sets'           => ['required', 'integer', 'min:1'],
            'exercises.*.target_reps'           => ['nullable', 'integer', 'min:1'],
            'exercises.*.target_rest_seconds'   => ['nullable', 'integer', 'min:0'],
        ];
    }
}
