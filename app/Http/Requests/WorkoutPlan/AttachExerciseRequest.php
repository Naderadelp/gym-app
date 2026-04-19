<?php

namespace App\Http\Requests\WorkoutPlan;

use App\Http\Requests\BaseRequest;
use App\Models\WorkoutPlan;

class AttachExerciseRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', WorkoutPlan::class);
    }

    public function rules(): array
    {
        return [
            'exercise_id'      => ['required', 'exists:exercises,id'],
            'sets'             => ['required', 'integer', 'min:1'],
            'reps'             => ['nullable', 'integer', 'min:1'],
            'duration_seconds' => ['nullable', 'integer', 'min:1'],
            'rest_seconds'     => ['required', 'integer', 'min:0'],
            'notes'            => ['nullable', 'string'],
            'order'            => ['nullable', 'integer', 'min:0'],
        ];
    }
}
