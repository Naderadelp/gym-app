# Implementation Plan: Workout Tracker Web Interface (V1)

**Branch**: `002-inertia-frontend` | **Date**: 2026-04-24 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `specs/002-inertia-frontend/spec.md`

## Summary

A server-rendered web UI layered on top of the existing Laravel 13 backend. Inertia.js bridges PHP controllers to Vue 3 (Composition API) pages, eliminating the need for a separate REST layer for the web app. Tailwind CSS handles all styling. Five phases mirror the API feature set: dashboard + profile, exercise library, routine builder + generator, live workout tracking, and analytics.

## Technical Context

**Language/Version**: PHP 8.3 (backend), JavaScript/ES2022 (frontend), Vue 3.4+
**Primary Dependencies**: Inertia.js (Laravel adapter + Vue 3 adapter), Vue 3 Composition API, Tailwind CSS 3, `vue-chartjs` + `chart.js` (analytics charts), Vite (asset bundling — already in Laravel 13)
**Storage**: No additional storage — reads from the same database as the API (001-workout-tracker-api)
**Testing**: None — no Pest, PHPUnit, or browser tests
**Target Platform**: Web browser (desktop and tablet; mobile-responsive)
**Project Type**: Server-rendered web application (Inertia.js SPA-style)
**Performance Goals**: First Contentful Paint under 1.5s; set-log save round-trip under 500ms
**Constraints**:
- All web controllers MUST be in `App\Http\Controllers\Web` namespace
- All web controllers MUST return `Inertia::render('PageName', [...data])`
- All web routes in `routes/web.php` under `auth` middleware
- Do NOT modify existing API controllers (`App\Http\Controllers\*`)
- No Sanctum for web auth — use standard Laravel session auth

## Constitution Check

No project constitution defined. No gates to enforce.

## Project Structure

### Documentation (this feature)

```text
specs/002-inertia-frontend/
├── plan.md              ← this file
├── research.md          ← Phase 0 decisions
├── data-model.md        ← Phase 1 page-data & component contracts
├── contracts/
│   └── web-routes.md    ← Phase 1 web route contracts
└── tasks.md             ← Phase 2 output (/speckit-tasks)
```

### Source Code (repository root)

```text
app/Http/Controllers/Web/
├── DashboardController.php
├── ProfileController.php
├── BodyMetricController.php
├── ExerciseController.php
├── RoutineController.php
├── WorkoutSessionController.php
└── AnalyticsController.php

resources/js/
├── Pages/
│   ├── Dashboard.vue
│   ├── Profile/
│   │   └── Edit.vue
│   ├── Exercises/
│   │   ├── Index.vue
│   │   └── CreateModal.vue
│   ├── Routines/
│   │   ├── Index.vue
│   │   ├── Builder.vue
│   │   └── SmartGeneratorModal.vue
│   ├── Workouts/
│   │   ├── ActiveSession.vue
│   │   └── components/
│   │       ├── WorkoutTimer.vue
│   │       └── RestTimer.vue
│   └── Analytics/
│       └── Index.vue
└── app.js                           (existing Inertia bootstrap)

routes/
└── web.php
```

**Structure Decision**: Standard Inertia.js layout. Pages in `resources/js/Pages/` — one Vue SFC per route. Sub-components (modals, timers) live alongside or in a `components/` subfolder.

---

## Master Frontend Blueprint

### Phase 1: Dashboard & Profile

#### Web Controllers

**`DashboardController@index`**
- Auth required
- `auth()->user()->workoutSessions()->latest('started_at')->first()` → latest session
- Weekly volume: `SUM(weight * reps)` from `workout_logs` via `workout_sessions` for current calendar week, `set_type != 'warmup'`
- Returns: `Inertia::render('Dashboard', ['latestSession' => ..., 'weeklyVolume' => ...])`

**`ProfileController@edit`**
- Returns: `Inertia::render('Profile/Edit', ['user' => auth()->user()->load('media'), 'bodyMetrics' => auth()->user()->bodyMetrics()->latest('logged_at')->get()])`

**`ProfileController@update`**
- Validate: `name`, `display_name`, `unit_preference` (in:metric,imperial)
- `auth()->user()->update($validated)` → `Redirect::route('profile.edit')`

**`ProfileController@uploadAvatar`** (separate POST route)
- `auth()->user()->addMediaFromRequest('avatar')->toMediaCollection('avatar')`
- `Redirect::back()`

**`BodyMetricController@store`**
- Validate: `logged_at` (required date), `weight` (numeric nullable)
- `BodyMetric::updateOrCreate(['user_id' => auth()->id(), 'logged_at' => $validated['logged_at']], $validated)`
- `Redirect::back()`

#### Vue Pages

**`Dashboard.vue`**
- Props: `latestSession` (object|null), `weeklyVolume` (number)
- Stat cards: weekly volume, last workout date
- "Start Empty Workout" button → `POST /workouts/start`

**`Profile/Edit.vue`**
- Props: `user` (with `avatar_url`), `bodyMetrics` (array)
- Profile form: `useForm({ name, display_name, unit_preference })` → `PUT /profile`
- Avatar upload: `useForm({ avatar: null }, { forceFormData: true })` → `POST /profile/avatar`
- Body metrics table: most recent first; inline add-metric form

---

### Phase 2: Exercise Library UI

#### Web Controllers

**`ExerciseController@index`**
- `QueryBuilder::for(Exercise::availableTo(auth()->id()))->allowedFilters(['primary_muscle', 'equipment_required'])->paginate(20)`
- `$muscleOptions = Exercise::availableTo(auth()->id())->whereNotNull('primary_muscle')->distinct()->pluck('primary_muscle')`
- `$equipmentOptions = Exercise::availableTo(auth()->id())->whereNotNull('equipment_required')->distinct()->pluck('equipment_required')`
- Returns: `Inertia::render('Exercises/Index', ['exercises' => $paged, 'muscleOptions' => $muscleOptions, 'equipmentOptions' => $equipmentOptions, 'filters' => $request->only('filter')])`

**`ExerciseController@store`**
- Validate: `name`, `primary_muscle`, `sub_muscle_target`, `difficulty_level` (in:1,2,3); optional `description`, `equipment_required`, `demonstration` (image file)
- `$exercise = Exercise::create([..., 'user_id' => auth()->id()])`
- If `demonstration`: `$exercise->addMediaFromRequest('demonstration')->toMediaCollection('demonstration')`
- `Redirect::route('exercises.index')`

#### Vue Pages

**`Exercises/Index.vue`**
- Props: `exercises` (Inertia paginated), `muscleOptions`, `equipmentOptions`, `filters`
- Filter bar: two `<select>` elements; on change → `router.get(route('exercises.index'), filters, { preserveState: true })`
- Exercise grid: cards with name, muscle badge, difficulty indicator, demo image
- "Add Exercise" button → toggles `CreateModal` (`v-if` with `v-model:show`)

**`Exercises/CreateModal.vue`**
- Local `useForm({ name, primary_muscle, sub_muscle_target, difficulty_level, description, equipment_required, demonstration: null })` with `{ forceFormData: true }`
- `form.post(route('exercises.store'), { onSuccess: () => emit('close') })`

---

### Phase 3: Routine Builder & Smart Generator UI

#### Web Controllers

**`RoutineController@index`**
- Returns: `Inertia::render('Routines/Index', ['routines' => auth()->user()->routines()->withCount('routineExercises')->latest()->get()])`

**`RoutineController@create`**
- Returns: `Inertia::render('Routines/Builder', ['availableExercises' => Exercise::availableTo(auth()->id())->get(['id', 'name', 'primary_muscle'])])`

**`RoutineController@store`**
- Validate: `name` (required), `exercises` (array), `exercises.*.exercise_id`, `exercises.*.order`, `exercises.*.target_sets`, `exercises.*.target_reps` (nullable)
- Create Routine; delete+reinsert `RoutineExercise` pivot rows
- `Redirect::route('routines.index')`

**`RoutineController@generate`**
- Validate: `primary_muscle` (required), `difficulty_level` (required, in:1,2,3)
- Same generation logic as `App\Http\Controllers\RoutineGeneratorController`
- On success: `Redirect::route('routines.show', $routine->id)` (or `Redirect::back()->withErrors(...)` if empty)

#### Vue Pages

**`Routines/Index.vue`**
- Props: `routines` (array with `routine_exercises_count`)
- Routine cards: name, exercise count, "Start" and "Edit" buttons
- "Generate Smart Workout" → opens `SmartGeneratorModal`

**`Routines/Builder.vue`**
- Props: `availableExercises`
- Reactive `selectedExercises` array (`ref([])`)
- Search: `ref('')` input + `computed` filter over `availableExercises`
- Click exercise → push to `selectedExercises` with defaults `{ target_sets: 3, target_reps: 10 }`
- Drag-to-reorder optional (V1 default: manual order by add sequence)
- `useForm({ name, exercises: selectedExercises }).post(route('routines.store'))`

**`Routines/SmartGeneratorModal.vue`**
- `useForm({ primary_muscle: '', difficulty_level: '' }).post(route('routines.generate'))`
- Shows flash error inline if no exercises found

---

### Phase 4: Active Workout Tracker

#### Web Controllers

**`WorkoutSessionController@create`**
- `$session = WorkoutSession::create(['user_id' => auth()->id(), 'started_at' => now(), 'routine_id' => $request->routine_id])`
- `Redirect::route('workouts.show', $session)`

**`WorkoutSessionController@show`**
- `$session->load('routine.routineExercises.exercise', 'workoutLogs.exercise')`
- Group `workoutLogs` by `exercise_id` in PHP before passing
- Returns: `Inertia::render('Workouts/ActiveSession', ['session' => $session, 'logsByExercise' => $grouped, 'routine' => $session->routine])`

**`WorkoutSessionController@finish`**
- `$session->update(['ended_at' => now()])`
- `Redirect::route('dashboard')`

**`WorkoutLogController@store`** (web variant — POST `/workouts/{session}/logs`)
- Validate set data; `WorkoutLog::create([..., 'workout_session_id' => $session->id])`
- Return `Redirect::back()->with(...)` (Inertia handles partial reload via `only`)

**`WorkoutLogController@destroy`** (web variant — DELETE `/workout-logs/{log}`)
- Authorize; delete; `Redirect::back()`

#### Vue Pages

**`Workouts/ActiveSession.vue`**
- Props: `session`, `logsByExercise` (keyed by exercise_id), `routine` (nullable)
- Uses `WorkoutTimer` component for elapsed display
- Set input form: `weight`, `reps`, `set_type` select → on "Log Set": `router.post(route('workout-logs.store.web', session.id), data, { preserveScroll: true, only: ['logsByExercise'] })`
- Delete: `router.delete(route('workout-logs.destroy.web', log.id), { preserveScroll: true, only: ['logsByExercise'] })`
- "Finish Workout": `router.post(route('workouts.finish', session.id))` with `beforeunload` warning if unsaved input

**`Workouts/components/WorkoutTimer.vue`**
- Props: `startedAt` (ISO string)
- `const elapsed = ref(0)` — `setInterval` every 1s computing diff from `new Date(startedAt)`
- Displays as `HH:MM:SS`
- `onUnmounted` → `clearInterval`

**`Workouts/components/RestTimer.vue`**
- Props: `defaultSeconds` (default: 60)
- `remaining` ref, `running` ref
- "Start Rest" starts countdown; audio/visual cue at zero; "Cancel" resets

---

### Phase 5: Analytics

#### Web Controllers

**`AnalyticsController@index`**
- Volume: last 12 weeks, `SUM(weight * reps)`, exclude warmup, group by `YEARWEEK`
- Format as `[{ label: 'Week 16', value: 12450 }, ...]` (12 entries, zero-fill missing weeks)
- PRs: `MAX(weight)` per exercise from same join, exclude warmup
- Returns: `Inertia::render('Analytics/Index', ['volumeData' => $volume, 'personalRecords' => $prs])`

#### Vue Pages

**`Analytics/Index.vue`**
- Props: `volumeData` (array of `{ label, value }`), `personalRecords` (array of `{ exercise_name, max_weight }`)
- Volume chart: `<Bar :data="chartData" :options="chartOptions" />` from `vue-chartjs`
- PR table: `<table>` with exercise name and max weight
- Zero/empty state: shown when all volume values are zero
