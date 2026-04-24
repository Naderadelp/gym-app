<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WorkoutLog;
use App\Models\WorkoutSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(): Response
    {
        $userId = auth()->id();

        $volumeRows = DB::table('workout_logs')
            ->join('workout_sessions', 'workout_logs.workout_session_id', '=', 'workout_sessions.id')
            ->where('workout_sessions.user_id', $userId)
            ->where('workout_logs.set_type', '!=', 'warmup')
            ->whereNotNull('workout_sessions.ended_at')
            ->where('workout_sessions.started_at', '>=', now()->subWeeks(12)->startOfWeek())
            ->selectRaw('YEARWEEK(workout_sessions.started_at, 1) as yw, SUM(workout_logs.weight * workout_logs.reps) as volume')
            ->groupBy('yw')
            ->orderBy('yw')
            ->pluck('volume', 'yw')
            ->toArray();

        $weeks = $this->last12Weeks();
        $volumeData = array_map(fn ($yw) => (float) ($volumeRows[$yw] ?? 0), array_keys($weeks));
        $weekLabels = array_values($weeks);

        $prs = DB::table('workout_logs')
            ->join('workout_sessions', 'workout_logs.workout_session_id', '=', 'workout_sessions.id')
            ->join('exercises', 'workout_logs.exercise_id', '=', 'exercises.id')
            ->where('workout_sessions.user_id', $userId)
            ->where('workout_logs.set_type', '!=', 'warmup')
            ->whereNotNull('workout_logs.weight')
            ->selectRaw('exercises.id, exercises.name, MAX(workout_logs.weight) as max_weight, MAX(workout_logs.reps) as best_reps')
            ->groupBy('exercises.id', 'exercises.name')
            ->orderByDesc('max_weight')
            ->get()
            ->map(fn ($row) => [
                'exercise'   => $row->name,
                'max_weight' => (float) $row->max_weight,
                'best_reps'  => (int) $row->best_reps,
            ])
            ->toArray();

        return Inertia::render('Analytics/Index', [
            'weekLabels'  => $weekLabels,
            'volumeData'  => $volumeData,
            'personalRecords' => $prs,
        ]);
    }

    private function last12Weeks(): array
    {
        $weeks = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subWeeks($i)->startOfWeek();
            $yw = (int) $date->format('oW');
            $weeks[$yw] = $date->format('M d');
        }
        return $weeks;
    }
}
