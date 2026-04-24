<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WorkoutLog;
use App\Models\WorkoutSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkoutLogController extends Controller
{
    public function store(Request $request, WorkoutSession $session): RedirectResponse
    {
        $this->authorize('update', $session);

        $validated = $request->validate([
            'exercise_id'      => ['required', 'integer', 'exists:exercises,id'],
            'set_number'       => ['required', 'integer', 'min:1'],
            'weight'           => ['nullable', 'numeric', 'min:0'],
            'reps'             => ['nullable', 'integer', 'min:0'],
            'set_type'         => ['required', 'in:warmup,normal,drop,failure'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'distance_km'      => ['nullable', 'numeric', 'min:0'],
            'rpe'              => ['nullable', 'integer', 'between:1,10'],
        ]);

        $session->workoutLogs()->create($validated);

        return redirect()->back();
    }

    public function destroy(WorkoutLog $log): RedirectResponse
    {
        $this->authorize('delete', $log);

        $log->delete();

        return redirect()->back();
    }
}
