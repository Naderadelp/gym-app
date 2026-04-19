<?php

namespace App\Http\Requests\WorkoutLog;

use App\Http\Requests\BaseRequest;
use App\Models\WorkoutLog;

class StoreWorkoutLogRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', WorkoutLog::class);
    }

    public function rules(): array
    {
        return [
            'workout_plan_id'  => ['nullable', 'exists:workout_plans,id'],
            'exercise_id'      => ['required', 'exists:exercises,id'],
            'sets_done'        => ['required', 'integer', 'min:1'],
            'reps_done'        => ['nullable', 'integer', 'min:1'],
            'weight'           => ['nullable', 'numeric', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:1'],
            'notes'            => ['nullable', 'string'],
            'logged_at'        => ['nullable', 'date'],
        ];
    }
}
