# Implementation Plan: Personal Workout Tracker API (V1)

**Branch**: `001-workout-tracker-api` | **Date**: 2026-04-24 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `specs/001-workout-tracker-api/spec.md`

## Summary

A mobile-first B2C JSON REST API built on Laravel 13 / PHP 8.3. Five sequential phases deliver user profile management with body metrics, a shared + personal exercise library, routine building with smart auto-generation, live workout session logging, and volume/PR analytics. All endpoints live under `routes/api.php → /api/v1/*` protected by `auth:sanctum`. All responses are standardised through `BaseController`.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 13
**Primary Dependencies**: Laravel Sanctum (auth), Spatie MediaLibrary (avatar + demo images), Spatie QueryBuilder (filtering/sorting/pagination), Spatie Permission (optional, already installed)
**Storage**: Relational database (MySQL/PostgreSQL — standard Laravel default)
**Testing**: None — no Pest or PHPUnit tests to be generated
**Target Platform**: Linux API server
**Project Type**: JSON REST API (web-service)
**Performance Goals**: Standard web API — sub-second response for paginated list queries with up to 2 years of user data
**Constraints**: Every controller MUST extend `BaseController` and use `$this->success()` / `$this->error()` / `$this->paginated()`; all routes under `v1` prefix with `auth:sanctum`
**Scale/Scope**: Single-tenant B2C, individual user data isolation enforced at query level

## Constitution Check

No project constitution has been defined (`.specify/memory/constitution.md` contains only the placeholder template). No gates to enforce.

**Post-design re-check**: N/A — no constitution principles to validate against.

## Project Structure

### Documentation (this feature)

```text
specs/001-workout-tracker-api/
├── plan.md              ← this file
├── research.md          ← Phase 0 decisions
├── data-model.md        ← Phase 1 entity definitions
├── contracts/
│   └── api.md           ← Phase 1 endpoint contracts
└── tasks.md             ← Phase 2 output (/speckit-tasks)
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── BaseController.php          (existing)
│   │   ├── ProfileController.php
│   │   ├── BodyMetricController.php
│   │   ├── ExerciseController.php
│   │   ├── RoutineController.php
│   │   ├── RoutineGeneratorController.php
│   │   ├── WorkoutSessionController.php
│   │   ├── WorkoutLogController.php
│   │   └── AnalyticsController.php
│   └── Requests/
│       ├── UpdateProfileRequest.php
│       ├── StoreBodyMetricRequest.php
│       ├── StoreExerciseRequest.php
│       ├── UpdateExerciseRequest.php
│       ├── StoreRoutineRequest.php
│       ├── UpdateRoutineRequest.php
│       ├── GenerateRoutineRequest.php
│       ├── StoreWorkoutSessionRequest.php
│       └── StoreWorkoutLogRequest.php
├── Models/
│   ├── User.php
│   ├── BodyMetric.php
│   ├── Exercise.php
│   ├── Routine.php
│   ├── RoutineExercise.php
│   ├── WorkoutSession.php
│   └── WorkoutLog.php
└── Policies/
    ├── ExercisePolicy.php
    ├── RoutinePolicy.php
    ├── WorkoutSessionPolicy.php
    └── WorkoutLogPolicy.php

database/migrations/
│   ├── xxxx_add_profile_fields_to_users_table.php
│   ├── xxxx_create_body_metrics_table.php
│   ├── xxxx_create_exercises_table.php
│   ├── xxxx_create_routines_table.php
│   ├── xxxx_create_routine_exercises_table.php
│   ├── xxxx_create_workout_sessions_table.php
│   └── xxxx_create_workout_logs_table.php

routes/
└── api.php
```

**Structure Decision**: Standard Laravel MVC. No repository layer needed — controllers query Eloquent models directly via scopes and Spatie QueryBuilder. Policies handle authorization.

---

## Master API Blueprint

### Phase 1: User Profiles & Body Metrics

#### Database & Models

**Users table additions**
- `display_name` — string, nullable
- `unit_preference` — string, default `'metric'` (enum: `'metric'`, `'imperial'`)

**`App\Models\User` updates**
- Add `display_name`, `unit_preference` to `$fillable`
- Implement `Spatie\MediaLibrary\HasMedia` + `InteractsWithMedia`
- `registerMediaCollections`: single collection `'avatar'` with `singleFile()`
- `hasMany(BodyMetric::class)`

**`body_metrics` table**
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| user_id | foreignId | constrained, cascadeOnDelete |
| weight | decimal(5,2) | nullable, stored in KG |
| height | decimal(5,2) | nullable, stored in CM |
| body_fat_percentage | decimal(4,2) | nullable |
| logged_at | date | |
| timestamps | | |

**`App\Models\BodyMetric`**: `$fillable`, cast `logged_at` → date, `belongsTo(User::class)`

#### Controllers

| Method | URI | Controller@action | Notes |
|--------|-----|-------------------|-------|
| GET | `/profile` | `ProfileController@show` | Returns user + avatar URL + latest BodyMetric (ordered by `logged_at` desc) |
| PUT | `/profile` | `ProfileController@update` | Updates `name`, `display_name`, `unit_preference` |
| POST | `/profile/avatar` | `ProfileController@uploadAvatar` | Attaches to `'avatar'` media collection |
| GET | `/body-metrics` | `BodyMetricController@index` | Spatie QueryBuilder, allowed sort: `logged_at`, returns `$this->paginated()` |
| POST | `/body-metrics` | `BodyMetricController@store` | `updateOrCreate(['logged_at' => $request->logged_at], [...])` |
| DELETE | `/body-metrics/{bodyMetric}` | `BodyMetricController@destroy` | |

---

### Phase 2: Exercise Library

#### Database & Models

**`exercises` table**
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| user_id | foreignId | nullable, constrained, cascadeOnDelete. NULL = system-wide |
| name | string | |
| description | text | nullable |
| primary_muscle | string | |
| sub_muscle_target | string | |
| difficulty_level | integer | 1–3 |
| equipment_required | string | nullable |
| timestamps | | |

**`App\Models\Exercise`**
- `$fillable`, `belongsTo(User::class)`
- Implement `HasMedia` + `InteractsWithMedia`; collection `'demonstration'` with `singleFile()`
- `scopeAvailableTo($query, $userId)`: `where('user_id', null)->orWhere('user_id', $userId)`

#### Controllers

| Method | URI | Controller@action | Notes |
|--------|-----|-------------------|-------|
| GET | `/exercises` | `ExerciseController@index` | Spatie QueryBuilder + `availableTo(auth()->id())`; filters: `primary_muscle`, `difficulty_level`, `equipment_required` |
| POST | `/exercises` | `ExerciseController@store` | Force `user_id = auth()->id()`; attach `'demonstration'` image if present |
| GET | `/exercises/{exercise}` | `ExerciseController@show` | Authorize: user owns OR system-wide |
| PUT | `/exercises/{exercise}` | `ExerciseController@update` | Reject if `user_id` is null |
| DELETE | `/exercises/{exercise}` | `ExerciseController@destroy` | Reject if `user_id` is null |

---

### Phase 3: Routines & Smart Generator

#### Database & Models

**`routines` table**: `id`, `user_id` (constrained, cascade), `name` (string), `description` (text, nullable), timestamps.

**`routine_exercises` table**
| Column | Type |
|--------|------|
| id | bigIncrements |
| routine_id | foreignId, constrained, cascade |
| exercise_id | foreignId, constrained, cascade |
| order | integer |
| target_sets | integer |
| target_reps | integer, nullable |
| target_rest_seconds | integer, nullable |

**Models**: `Routine` → `$fillable`, `belongsTo(User::class)`, `hasMany(RoutineExercise::class)`. `RoutineExercise` → `belongsTo(Routine::class)`, `belongsTo(Exercise::class)`.

#### Controllers

| Method | URI | Controller@action | Notes |
|--------|-----|-------------------|-------|
| GET | `/routines` | `RoutineController@index` | User's routines only |
| POST | `/routines` | `RoutineController@store` | Accepts nested `exercises` array; sync pivot |
| GET | `/routines/{routine}` | `RoutineController@show` | |
| PUT | `/routines/{routine}` | `RoutineController@update` | Sync pivot on update |
| DELETE | `/routines/{routine}` | `RoutineController@destroy` | |
| POST | `/routines/generate` | `RoutineGeneratorController@generate` | Validate `primary_muscle` + `difficulty_level`; query `availableTo`; filter; group by `sub_muscle_target`; pick 1 random per group; create Routine + attach |

---

### Phase 4: Workout Sessions & Logging

#### Database & Models

**`workout_sessions` table**: `id`, `user_id` (constrained, cascade), `routine_id` (foreignId, nullable, constrained, setNullOnDelete), `started_at` (datetime), `ended_at` (datetime, nullable), `notes` (text, nullable), timestamps.

**`workout_logs` table**
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| workout_session_id | foreignId | constrained, cascade |
| exercise_id | foreignId | constrained, cascade |
| set_number | integer | |
| weight | decimal(5,2) | nullable |
| reps | integer | nullable |
| duration_seconds | integer | nullable |
| distance_km | decimal(5,2) | nullable |
| rpe | integer | nullable, 1–10 |
| set_type | string | default `'normal'`; enum: `'warmup'`, `'normal'`, `'drop'`, `'failure'` |
| timestamps | | |

**Models**: `WorkoutSession` → `$fillable`, `belongsTo(User::class)`, `belongsTo(Routine::class)`, `hasMany(WorkoutLog::class)`. `WorkoutLog` → `$fillable`, `belongsTo(WorkoutSession::class)`, `belongsTo(Exercise::class)`.

#### Controllers

| Method | URI | Controller@action | Notes |
|--------|-----|-------------------|-------|
| GET | `/workout-sessions` | `WorkoutSessionController@index` | User's sessions |
| POST | `/workout-sessions` | `WorkoutSessionController@store` | Default `started_at` to `now()` |
| GET | `/workout-sessions/{session}` | `WorkoutSessionController@show` | Eager load logs grouped by exercise |
| PUT | `/workout-sessions/{session}` | `WorkoutSessionController@update` | Update notes/routine |
| DELETE | `/workout-sessions/{session}` | `WorkoutSessionController@destroy` | |
| POST | `/workout-sessions/{session}/finish` | `WorkoutSessionController@finish` | Set `ended_at = now()` |
| POST | `/workout-sessions/{session}/logs` | `WorkoutLogController@store` | Add set to active session |
| DELETE | `/workout-logs/{log}` | `WorkoutLogController@destroy` | |

---

### Phase 5: Analytics

#### Controllers

| Method | URI | Controller@action | Logic |
|--------|-----|-------------------|-------|
| GET | `/analytics/volume` | `AnalyticsController@volume` | `weight * reps` from `workout_logs` joined via `workout_sessions` for auth user; exclude `set_type = 'warmup'`; group by session or week via `?group_by=week\|session` |
| GET | `/analytics/personal-records` | `AnalyticsController@personalRecords` | Max `weight` per exercise; optional `?exercise_id=X`; exclude `set_type = 'warmup'` |
