<?php

namespace App\Http\Requests\Exercise;

use App\Http\Requests\BaseRequest;
use App\Models\Exercise;

class UpdateExerciseRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', Exercise::class);
    }

    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'string', 'max:255'],
            'category'     => ['sometimes', 'string', 'max:100'],
            'muscle_group' => ['sometimes', 'string', 'max:100'],
            'description'  => ['nullable', 'string'],
            'image'        => ['nullable', 'image', 'max:2048'],
        ];
    }
}
