<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BodyMetric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BodyMetricController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'logged_at'           => ['required', 'date'],
            'weight'              => ['nullable', 'numeric', 'min:0', 'max:500'],
            'height'              => ['nullable', 'numeric', 'min:0', 'max:300'],
            'body_fat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $validated['user_id'] = auth()->id();

        BodyMetric::updateOrCreate(
            ['user_id' => auth()->id(), 'logged_at' => $validated['logged_at']],
            $validated
        );

        return back()->with('success', 'Body metric logged.');
    }
}
