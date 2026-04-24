<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\Routine;
use App\Models\RoutineExercise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoutineController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Routines/Index', [
            'routines' => auth()->user()
                ->routines()
                ->withCount('routineExercises')
                ->latest()
                ->get()
                ->map(fn ($r) => [
                    'id'                     => $r->id,
                    'name'                   => $r->name,
                    'description'            => $r->description,
                    'routine_exercises_count' => $r->routine_exercises_count,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Routines/Builder', [
            'availableExercises' => Exercise::availableTo(auth()->id())
                ->get(['id', 'name', 'primary_muscle']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                            => ['required', 'string', 'max:255'],
            'description'                     => ['nullable', 'string'],
            'exercises'                       => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id'         => ['required', 'integer', 'exists:exercises,id'],
            'exercises.*.order'               => ['required', 'integer', 'min:1'],
            'exercises.*.target_sets'         => ['required', 'integer', 'min:1'],
            'exercises.*.target_reps'         => ['nullable', 'integer', 'min:1'],
            'exercises.*.target_rest_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $routine = Routine::create([
            'user_id'     => auth()->id(),
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $this->syncExercises($routine, $validated['exercises']);

        return redirect()->route('routines.index')->with('success', 'Routine created.');
    }

    public function show(Routine $routine): Response
    {
        $this->authorize('view', $routine);

        return Inertia::render('Routines/Builder', [
            'routine'            => $routine->load('routineExercises.exercise'),
            'availableExercises' => Exercise::availableTo(auth()->id())
                ->get(['id', 'name', 'primary_muscle']),
        ]);
    }

    public function edit(Routine $routine): Response
    {
        return $this->show($routine);
    }

    public function update(Request $request, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $routine);

        $validated = $request->validate([
            'name'                            => ['required', 'string', 'max:255'],
            'description'                     => ['nullable', 'string'],
            'exercises'                       => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id'         => ['required', 'integer', 'exists:exercises,id'],
            'exercises.*.order'               => ['required', 'integer', 'min:1'],
            'exercises.*.target_sets'         => ['required', 'integer', 'min:1'],
            'exercises.*.target_reps'         => ['nullable', 'integer', 'min:1'],
            'exercises.*.target_rest_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $routine->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $this->syncExercises($routine, $validated['exercises']);

        return redirect()->route('routines.index')->with('success', 'Routine updated.');
    }

    public function destroy(Routine $routine): RedirectResponse
    {
        $this->authorize('delete', $routine);
        $routine->delete();

        return redirect()->route('routines.index')->with('success', 'Routine deleted.');
    }

    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'primary_muscle'  => ['required', 'string'],
            'difficulty_level' => ['required', 'integer', 'in:1,2,3'],
        ]);

        $exercises = Exercise::availableTo(auth()->id())
            ->where('primary_muscle', $validated['primary_muscle'])
            ->where('difficulty_level', '<=', $validated['difficulty_level'])
            ->get()
            ->groupBy('sub_muscle_target');

        if ($exercises->isEmpty()) {
            return back()->with('error', 'No exercises found for the selected muscle group and difficulty.');
        }

        $routine = Routine::create([
            'user_id'     => auth()->id(),
            'name'        => ucfirst($validated['primary_muscle']) . ' Workout (Auto-generated)',
            'description' => "Generated for {$validated['primary_muscle']}, difficulty ≤ {$validated['difficulty_level']}",
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

        return redirect()->route('routines.show', $routine)->with('success', 'Routine generated!');
    }

    private function syncExercises(Routine $routine, array $exercises): void
    {
        $routine->routineExercises()->delete();

        $rows = array_map(fn ($ex) => [
            'routine_id'          => $routine->id,
            'exercise_id'         => $ex['exercise_id'],
            'order'               => $ex['order'],
            'target_sets'         => $ex['target_sets'],
            'target_reps'         => $ex['target_reps'] ?? null,
            'target_rest_seconds' => $ex['target_rest_seconds'] ?? null,
        ], $exercises);

        RoutineExercise::insert($rows);
    }
}
