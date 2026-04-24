<?php

namespace App\Http\Requests\Exercise;

use App\Http\Requests\BaseRequest;
use App\Models\Exercise;

class UpdateExerciseRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('exercise'));
    }

    public function rules(): array
    {
        return [
            'name'               => ['sometimes', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'primary_muscle'     => ['sometimes', 'string', 'max:100'],
            'sub_muscle_target'  => ['sometimes', 'string', 'max:100'],
            'difficulty_level'   => ['sometimes', 'integer', 'min:1', 'max:5'],
            'equipment_required' => ['nullable', 'string', 'max:100'],
            'demonstration'      => ['nullable', 'image', 'max:4096'],
        ];
    }
}
