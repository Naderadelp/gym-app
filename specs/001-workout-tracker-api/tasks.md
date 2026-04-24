# Tasks: Personal Workout Tracker API (V1)

**Input**: Design documents from `specs/001-workout-tracker-api/`
**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/api.md ✅
**Tests**: None — no Pest/PHPUnit tests per plan specification.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1–US5)

## Existing Project Context

> The project already has: `BaseController` (✅ ready), Spatie MediaLibrary migration (✅), Sanctum migration (✅), `Exercise` model/migration (needs refactoring), `WorkoutLog` model/migration (needs replacement), `WorkoutPlan` model/migration (separate concept — keep as-is).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Verify existing infrastructure is ready and establish the v1 route foundation.

- [X] T001 Confirm `BaseController` has `success()`, `error()`, `paginated()` in `app/Http/Controllers/BaseController.php` — already implemented, no changes needed (documentation checkpoint)
- [X] T002 Add `v1` prefix group with `auth:sanctum` middleware wrapper to `routes/api.php`; move existing authenticated routes inside or alongside the new group as appropriate

**Checkpoint**: v1 route group is ready to receive all new routes. `BaseController` confirmed.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Infrastructure that is shared across all user stories and must exist first.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [X] T003 Create migration to add `display_name` (string, nullable) and `unit_preference` (string, default `'metric'`) to the `users` table in `database/migrations/`
- [X] T004 [P] Verify Spatie MediaLibrary `HasMedia` and `InteractsWithMedia` traits can be applied to `User` model (media table migration already exists — no new migration needed)

**Checkpoint**: Foundation ready — user story phases can begin.

---

## Phase 3: User Story 1 — Profile & Body Metrics (Priority: P1) 🎯 MVP

**Goal**: Users can view and update their profile (including avatar upload) and log daily body metrics with upsert-per-day behaviour.

**Independent Test**: `GET /api/v1/profile` returns user + avatar URL + latest body metric; `POST /api/v1/body-metrics` twice on the same date returns one record; `DELETE /api/v1/body-metrics/{id}` removes it.

### Migrations

- [X] T005 [P] [US1] Create migration for `body_metrics` table: `id`, `user_id` (foreignId, constrained, cascadeOnDelete), `weight` (decimal 5,2, nullable), `height` (decimal 5,2, nullable), `body_fat_percentage` (decimal 4,2, nullable), `logged_at` (date), `timestamps`; add unique index on `(user_id, logged_at)` in `database/migrations/`

### Models

- [X] T006 [P] [US1] Update `app/Models/User.php`: implement `HasMedia`+`InteractsWithMedia`; add `registerMediaCollections()` with single `'avatar'` collection (`singleFile()`); add `display_name` and `unit_preference` to `$fillable`; add `hasMany(BodyMetric::class)`
- [X] T007 [P] [US1] Create `app/Models/BodyMetric.php`: `$fillable` = `[user_id, weight, height, body_fat_percentage, logged_at]`; cast `logged_at` to `date`; `belongsTo(User::class)`

### Form Requests

- [X] T008 [P] [US1] Create `app/Http/Requests/UpdateProfileRequest.php`: rules — `name` nullable string max:255, `display_name` nullable string max:255, `unit_preference` nullable in:metric,imperial
- [X] T009 [P] [US1] Create `app/Http/Requests/StoreBodyMetricRequest.php`: rules — `logged_at` required date, `weight` nullable numeric min:0, `height` nullable numeric min:0, `body_fat_percentage` nullable numeric min:0 max:99.99

### Controllers

- [X] T010 [US1] Create `app/Http/Controllers/ProfileController.php` extending `BaseController`:
  - `show()`: return `auth()->user()` with `getFirstMediaUrl('avatar')` and `latestBodyMetric` (`bodyMetrics()->latest('logged_at')->first()`) via `$this->success()`
  - `update(UpdateProfileRequest $request)`: `auth()->user()->update($request->validated())` via `$this->success()`
  - `uploadAvatar(Request $request)`: `auth()->user()->addMediaFromRequest('avatar')->toMediaCollection('avatar')` via `$this->success()`
- [X] T011 [US1] Create `app/Http/Controllers/BodyMetricController.php` extending `BaseController`:
  - `index()`: `QueryBuilder::for(BodyMetric::where('user_id', auth()->id()))` with `allowedSorts(['logged_at'])`; return `$this->paginated()`
  - `store(StoreBodyMetricRequest $request)`: `BodyMetric::updateOrCreate(['user_id' => auth()->id(), 'logged_at' => $request->logged_at], $request->validated())` via `$this->success()`
  - `destroy(BodyMetric $bodyMetric)`: authorize ownership; delete; `$this->success()`

### Routes

- [X] T012 [US1] Add to v1 group in `routes/api.php`: `GET /profile`, `PUT /profile`, `POST /profile/avatar`; `apiResource('body-metrics', BodyMetricController::class)->only(['index', 'store', 'destroy'])`

**Checkpoint**: US1 fully functional — profile + body metric CRUD independently testable.

---

## Phase 4: User Story 2 — Exercise Library (Priority: P2)

**Goal**: Users browse system-wide + own exercises with filtering, create custom exercises (with optional demo image), and cannot modify system exercises.

**Independent Test**: `GET /api/v1/exercises?filter[primary_muscle]=chest` returns only chest exercises visible to the user; `POST` creates a user-owned exercise; `PUT/DELETE` on a system exercise (user_id=null) returns 403.

### Migrations

- [X] T013 [P] [US2] Create migration to update `exercises` table: add `user_id` (foreignId nullable, constrained cascadeOnDelete), `primary_muscle` (string), `sub_muscle_target` (string), `difficulty_level` (tinyInteger), `equipment_required` (string nullable); drop `category` and `muscle_group` columns in `database/migrations/`

### Models

- [X] T014 [US2] Refactor `app/Models/Exercise.php`: update `$fillable` to `[user_id, name, description, primary_muscle, sub_muscle_target, difficulty_level, equipment_required]`; rename media collection from `'cover'` to `'demonstration'`; add `belongsTo(User::class)` (nullable); add `scopeAvailableTo($query, $userId)` → `$query->whereNull('user_id')->orWhere('user_id', $userId)`

### Policies

- [X] T015 [P] [US2] Create `app/Policies/ExercisePolicy.php`: `update()` and `delete()` return `!is_null($exercise->user_id) && $exercise->user_id === $user->id`; `view()` returns true (available exercises are already scoped at query level)

### Form Requests

- [X] T016 [P] [US2] Create `app/Http/Requests/StoreExerciseRequest.php`: `name` required string, `primary_muscle` required string, `sub_muscle_target` required string, `difficulty_level` required integer in:1,2,3, `description` nullable, `equipment_required` nullable string
- [X] T017 [P] [US2] Create `app/Http/Requests/UpdateExerciseRequest.php`: same rules as `StoreExerciseRequest` but all fields optional (sometimes_required)

### Controllers

- [X] T018 [US2] Refactor `app/Http/Controllers/ExerciseController.php` to extend `BaseController`:
  - `index()`: `QueryBuilder::for(Exercise::availableTo(auth()->id()))` with `allowedFilters(['primary_muscle', 'difficulty_level', 'equipment_required'])`; `$this->paginated()`
  - `store(StoreExerciseRequest $request)`: merge `user_id = auth()->id()`; attach `'demonstration'` image if file present; `$this->success()`
  - `show(Exercise $exercise)`: authorize via `ExercisePolicy`; `$this->success()`
  - `update(UpdateExerciseRequest $request, Exercise $exercise)`: `$this->authorize('update', $exercise)`; update; `$this->success()`
  - `destroy(Exercise $exercise)`: `$this->authorize('delete', $exercise)`; delete; `$this->success()`

### Routes

- [X] T019 [US2] Add `apiResource('exercises', ExerciseController::class)` to v1 group in `routes/api.php` (replace or confirm the existing exercises route is inside the v1 group)

**Checkpoint**: US2 independently testable — exercise library with ownership rules working.

---

## Phase 5: User Story 3 — Routines & Smart Generator (Priority: P3)

**Goal**: Users create/edit/delete named routines with ordered exercises, and generate a balanced routine automatically by targeting a muscle group and max difficulty.

**Independent Test**: `POST /api/v1/routines` with nested exercises creates pivot rows; `PUT` syncs the list (removes deleted exercises); `POST /api/v1/routines/generate` with `primary_muscle=chest&difficulty_level=2` creates a routine with one exercise per sub_muscle_target group.

### Migrations

- [X] T020 [P] [US3] Create migration for `routines` table: `id`, `user_id` (foreignId constrained cascadeOnDelete), `name` (string), `description` (text nullable), `timestamps` in `database/migrations/`
- [X] T021 [P] [US3] Create migration for `routine_exercises` table: `id`, `routine_id` (foreignId constrained cascadeOnDelete), `exercise_id` (foreignId constrained cascadeOnDelete), `order` (integer), `target_sets` (integer), `target_reps` (integer nullable), `target_rest_seconds` (integer nullable) — no timestamps in `database/migrations/`

### Models

- [X] T022 [P] [US3] Create `app/Models/Routine.php`: `$fillable` = `[user_id, name, description]`; `belongsTo(User::class)`; `hasMany(RoutineExercise::class)`
- [X] T023 [P] [US3] Create `app/Models/RoutineExercise.php`: `$fillable` = `[routine_id, exercise_id, order, target_sets, target_reps, target_rest_seconds]`; `belongsTo(Routine::class)`; `belongsTo(Exercise::class)`; `public $timestamps = false`

### Policies

- [X] T024 [P] [US3] Create `app/Policies/RoutinePolicy.php`: all mutations (`update`, `delete`) require `$routine->user_id === $user->id`

### Form Requests

- [X] T025 [P] [US3] Create `app/Http/Requests/StoreRoutineRequest.php`: `name` required string; `description` nullable; `exercises` required array; `exercises.*.exercise_id` required exists:exercises,id; `exercises.*.order` required integer min:1; `exercises.*.target_sets` required integer min:1; `exercises.*.target_reps` nullable integer; `exercises.*.target_rest_seconds` nullable integer
- [X] T026 [P] [US3] Create `app/Http/Requests/UpdateRoutineRequest.php`: same rules as `StoreRoutineRequest` but top-level fields optional
- [X] T027 [P] [US3] Create `app/Http/Requests/GenerateRoutineRequest.php`: `primary_muscle` required string; `difficulty_level` required integer in:1,2,3

### Controllers

- [X] T028 [US3] Create `app/Http/Controllers/RoutineController.php` extending `BaseController`:
  - `index()`: `auth()->user()->routines()->paginate()`; `$this->paginated()`
  - `store(StoreRoutineRequest $request)`: create `Routine`; delete existing pivot rows then insert each exercise from `$request->exercises` as `RoutineExercise`; `$this->success()`
  - `show(Routine $routine)`: load `routineExercises.exercise`; `$this->success()`
  - `update(UpdateRoutineRequest $request, Routine $routine)`: authorize; update routine; delete all pivot rows; reinsert from request; `$this->success()`
  - `destroy(Routine $routine)`: authorize; delete; `$this->success()`
- [X] T029 [US3] Create `app/Http/Controllers/RoutineGeneratorController.php` extending `BaseController`:
  - `generate(GenerateRoutineRequest $request)`: query `Exercise::availableTo(auth()->id())->where('primary_muscle', $request->primary_muscle)->where('difficulty_level', '<=', $request->difficulty_level)->get()`; group by `sub_muscle_target`; pick one random exercise per group (abort with 422 if empty); create `Routine` with auto-name; attach exercises via `RoutineExercise` with defaults `target_sets=3, target_reps=10, target_rest_seconds=60`; `$this->success()`

### Routes

- [X] T030 [US3] Add to v1 group in `routes/api.php`: `apiResource('routines', RoutineController::class)`; `Route::post('routines/generate', [RoutineGeneratorController::class, 'generate'])` — place the generate route BEFORE the apiResource to avoid route conflict with `{routine}` param

**Checkpoint**: US3 independently testable — routine CRUD and generator working.

---

## Phase 6: User Story 4 — Workout Sessions & Logging (Priority: P4)

**Goal**: Users start a session, log individual sets (weight, reps, RPE, set type), delete mistakes, and mark sessions finished. Session detail shows logs grouped by exercise.

**Independent Test**: Start session → log 3 sets for 2 exercises → delete one set → finish session → `GET /api/v1/workout-sessions/{id}` returns sets grouped by exercise with correct count.

### Migrations

- [X] T031 [P] [US4] Create migration for `workout_sessions` table: `id`, `user_id` (foreignId constrained cascadeOnDelete), `routine_id` (foreignId nullable constrained setNullOnDelete), `started_at` (datetime), `ended_at` (datetime nullable), `notes` (text nullable), `timestamps` in `database/migrations/`
- [X] T032 [P] [US4] Create migration to replace `workout_logs` table: drop existing and recreate with columns: `id`, `workout_session_id` (foreignId constrained cascadeOnDelete), `exercise_id` (foreignId constrained cascadeOnDelete), `set_number` (integer), `weight` (decimal 5,2 nullable), `reps` (integer nullable), `duration_seconds` (integer nullable), `distance_km` (decimal 5,2 nullable), `rpe` (tinyInteger nullable), `set_type` (string default `'normal'`), `timestamps` in `database/migrations/`

### Models

- [X] T033 [P] [US4] Create `app/Models/WorkoutSession.php`: `$fillable` = `[user_id, routine_id, started_at, ended_at, notes]`; casts `started_at` and `ended_at` to `datetime`; `belongsTo(User::class)`; `belongsTo(Routine::class)`; `hasMany(WorkoutLog::class)`
- [X] T034 [P] [US4] Refactor `app/Models/WorkoutLog.php`: update `$fillable` to `[workout_session_id, exercise_id, set_number, weight, reps, duration_seconds, distance_km, rpe, set_type]`; `belongsTo(WorkoutSession::class)`; `belongsTo(Exercise::class)`

### Policies

- [X] T035 [P] [US4] Create `app/Policies/WorkoutSessionPolicy.php`: all mutations require `$session->user_id === $user->id`
- [X] T036 [P] [US4] Create `app/Policies/WorkoutLogPolicy.php`: mutations require `$log->workoutSession->user_id === $user->id`

### Form Requests

- [X] T037 [P] [US4] Create `app/Http/Requests/StoreWorkoutSessionRequest.php`: `routine_id` nullable exists:routines,id; `started_at` nullable date (defaults to now in controller); `notes` nullable string
- [X] T038 [P] [US4] Create `app/Http/Requests/StoreWorkoutLogRequest.php`: `exercise_id` required exists:exercises,id; `set_number` required integer min:1; `weight` nullable numeric min:0; `reps` nullable integer min:0; `duration_seconds` nullable integer min:0; `distance_km` nullable numeric min:0; `rpe` nullable integer between:1,10; `set_type` nullable in:warmup,normal,drop,failure

### Controllers

- [X] T039 [US4] Create `app/Http/Controllers/WorkoutSessionController.php` extending `BaseController`:
  - `index()`: user's sessions paginated; `$this->paginated()`
  - `store(StoreWorkoutSessionRequest $request)`: create session with `started_at = $request->started_at ?? now()`; `$this->success()`
  - `show(WorkoutSession $session)`: authorize; load `workoutLogs.exercise`; group logs by `exercise_id` in response; `$this->success()`
  - `update(Request $request, WorkoutSession $session)`: authorize; update notes/routine_id; `$this->success()`
  - `destroy(WorkoutSession $session)`: authorize; delete; `$this->success()`
  - `finish(WorkoutSession $session)`: authorize; `$session->update(['ended_at' => now()])`; `$this->success()`
- [X] T040 [US4] Create `app/Http/Controllers/WorkoutLogController.php` extending `BaseController` (refactor existing file):
  - `store(StoreWorkoutLogRequest $request, WorkoutSession $session)`: authorize session ownership; create log tied to session; `$this->success()`
  - `destroy(WorkoutLog $log)`: authorize via `WorkoutLogPolicy`; delete; `$this->success()`

### Routes

- [X] T041 [US4] Add to v1 group in `routes/api.php`:
  - `apiResource('workout-sessions', WorkoutSessionController::class)`
  - `Route::post('workout-sessions/{session}/finish', [WorkoutSessionController::class, 'finish'])`
  - `Route::post('workout-sessions/{session}/logs', [WorkoutLogController::class, 'store'])`
  - `Route::delete('workout-logs/{log}', [WorkoutLogController::class, 'destroy'])`

**Checkpoint**: US4 independently testable — full session lifecycle and set logging working.

---

## Phase 7: User Story 5 — Progression & Analytics (Priority: P5)

**Goal**: Users see total training volume grouped by week or session, and personal record (max weight) per exercise — both always excluding warmup sets.

**Independent Test**: With known session history, `GET /api/v1/analytics/volume?group_by=week` returns correct totals excluding warmup sets; `GET /api/v1/analytics/personal-records?exercise_id=X` returns max non-warmup weight for that exercise.

### Controllers

- [X] T042 [US5] Create `app/Http/Controllers/AnalyticsController.php` extending `BaseController`:
  - `volume(Request $request)`: validate `group_by` in:week,session (default `week`); join `workout_logs` with `workout_sessions` on `workout_session_id`; filter `workout_sessions.user_id = auth()->id()`; filter `workout_logs.set_type != 'warmup'`; filter `weight IS NOT NULL AND reps IS NOT NULL`; compute `SUM(weight * reps)`:
    - `group_by=week`: `GROUP BY YEARWEEK(started_at)` → return `[{period, total_volume, session_count}]`
    - `group_by=session`: `GROUP BY workout_session_id` → return `[{session_id, started_at, total_volume}]`
  - `personalRecords(Request $request)`: validate `exercise_id` nullable exists:exercises,id; join as above; filter `weight IS NOT NULL`; `MAX(weight)` grouped by `exercise_id`; optionally filter by `exercise_id`; return `[{exercise_id, exercise_name, max_weight, achieved_at}]`; `$this->success()`

### Routes

- [X] T043 [US5] Add to v1 group in `routes/api.php`:
  - `Route::get('analytics/volume', [AnalyticsController::class, 'volume'])`
  - `Route::get('analytics/personal-records', [AnalyticsController::class, 'personalRecords'])`

**Checkpoint**: US5 independently testable — analytics returning correct warmup-excluded results.

---

## Phase 8: Polish & Cross-Cutting Concerns

- [X] T044 [P] Register all policies in `app/Providers/AuthServiceProvider.php` (or verify auto-discovery covers `ExercisePolicy`, `RoutinePolicy`, `WorkoutSessionPolicy`, `WorkoutLogPolicy`)
- [X] T045 [P] Validate all routes in `routes/api.php` match the endpoint contracts in `specs/001-workout-tracker-api/contracts/api.md` — confirm correct HTTP verbs, URI patterns, and controller bindings
- [X] T046 Review `routes/api.php` for old pre-v1 routes (`workout-plans`, `logs`, `admin`) — decide whether to keep them outside the v1 group or migrate/remove them per project needs

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 — BLOCKS all user stories
- **Phase 3 (US1)**: Depends on Phase 2 completion
- **Phase 4 (US2)**: Depends on Phase 2; exercises migration must exist before the ExercisePolicy can reference nullable `user_id`
- **Phase 5 (US3)**: Depends on Phase 4 (Exercise model's `scopeAvailableTo` used by generator)
- **Phase 6 (US4)**: Depends on Phase 5 (WorkoutSession references Routine; WorkoutLog references Exercise)
- **Phase 7 (US5)**: Depends on Phase 6 (analytics query workout_sessions + workout_logs)
- **Phase 8 (Polish)**: Depends on all story phases complete

### Within Each Phase

- Migrations → can be created in parallel (different files)
- Models → can be created in parallel (different files); must run AFTER migrations exist
- Form Requests → can be created in parallel with models (independent files)
- Controllers → depend on models + requests being complete
- Routes → depend on controllers being registered

### Parallel Opportunities Per Phase

```
Phase 3 (US1) parallel batch:
  T005 (migration)
  T006 (User model update)
  T007 (BodyMetric model)
  T008 (UpdateProfileRequest)
  T009 (StoreBodyMetricRequest)
→ then T010 (ProfileController) + T011 (BodyMetricController) in parallel
→ then T012 (routes)

Phase 4 (US2) parallel batch:
  T013 (exercises migration)
  T015 (ExercisePolicy)
  T016 (StoreExerciseRequest)
  T017 (UpdateExerciseRequest)
→ then T014 (Exercise model refactor — needs migration done)
→ then T018 (ExerciseController refactor — needs model + policy + requests)
→ then T019 (routes)

Phase 5 (US3) parallel batch:
  T020 + T021 (migrations)
  T022 + T023 (models)
  T024 (policy)
  T025 + T026 + T027 (requests)
→ then T028 + T029 in parallel (controllers)
→ then T030 (routes)

Phase 6 (US4) parallel batch:
  T031 + T032 (migrations)
  T033 + T034 (models)
  T035 + T036 (policies)
  T037 + T038 (requests)
→ then T039 + T040 in parallel (controllers)
→ then T041 (routes)
```

---

## Implementation Strategy

### MVP First (US1 Only)

1. Complete Phase 1 + Phase 2 (Setup + Foundation)
2. Complete Phase 3 (US1 — Profile & Body Metrics)
3. **STOP and validate**: profile + body metrics API working end-to-end
4. Deploy/demo

### Incremental Delivery

1. Setup + Foundation → all blocked stories unlocked
2. US1 → profile + body metrics MVP
3. US2 → exercise library (blocks US3 generator)
4. US3 → routines + generator
5. US4 → live workout logging
6. US5 → analytics dashboard

Each phase adds value without breaking prior phases.

---

## Task Summary

| Phase | Tasks | Parallel Opportunities |
|-------|-------|----------------------|
| Phase 1 — Setup | T001–T002 | 1 |
| Phase 2 — Foundation | T003–T004 | 1 |
| Phase 3 — US1 (Profile) | T005–T012 | 5 |
| Phase 4 — US2 (Exercises) | T013–T019 | 4 |
| Phase 5 — US3 (Routines) | T020–T030 | 7 |
| Phase 6 — US4 (Sessions) | T031–T041 | 7 |
| Phase 7 — US5 (Analytics) | T042–T043 | 0 |
| Phase 8 — Polish | T044–T046 | 2 |
| **Total** | **46 tasks** | **27** |

---

## Notes

- No test tasks generated — plan explicitly excludes Pest/PHPUnit tests
- T032 replaces the existing `workout_logs` table (old schema is incompatible); handle with a drop+recreate migration if no production data exists
- T013 alters the existing `exercises` table; old `category` and `muscle_group` columns are removed
- All controllers must extend `BaseController` and use only `$this->success()` / `$this->error()` / `$this->paginated()`
- Mark task complete and commit after each phase checkpoint before starting the next
