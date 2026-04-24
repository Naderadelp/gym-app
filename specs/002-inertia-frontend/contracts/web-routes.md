# Web Route Contracts: Workout Tracker (V1)

**File**: `routes/web.php`
**Auth**: All routes require `auth` middleware (standard session-based)
**Response format**: All controllers return `Inertia::render(...)` or `Redirect::route(...)`

---

## Phase 1 — Dashboard & Profile

### GET /dashboard
**Controller**: `Web\DashboardController@index`
**Returns**: `Inertia::render('Dashboard', [...])`
**Props**: `latestSession`, `weeklyVolume`
**Named route**: `dashboard`

---

### GET /profile
**Controller**: `Web\ProfileController@edit`
**Returns**: `Inertia::render('Profile/Edit', [...])`
**Props**: `user` (with avatar_url), `bodyMetrics`
**Named route**: `profile.edit`

---

### PUT /profile
**Controller**: `Web\ProfileController@update`
**Request body**: `name?`, `display_name?`, `unit_preference?` (metric|imperial)
**Returns**: `Redirect::route('profile.edit')`
**Named route**: `profile.update`

---

### POST /profile/avatar
**Controller**: `Web\ProfileController@uploadAvatar`
**Request body**: `avatar` (multipart file)
**Returns**: `Redirect::back()`
**Named route**: `profile.avatar`

---

### POST /body-metrics
**Controller**: `Web\BodyMetricController@store`
**Request body**: `logged_at` (date), `weight?` (numeric)
**Returns**: `Redirect::back()`
**Named route**: `body-metrics.store`

---

## Phase 2 — Exercise Library

### GET /exercises
**Controller**: `Web\ExerciseController@index`
**Query params**: `filter[primary_muscle]?`, `filter[equipment_required]?`, `page?`
**Returns**: `Inertia::render('Exercises/Index', [...])`
**Props**: `exercises` (paginated), `muscleOptions`, `equipmentOptions`, `filters`
**Named route**: `exercises.index`

---

### POST /exercises
**Controller**: `Web\ExerciseController@store`
**Request body**: `name`, `primary_muscle`, `sub_muscle_target`, `difficulty_level` (1|2|3), `description?`, `equipment_required?`, `demonstration?` (file, multipart)
**Returns**: `Redirect::route('exercises.index')`
**Named route**: `exercises.store`

---

## Phase 3 — Routines

### GET /routines
**Controller**: `Web\RoutineController@index`
**Returns**: `Inertia::render('Routines/Index', [...])`
**Props**: `routines`
**Named route**: `routines.index`

---

### GET /routines/create
**Controller**: `Web\RoutineController@create`
**Returns**: `Inertia::render('Routines/Builder', [...])`
**Props**: `availableExercises`
**Named route**: `routines.create`

---

### POST /routines
**Controller**: `Web\RoutineController@store`
**Request body**: `name`, `exercises[]` → `[{ exercise_id, order, target_sets, target_reps? }]`
**Returns**: `Redirect::route('routines.index')`
**Named route**: `routines.store`

---

### POST /routines/generate
**Controller**: `Web\RoutineController@generate`
**Request body**: `primary_muscle`, `difficulty_level` (1|2|3)
**Returns**: `Redirect::route('routines.show', $routine->id)` on success; `Redirect::back()->withErrors(...)` if no exercises found
**Named route**: `routines.generate`

---

### GET /routines/{routine}
**Controller**: `Web\RoutineController@show`
**Returns**: `Inertia::render('Routines/Builder', [...])` (edit mode)
**Props**: `availableExercises`, `routine` (with existing exercises)
**Named route**: `routines.show`

---

### PUT /routines/{routine}
**Controller**: `Web\RoutineController@update`
**Request body**: Same as `store`
**Returns**: `Redirect::route('routines.index')`
**Named route**: `routines.update`

---

### DELETE /routines/{routine}
**Controller**: `Web\RoutineController@destroy`
**Returns**: `Redirect::route('routines.index')`
**Named route**: `routines.destroy`

---

## Phase 4 — Workout Sessions

### POST /workouts/start
**Controller**: `Web\WorkoutSessionController@create`
**Request body**: `routine_id?` (integer)
**Returns**: `Redirect::route('workouts.show', $session->id)`
**Named route**: `workouts.start`

---

### GET /workouts/{session}
**Controller**: `Web\WorkoutSessionController@show`
**Returns**: `Inertia::render('Workouts/ActiveSession', [...])`
**Props**: `session`, `logsByExercise`, `routine`
**Named route**: `workouts.show`

---

### POST /workouts/{session}/finish
**Controller**: `Web\WorkoutSessionController@finish`
**Request body**: none
**Returns**: `Redirect::route('dashboard')`
**Named route**: `workouts.finish`

---

### POST /workouts/{session}/logs
**Controller**: `Web\WorkoutLogController@store`
**Request body**: `exercise_id`, `set_number`, `weight?`, `reps?`, `duration_seconds?`, `distance_km?`, `rpe?`, `set_type?` (warmup|normal|drop|failure)
**Returns**: `Redirect::back()` — Inertia partial reload via `only: ['logsByExercise']` on client
**Named route**: `workout-logs.store.web`

---

### DELETE /workout-logs/{log}
**Controller**: `Web\WorkoutLogController@destroy`
**Returns**: `Redirect::back()` — Inertia partial reload via `only: ['logsByExercise']` on client
**Named route**: `workout-logs.destroy.web`

---

## Phase 5 — Analytics

### GET /analytics
**Controller**: `Web\AnalyticsController@index`
**Returns**: `Inertia::render('Analytics/Index', [...])`
**Props**: `volumeData` (12 weekly data points), `personalRecords`
**Named route**: `analytics.index`

---

## Route Registration Summary

```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [Web\DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [Web\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [Web\ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [Web\ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::post('/body-metrics', [Web\BodyMetricController::class, 'store'])->name('body-metrics.store');

    // Exercises
    Route::get('/exercises', [Web\ExerciseController::class, 'index'])->name('exercises.index');
    Route::post('/exercises', [Web\ExerciseController::class, 'store'])->name('exercises.store');

    // Routines
    Route::post('/routines/generate', [Web\RoutineController::class, 'generate'])->name('routines.generate');
    Route::resource('routines', Web\RoutineController::class);

    // Workout sessions
    Route::post('/workouts/start', [Web\WorkoutSessionController::class, 'create'])->name('workouts.start');
    Route::get('/workouts/{session}', [Web\WorkoutSessionController::class, 'show'])->name('workouts.show');
    Route::post('/workouts/{session}/finish', [Web\WorkoutSessionController::class, 'finish'])->name('workouts.finish');
    Route::post('/workouts/{session}/logs', [Web\WorkoutLogController::class, 'store'])->name('workout-logs.store.web');
    Route::delete('/workout-logs/{log}', [Web\WorkoutLogController::class, 'destroy'])->name('workout-logs.destroy.web');

    // Analytics
    Route::get('/analytics', [Web\AnalyticsController::class, 'index'])->name('analytics.index');
});
```

> **Note**: `routines/generate` is declared **before** `Route::resource('routines', ...)` to prevent Laravel from matching `generate` as a `{routine}` wildcard parameter.
