<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $latestSession = $user->workoutSessions()
            ->with('routine')
            ->latest('started_at')
            ->first();

        $weeklyVolume = DB::table('workout_logs')
            ->join('workout_sessions', 'workout_logs.workout_session_id', '=', 'workout_sessions.id')
            ->where('workout_sessions.user_id', $user->id)
            ->where('workout_logs.set_type', '!=', 'warmup')
            ->whereNotNull('workout_logs.weight')
            ->whereNotNull('workout_logs.reps')
            ->whereRaw('YEARWEEK(workout_sessions.started_at, 1) = YEARWEEK(NOW(), 1)')
            ->sum(DB::raw('workout_logs.weight * workout_logs.reps'));

        return Inertia::render('Dashboard', [
            'latestSession' => $latestSession ? [
                'id'         => $latestSession->id,
                'started_at' => $latestSession->started_at?->toISOString(),
                'ended_at'   => $latestSession->ended_at?->toISOString(),
                'routine'    => $latestSession->routine?->name,
            ] : null,
            'weeklyVolume' => round((float) $weeklyVolume, 1),
        ]);
    }
}
