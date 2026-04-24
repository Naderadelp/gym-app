<?php

namespace App\Http\Requests\WorkoutLog;

use App\Http\Requests\BaseRequest;

class StoreWorkoutLogRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'exercise_id'      => ['required', 'integer', 'exists:exercises,id'],
            'set_number'       => ['required', 'integer', 'min:1'],
            'weight'           => ['nullable', 'numeric', 'min:0'],
            'reps'             => ['nullable', 'integer', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'distance_km'      => ['nullable', 'numeric', 'min:0'],
            'rpe'              => ['nullable', 'integer', 'between:1,10'],
            'set_type'         => ['nullable', 'in:warmup,normal,drop,failure'],
        ];
    }
}
