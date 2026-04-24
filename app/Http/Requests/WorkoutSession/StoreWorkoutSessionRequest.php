<?php

namespace App\Http\Requests\WorkoutSession;

use App\Http\Requests\BaseRequest;

class StoreWorkoutSessionRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'routine_id' => ['nullable', 'integer', 'exists:routines,id'],
            'started_at' => ['nullable', 'date'],
            'notes'      => ['nullable', 'string'],
        ];
    }
}
