<?php

namespace App\Http\Requests\Routine;

use App\Http\Requests\BaseRequest;

class UpdateRoutineRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name'                              => ['sometimes', 'string', 'max:255'],
            'description'                       => ['nullable', 'string'],
            'exercises'                         => ['sometimes', 'array', 'min:1'],
            'exercises.*.exercise_id'           => ['required_with:exercises', 'integer', 'exists:exercises,id'],
            'exercises.*.order'                 => ['required_with:exercises', 'integer', 'min:1'],
            'exercises.*.target_sets'           => ['required_with:exercises', 'integer', 'min:1'],
            'exercises.*.target_reps'           => ['nullable', 'integer', 'min:1'],
            'exercises.*.target_rest_seconds'   => ['nullable', 'integer', 'min:0'],
        ];
    }
}
