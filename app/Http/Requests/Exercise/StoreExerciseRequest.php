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
            'name'         => ['required', 'string', 'max:255'],
            'category'     => ['required', 'string', 'max:100'],
            'muscle_group' => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string'],
            'image'        => ['nullable', 'image', 'max:2048'],
        ];
    }
}
