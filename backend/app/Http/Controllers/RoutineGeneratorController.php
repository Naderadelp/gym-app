<?php

namespace App\Http\Controllers;

use App\Http\Requests\Routine\GenerateRoutineRequest;
use App\Http\Resources\RoutineResource;
use App\Models\Exercise;
use App\Models\Routine;
use App\Models\RoutineExercise;
use Illuminate\Http\JsonResponse;

class RoutineGeneratorController extends BaseController
{
    public function generate(GenerateRoutineRequest $request): JsonResponse
    {
        $exercises = Exercise::availableTo(auth()->id())
            ->where('primary_muscle', $request->primary_muscle)
            ->where('difficulty_level', '<=', $request->difficulty_level)
            ->get()
            ->groupBy('sub_muscle_target');

        if ($exercises->isEmpty()) {
            return $this->error('No exercises found for the given muscle group and difficulty.', 422);
        }

        $routine = Routine::create([
            'user_id'     => auth()->id(),
            'name'        => ucfirst($request->primary_muscle) . ' Workout (Auto-generated)',
            'description' => "Generated for {$request->primary_muscle}, difficulty ≤ {$request->difficulty_level}",
        ]);

        $rows = [];
        $order = 1;
        foreach ($exercises as $subMuscle => $group) {
            $exercise = $group->random();
            $rows[] = [
                'routine_id'          => $routine->id,
                'exercise_id'         => $exercise->id,
                'order'               => $order++,
                'target_sets'         => 3,
                'target_reps'         => 10,
                'target_rest_seconds' => 60,
            ];
        }

        RoutineExercise::insert($rows);

        return $this->success(
            new RoutineResource($routine->load('routineExercises.exercise')),
            201,
            'Routine generated.'
        );
    }
}
