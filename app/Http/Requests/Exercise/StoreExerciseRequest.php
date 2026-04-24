<?php

namespace App\Http\Requests\Exercise;

use App\Http\Requests\BaseRequest;
use App\Models\Exercise;

class StoreExerciseRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Exercise::class);
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'primary_muscle'     => ['required', 'string', 'max:100'],
            'sub_muscle_target'  => ['required', 'string', 'max:100'],
            'difficulty_level'   => ['required', 'integer', 'min:1', 'max:5'],
            'equipment_required' => ['nullable', 'string', 'max:100'],
            'demonstration'      => ['nullable', 'image', 'max:4096'],
        ];
    }
}
