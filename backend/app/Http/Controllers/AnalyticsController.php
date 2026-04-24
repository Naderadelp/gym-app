<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends BaseController
{
    public function volume(Request $request): JsonResponse
    {
        $request->validate(['group_by' => ['nullable', 'in:week,session']]);
        $groupBy = $request->input('group_by', 'week');

        $query = DB::table('workout_logs')
            ->join('workout_sessions', 'workout_logs.workout_session_id', '=', 'workout_sessions.id')
            ->where('workout_sessions.user_id', auth()->id())
            ->where('workout_logs.set_type', '!=', 'warmup')
            ->whereNotNull('workout_logs.weight')
            ->whereNotNull('workout_logs.reps');

        if ($groupBy === 'week') {
            $results = $query->selectRaw('
                    YEARWEEK(workout_sessions.started_at, 1) as period,
                    MIN(DATE(workout_sessions.started_at)) as week_start,
                    ROUND(SUM(workout_logs.weight * workout_logs.reps), 2) as total_volume,
                    COUNT(DISTINCT workout_sessions.id) as session_count
                ')
                ->groupByRaw('YEARWEEK(workout_sessions.started_at, 1)')
                ->orderBy('period')
                ->get();
        } else {
            $results = $query->selectRaw('
                    workout_sessions.id as session_id,
                    workout_sessions.started_at,
                    ROUND(SUM(workout_logs.weight * workout_logs.reps), 2) as total_volume
                ')
                ->groupBy('workout_sessions.id', 'workout_sessions.started_at')
                ->orderBy('workout_sessions.started_at', 'desc')
                ->get();
        }

        return $this->success($results);
    }

    public function personalRecords(Request $request): JsonResponse
    {
        $request->validate(['exercise_id' => ['nullable', 'integer', 'exists:exercises,id']]);

        $query = DB::table('workout_logs')
            ->join('workout_sessions', 'workout_logs.workout_session_id', '=', 'workout_sessions.id')
            ->join('exercises', 'workout_logs.exercise_id', '=', 'exercises.id')
            ->where('workout_sessions.user_id', auth()->id())
            ->where('workout_logs.set_type', '!=', 'warmup')
            ->whereNotNull('workout_logs.weight')
            ->selectRaw('
                exercises.id as exercise_id,
                exercises.name as exercise_name,
                MAX(workout_logs.weight) as max_weight,
                MAX(workout_sessions.started_at) as achieved_at
            ')
            ->groupBy('exercises.id', 'exercises.name');

        if ($request->exercise_id) {
            $query->where('workout_logs.exercise_id', $request->exercise_id);
        }

        return $this->success($query->orderBy('exercises.name')->get());
    }
}
