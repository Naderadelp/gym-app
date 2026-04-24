# Data Model: Personal Workout Tracker API (V1)

**Branch**: `001-workout-tracker-api` | **Date**: 2026-04-24

---

## Entity Relationship Overview

```
users
  ├─── body_metrics          (one-to-many, cascade delete)
  ├─── exercises             (one-to-many, cascade delete; NULL user_id = system-wide)
  ├─── routines              (one-to-many, cascade delete)
  └─── workout_sessions      (one-to-many, cascade delete)

routines
  └─── routine_exercises     (one-to-many, cascade delete)
         └── exercise_id FK → exercises

workout_sessions
  ├─── routine_id FK         (nullable, set null on delete)
  └─── workout_logs          (one-to-many, cascade delete)
         └── exercise_id FK → exercises
```

---

## Entity: `users` (existing table — additive migration)

### New Columns

| Column | Type | Nullable | Default | Validation |
|--------|------|----------|---------|------------|
| display_name | string(255) | yes | null | max:255 |
| unit_preference | string | no | `'metric'` | in:metric,imperial |

### Existing Key Columns Referenced

| Column | Notes |
|--------|-------|
| id | PK |
| name | string, updated via PUT /profile |
| email | string, auth identifier |

### Media Collections

| Collection | Type | Notes |
|------------|------|-------|
| avatar | single file | Replaces previous on upload; URL exposed in profile response |

### Relationships

- `hasMany(BodyMetric::class)` via `user_id`
- `hasMany(Exercise::class)` via `user_id`
- `hasMany(Routine::class)` via `user_id`
- `hasMany(WorkoutSession::class)` via `user_id`

---

## Entity: `body_metrics`

### Columns

| Column | Type | Nullable | Default | Validation |
|--------|------|----------|---------|------------|
| id | bigIncrements | — | — | |
| user_id | foreignId | no | — | exists:users,id |
| weight | decimal(5,2) | yes | null | numeric, min:0, max:999.99 |
| height | decimal(5,2) | yes | null | numeric, min:0, max:999.99 |
| body_fat_percentage | decimal(4,2) | yes | null | numeric, min:0, max:99.99 |
| logged_at | date | no | — | date, required |
| created_at / updated_at | timestamps | — | — | |

### Constraints

- Unique index on `(user_id, logged_at)` — enforces one entry per user per day
- `logged_at` is stored as a `DATE` (no time component)

### Relationships

- `belongsTo(User::class)`

### Business Rules

- `store` uses `updateOrCreate(['user_id' => auth()->id(), 'logged_at' => $request->logged_at], $fillable)` — no duplicates
- Soft data: all metric fields are nullable; at least one should be present (validation enforced at controller level)

---

## Entity: `exercises`

### Columns

| Column | Type | Nullable | Default | Validation |
|--------|------|----------|---------|------------|
| id | bigIncrements | — | — | |
| user_id | foreignId | yes | null | exists:users,id or null |
| name | string(255) | no | — | required, max:255 |
| description | text | yes | null | |
| primary_muscle | string(100) | no | — | required, max:100 |
| sub_muscle_target | string(100) | no | — | required, max:100 |
| difficulty_level | tinyInteger | no | — | required, integer, in:1,2,3 |
| equipment_required | string(100) | yes | null | max:100 |
| created_at / updated_at | timestamps | — | — | |

### Constraints

- `user_id` nullable — NULL means system-wide, non-null means user-owned
- Foreign key: `user_id` → `users.id`, ON DELETE CASCADE (so user deletion removes their custom exercises)

### Media Collections

| Collection | Type | Notes |
|------------|------|-------|
| demonstration | single file | Optional; exercise demonstration image |

### Relationships

- `belongsTo(User::class)` (nullable)
- `hasMany(RoutineExercise::class)` via `exercise_id`
- `hasMany(WorkoutLog::class)` via `exercise_id`

### Query Scopes

- `scopeAvailableTo($query, int $userId)`: `->where(fn($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))`

### Authorization Rules

- `show`: user_id is null OR user_id === auth()->id()
- `update` / `destroy`: user_id MUST equal auth()->id() (system exercises are immutable)

---

## Entity: `routines`

### Columns

| Column | Type | Nullable | Default | Validation |
|--------|------|----------|---------|------------|
| id | bigIncrements | — | — | |
| user_id | foreignId | no | — | exists:users,id |
| name | string(255) | no | — | required, max:255 |
| description | text | yes | null | |
| created_at / updated_at | timestamps | — | — | |

### Relationships

- `belongsTo(User::class)`
- `hasMany(RoutineExercise::class)`

---

## Entity: `routine_exercises`

### Columns

| Column | Type | Nullable | Default | Validation |
|--------|------|----------|---------|------------|
| id | bigIncrements | — | — | |
| routine_id | foreignId | no | — | exists:routines,id |
| exercise_id | foreignId | no | — | exists:exercises,id |
| order | integer | no | — | required, integer, min:1 |
| target_sets | integer | no | — | required, integer, min:1 |
| target_reps | integer | yes | null | integer, min:1 |
| target_rest_seconds | integer | yes | null | integer, min:0 |

### Constraints

- Both `routine_id` and `exercise_id` cascade on delete
- No timestamps (pivot-style; `id` primary key retained for direct reference)

### Relationships

- `belongsTo(Routine::class)`
- `belongsTo(Exercise::class)`

### Generator Defaults

When created by `RoutineGeneratorController`: `target_sets = 3`, `target_reps = 10`, `target_rest_seconds = 60`.

---

## Entity: `workout_sessions`

### Columns

| Column | Type | Nullable | Default | Validation |
|--------|------|----------|---------|------------|
| id | bigIncrements | — | — | |
| user_id | foreignId | no | — | exists:users,id |
| routine_id | foreignId | yes | null | exists:routines,id or null |
| started_at | datetime | no | `now()` | date |
| ended_at | datetime | yes | null | date, after:started_at |
| notes | text | yes | null | |
| created_at / updated_at | timestamps | — | — | |

### Constraints

- `routine_id` → `routines.id`, ON DELETE SET NULL (session survives routine deletion)

### Relationships

- `belongsTo(User::class)`
- `belongsTo(Routine::class)` (nullable)
- `hasMany(WorkoutLog::class)`

### State Transitions

```
created (ended_at = null) ──── POST /finish ───► finished (ended_at = now())
```

---

## Entity: `workout_logs`

### Columns

| Column | Type | Nullable | Default | Validation |
|--------|------|----------|---------|------------|
| id | bigIncrements | — | — | |
| workout_session_id | foreignId | no | — | exists:workout_sessions,id |
| exercise_id | foreignId | no | — | exists:exercises,id |
| set_number | integer | no | — | required, integer, min:1 |
| weight | decimal(5,2) | yes | null | numeric, min:0 |
| reps | integer | yes | null | integer, min:0 |
| duration_seconds | integer | yes | null | integer, min:0 |
| distance_km | decimal(5,2) | yes | null | numeric, min:0 |
| rpe | tinyInteger | yes | null | integer, in:1..10 |
| set_type | string(20) | no | `'normal'` | in:warmup,normal,drop,failure |
| created_at / updated_at | timestamps | — | — | |

### Constraints

- `workout_session_id` → `workout_sessions.id`, ON DELETE CASCADE
- `exercise_id` → `exercises.id`, ON DELETE CASCADE

### Relationships

- `belongsTo(WorkoutSession::class)`
- `belongsTo(Exercise::class)`

### Business Rules

- At least one of `weight`, `reps`, `duration_seconds`, or `distance_km` should be present (depends on exercise type)
- `set_type = 'warmup'` is **excluded** from all volume and personal record calculations

---

## Analytics Derivations (not stored entities)

### Volume

```
volume = SUM(weight * reps)
source  = workout_logs
          JOIN workout_sessions ON workout_session_id = workout_sessions.id
WHERE   workout_sessions.user_id = {auth}
  AND   workout_logs.set_type != 'warmup'
  AND   weight IS NOT NULL
  AND   reps IS NOT NULL
GROUP BY  YEARWEEK(started_at)   -- ?group_by=week (default)
       OR workout_session_id      -- ?group_by=session
```

### Personal Records

```
pr = MAX(weight)
source = workout_logs
         JOIN workout_sessions ON workout_session_id = workout_sessions.id
WHERE  workout_sessions.user_id = {auth}
  AND  workout_logs.set_type != 'warmup'
  AND  weight IS NOT NULL
  [AND workout_logs.exercise_id = {exercise_id}]  -- optional filter
GROUP BY workout_logs.exercise_id
```
