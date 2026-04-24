<?php

namespace App\Http\Requests\WorkoutPlan;

use App\Http\Requests\BaseRequest;
use App\Models\WorkoutPlan;
use Illuminate\Validation\Rule;

class UpdateWorkoutPlanRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', WorkoutPlan::class);
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date'  => ['sometimes', 'date'],
            'end_date'    => ['sometimes', 'date', 'after_or_equal:start_date'],
            'status'      => ['sometimes', Rule::in(['active', 'completed', 'paused'])],
        ];
    }
}
