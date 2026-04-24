<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExerciseController extends Controller
{
    public function index(Request $request): Response
    {
        $exercises = QueryBuilder::for(Exercise::availableTo(auth()->id()))
            ->allowedFilters([
                AllowedFilter::exact('primary_muscle'),
                AllowedFilter::partial('equipment_required'),
            ])
            ->with('media')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($e) => [
                'id'                 => $e->id,
                'name'               => $e->name,
                'primary_muscle'     => $e->primary_muscle,
                'sub_muscle_target'  => $e->sub_muscle_target,
                'difficulty_level'   => $e->difficulty_level,
                'equipment_required' => $e->equipment_required,
                'is_custom'          => ! is_null($e->user_id),
                'demonstration_url'  => $e->getFirstMediaUrl('demonstration'),
            ]);

        $muscleOptions = Exercise::availableTo(auth()->id())
            ->whereNotNull('primary_muscle')
            ->distinct()
            ->pluck('primary_muscle');

        $equipmentOptions = Exercise::availableTo(auth()->id())
            ->whereNotNull('equipment_required')
            ->distinct()
            ->pluck('equipment_required');

        return Inertia::render('Exercises/Index', [
            'exercises'       => $exercises,
            'muscleOptions'   => $muscleOptions,
            'equipmentOptions' => $equipmentOptions,
            'filters'         => $request->only('filter'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'primary_muscle'     => ['required', 'string', 'max:100'],
            'sub_muscle_target'  => ['required', 'string', 'max:100'],
            'difficulty_level'   => ['required', 'integer', 'in:1,2,3'],
            'description'        => ['nullable', 'string'],
            'equipment_required' => ['nullable', 'string', 'max:100'],
            'demonstration'      => ['nullable', 'image', 'max:4096'],
        ]);

        $exercise = Exercise::create(array_merge(
            collect($validated)->except('demonstration')->toArray(),
            ['user_id' => auth()->id()]
        ));

        if ($request->hasFile('demonstration')) {
            $exercise->addMediaFromRequest('demonstration')
                ->usingFileName(md5(time()) . '.' . $request->file('demonstration')->extension())
                ->toMediaCollection('demonstration');
        }

        return redirect()->route('exercises.index')->with('success', 'Exercise created.');
    }
}
