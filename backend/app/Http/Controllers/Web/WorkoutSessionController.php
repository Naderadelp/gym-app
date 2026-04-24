<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WorkoutSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutSessionController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $session = WorkoutSession::create([
            'user_id'    => auth()->id(),
            'routine_id' => $request->input('routine_id'),
            'started_at' => now(),
        ]);

        return redirect()->route('workouts.show', $session);
    }

    public function show(WorkoutSession $session): Response
    {
        $this->authorize('view', $session);

        $session->load([
            'routine.routineExercises.exercise',
            'workoutLogs.exercise',
        ]);

        $logsByExercise = $session->workoutLogs
            ->groupBy('exercise_id')
            ->map(fn ($logs) => $logs->sortBy('set_number')->values())
            ->toArray();

        return Inertia::render('Workouts/ActiveSession', [
            'session'         => [
                'id'         => $session->id,
                'started_at' => $session->started_at->toISOString(),
                'ended_at'   => $session->ended_at?->toISOString(),
                'notes'      => $session->notes,
            ],
            'routine'         => $session->routine ? [
                'id'   => $session->routine->id,
                'name' => $session->routine->name,
                'exercises' => $session->routine->routineExercises->map(fn ($re) => [
                    'exercise_id'         => $re->exercise_id,
                    'name'                => $re->exercise->name,
                    'primary_muscle'      => $re->exercise->primary_muscle,
                    'order'               => $re->order,
                    'target_sets'         => $re->target_sets,
                    'target_reps'         => $re->target_reps,
                    'target_rest_seconds' => $re->target_rest_seconds,
                ]),
            ] : null,
            'logsByExercise'  => $logsByExercise,
            'allLogs'         => $session->workoutLogs->map(fn ($log) => [
                'id'                 => $log->id,
                'exercise_id'        => $log->exercise_id,
                'exercise_name'      => $log->exercise->name,
                'set_number'         => $log->set_number,
                'weight'             => $log->weight,
                'reps'               => $log->reps,
                'set_type'           => $log->set_type,
                'duration_seconds'   => $log->duration_seconds,
                'distance_km'        => $log->distance_km,
                'rpe'                => $log->rpe,
            ])->values(),
        ]);
    }

    public function finish(WorkoutSession $session): RedirectResponse
    {
        $this->authorize('update', $session);

        $session->update(['ended_at' => now()]);

        return redirect()->route('dashboard')->with('success', 'Workout finished!');
    }
}
