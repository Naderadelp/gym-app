<?php

namespace App\Http\Requests\WorkoutPlan;

use App\Http\Requests\BaseRequest;
use App\Models\WorkoutPlan;
use Illuminate\Validation\Rule;

class StoreWorkoutPlanRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', WorkoutPlan::class);
    }

    public function rules(): array
    {
        $isAdmin = $this->user()->hasRole('admin');

        return [
            'trainer_id'  => $isAdmin ? ['required', 'exists:users,id'] : ['prohibited'],
            'member_id'   => ['required', 'exists:users,id'],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'status'      => ['required', Rule::in(['active', 'completed', 'paused'])],
        ];
    }
}
