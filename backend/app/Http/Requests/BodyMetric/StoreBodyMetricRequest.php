<?php

namespace App\Http\Requests\BodyMetric;

use App\Http\Requests\BaseRequest;

class StoreBodyMetricRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'weight'              => ['nullable', 'numeric', 'min:0', 'max:500'],
            'height'              => ['nullable', 'numeric', 'min:0', 'max:300'],
            'body_fat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'logged_at'           => ['required', 'date'],
        ];
    }
}
