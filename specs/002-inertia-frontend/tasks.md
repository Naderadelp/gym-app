# Tasks: Workout Tracker Web Interface (V1)

**Input**: Design documents from `specs/002-inertia-frontend/`
**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/web-routes.md ✅
**Tests**: None — no Pest, PHPUnit, or browser tests per plan specification.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1–US5)

## Existing Project Context

> **Installed**: Tailwind CSS v4 (via `@tailwindcss/vite`), Vite, Laravel 13, PHP 8.3.
> **Not yet installed**: Inertia.js (Laravel adapter), `@inertiajs/vue3`, Vue 3, `@vitejs/plugin-vue`, `chart.js`, `vue-chartjs`.
> **app.js**: Currently empty — needs full Inertia bootstrap.
> **Web auth**: Existing auth is API-only (Sanctum tokens). Web session auth pages (login/register) must be created in Foundation phase.
> **No Pages directory**: `resources/js/Pages/` does not exist yet.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Install all dependencies and wire up the Inertia + Vue 3 pipeline.

- [ ] T001 Install Inertia Laravel adapter: `composer require inertiajs/inertia-laravel`
- [ ] T002 Install frontend packages: `npm install @inertiajs/vue3 vue @vitejs/plugin-vue chart.js vue-chartjs`
- [ ] T003 [P] Update `vite.config.js`: import and add `vue()` plugin from `@vitejs/plugin-vue`; add `resources/js/app.js` as Vite entry if not already present
- [ ] T004 [P] Create `resources/views/app.blade.php`: Inertia root template — include `@viteReactRefresh` equivalent (`@vite(['resources/css/app.css', 'resources/js/app.js'])`), `@inertiaHead`, `@inertia` directives
- [ ] T005 Bootstrap `resources/js/app.js` with `createInertiaApp`: import `createApp` from Vue, `InertiaProgress`, resolve pages from `Pages/` glob, mount to `#app`

**Checkpoint**: `npm run dev` compiles without errors; visiting `/` loads via Vite.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Inertia middleware, shared layout, and web auth pages. All user stories depend on these.

**⚠️ CRITICAL**: No user story implementation can begin until this phase is complete.

- [ ] T006 Register `HandleInertiaRequests` middleware in `bootstrap/app.php` (add to the `web` middleware group)
- [ ] T007 [P] Create shared layout `resources/js/Layouts/AppLayout.vue`: top navigation bar (Tailwind-styled) with placeholder links, slot for page content, flash message display (Inertia shared `flash` prop)
- [ ] T008 [P] Create `app/Http/Controllers/Web/Auth/LoginController.php`: `showLogin()` returns `Inertia::render('Auth/Login')`; `login(Request $request)` validates credentials, calls `Auth::attempt()`, redirects to `dashboard` on success
- [ ] T009 [P] Create `resources/js/Pages/Auth/Login.vue`: email + password form using `useForm()`, submits to `POST /login`, displays validation errors
- [ ] T010 [P] Create `app/Http/Controllers/Web/Auth/RegisterController.php`: `showRegister()` returns `Inertia::render('Auth/Register')`; `register()` validates, creates user, logs in, redirects to `dashboard`
- [ ] T011 [P] Create `resources/js/Pages/Auth/Register.vue`: name + email + password + confirm form, submits to `POST /register`
- [ ] T012 Add auth and logout routes to `routes/web.php`: `GET /login` → `LoginController@showLogin`; `POST /login` → `LoginController@login`; `GET /register` → `RegisterController@showRegister`; `POST /register` → `RegisterController@register`; `POST /logout` → `Auth::logout()` + redirect; wrap all existing routes + future routes with `auth` middleware group

**Checkpoint**: Can register, login, and see a blank dashboard page at `/dashboard` without errors.

---

## Phase 3: User Story 1 — Dashboard & Profile (Priority: P1) 🎯 MVP

**Goal**: After login, users see their weekly volume + latest workout. From the profile page they update their info, upload an avatar, and log body metrics.

**Independent Test**: Login → land on `/dashboard` showing weekly volume stat and latest session info → navigate to `/profile` → update display name → upload avatar photo → log today's weight → see new metric entry in the history table.

### Controllers

- [ ] T013 [P] [US1] Create `app/Http/Controllers/Web/DashboardController.php`: `index()` queries `auth()->user()->workoutSessions()->latest('started_at')->first()`; computes weekly volume (`SUM(weight*reps)` from `workout_logs` via sessions, exclude warmup, current ISO week); returns `Inertia::render('Dashboard', ['latestSession' => ..., 'weeklyVolume' => ...])`
- [ ] T014 [P] [US1] Create `app/Http/Controllers/Web/ProfileController.php`: `edit()` returns `Inertia::render('Profile/Edit', ['user' => auth()->user()->load('media'), 'bodyMetrics' => ...latest...])`; `update()` validates and calls `auth()->user()->update($validated)` then `Redirect::route('profile.edit')`; `uploadAvatar()` adds media to `'avatar'` collection then `Redirect::back()`
- [ ] T015 [P] [US1] Create `app/Http/Controllers/Web/BodyMetricController.php`: `store()` validates `logged_at` + `weight`, calls `BodyMetric::updateOrCreate(...)`, `Redirect::back()`

### Vue Pages

- [ ] T016 [US1] Create `resources/js/Pages/Dashboard.vue`: uses `AppLayout`; displays `weeklyVolume` stat card and `latestSession` info; "Start Empty Workout" button submits `POST /workouts/start` via `useForm()`; zero state for new users
- [ ] T017 [US1] Create `resources/js/Pages/Profile/Edit.vue`: uses `AppLayout`; profile update `useForm({name, display_name, unit_preference})` → `PUT /profile`; avatar upload `useForm({avatar: null}, {forceFormData: true})` → `POST /profile/avatar`; body metric inline form `useForm({logged_at, weight})` → `POST /body-metrics`; history table sorted newest-first

### Routes

- [ ] T018 [US1] Add dashboard and profile routes to `routes/web.php` inside the `auth` middleware group: `GET /dashboard` (named `dashboard`); `GET /profile`, `PUT /profile`, `POST /profile/avatar`; `POST /body-metrics`

**Checkpoint**: US1 fully functional — profile, avatar upload, and body metric logging work end-to-end.

---

## Phase 4: User Story 2 — Exercise Library (Priority: P2)

**Goal**: Visual exercise grid with muscle/equipment filters, and a modal to create custom exercises with optional demo image — all without leaving the page.

**Independent Test**: Open `/exercises` → filter by `primary_muscle=chest` → only chest exercises show → click "Add Exercise" → fill form with demo image → submit → new card appears in grid without page navigation.

### Controllers

- [ ] T019 [P] [US2] Create `app/Http/Controllers/Web/ExerciseController.php`: `index()` uses Spatie QueryBuilder with `availableTo(auth()->id())`, allowed filters `primary_muscle` + `equipment_required`, paginate 20; also passes `muscleOptions`, `equipmentOptions`, and `filters`; returns `Inertia::render('Exercises/Index', [...])`; `store()` validates, creates exercise with `user_id = auth()->id()`, attaches demo image if present, `Redirect::route('exercises.index')`

### Vue Pages

- [ ] T020 [US2] Create `resources/js/Pages/Exercises/Index.vue`: uses `AppLayout`; filter bar with two `<select>` dropdowns bound to `router.get(route('exercises.index'), filters, {preserveState: true})`; responsive grid of exercise cards (name, primary_muscle badge, difficulty 1-3 indicator, demo image if present); "Add Exercise" button → `v-if` toggle of `CreateModal`; pagination links
- [ ] T021 [P] [US2] Create `resources/js/Pages/Exercises/CreateModal.vue`: `useForm({name, primary_muscle, sub_muscle_target, difficulty_level, description, equipment_required, demonstration: null}, {forceFormData: true})`; `form.post(route('exercises.store'), {onSuccess: () => emit('close')})`; file input for demonstration image; difficulty dropdown (1, 2, 3)

### Routes

- [ ] T022 [US2] Add exercise routes to `routes/web.php`: `GET /exercises` (named `exercises.index`); `POST /exercises` (named `exercises.store`)

**Checkpoint**: US2 independently functional — exercise browse + filter + create working.

---

## Phase 5: User Story 3 — Routines & Generator (Priority: P3)

**Goal**: Users view and manage saved routines, build new ones with an exercise search form, or generate a smart routine via modal.

**Independent Test**: Open `/routines` → click "Generate Smart Workout" → select muscle + difficulty → get redirected to new routine → go back to list → click "Create Routine" → search for exercises → add 3 → set target values → save → routine appears in list.

### Controllers

- [ ] T023 [P] [US3] Create `app/Http/Controllers/Web/RoutineController.php` with all 7 methods:
  - `index()`: `auth()->user()->routines()->withCount('routineExercises')->latest()->get()` → `Inertia::render('Routines/Index', [...])`
  - `create()`: `Exercise::availableTo(auth()->id())->get(['id','name','primary_muscle'])` → `Inertia::render('Routines/Builder', [...])`
  - `store()`: validate `name` + `exercises[]`; create Routine; delete+reinsert pivot rows; `Redirect::route('routines.index')`
  - `show(Routine $routine)`: load exercises; `Inertia::render('Routines/Builder', ['routine' => $routine, 'availableExercises' => ...])`
  - `update()`: authorize + same pivot sync as store; `Redirect::route('routines.index')`
  - `destroy()`: authorize + delete; `Redirect::route('routines.index')`
  - `generate()`: validate `primary_muscle` + `difficulty_level`; run generator logic (query `availableTo`, filter, group by `sub_muscle_target`, pick 1 random per group); create Routine + attach; `Redirect::route('routines.show', $routine)` or `Redirect::back()->withErrors(['message' => '...'])` if empty

### Vue Pages

- [ ] T024 [US3] Create `resources/js/Pages/Routines/Index.vue`: uses `AppLayout`; cards with routine name, exercise count, "Start" button (→ `POST /workouts/start` with `routine_id`) and "Edit" link; "Generate Smart Workout" button → `showGeneratorModal = true`; "Create Routine" link → `route('routines.create')`; include `SmartGeneratorModal`
- [ ] T025 [P] [US3] Create `resources/js/Pages/Routines/SmartGeneratorModal.vue`: `useForm({primary_muscle: '', difficulty_level: ''}).post(route('routines.generate'))`; `<select>` for muscle (pass `muscleOptions` as prop or hardcode); `<select>` for difficulty 1/2/3; display flash error inline if no exercises found
- [ ] T026 [US3] Create `resources/js/Pages/Routines/Builder.vue`: receives `availableExercises` + optional `routine` (edit mode); `searchQuery` ref + computed filtered list; click exercise → push to `selectedExercises` with defaults `{target_sets:3, target_reps:10}`; each selected row shows name, target_sets input, target_reps input, remove button; submit via `useForm({name, exercises: [...]}).post/put`

### Routes

- [ ] T027 [US3] Add routine routes to `routes/web.php`: `POST /routines/generate` (named `routines.generate`) declared **before** `Route::resource('routines', ...)`; full `Route::resource('routines', Web\RoutineController::class)`

**Checkpoint**: US3 independently functional — routine CRUD and smart generator working.

---

## Phase 6: User Story 4 — Live Workout Tracker (Priority: P4)

**Goal**: Real-time session tracking with running timer, set logging without page reload, rest countdown, and finish action.

**Independent Test**: Start session → timer counts up → log 3 sets for 2 exercises (one warmup) → delete one set → start rest timer after completing a set → finish session → redirected to dashboard.

### Controllers

- [ ] T028 [P] [US4] Create `app/Http/Controllers/Web/WorkoutSessionController.php`:
  - `create(Request $request)`: `WorkoutSession::create(['user_id' => auth()->id(), 'started_at' => now(), 'routine_id' => $request->routine_id])` → `Redirect::route('workouts.show', $session)`
  - `show(WorkoutSession $session)`: authorize ownership; eager load `routine.routineExercises.exercise`, `workoutLogs.exercise`; group logs by `exercise_id` in PHP; `Inertia::render('Workouts/ActiveSession', ['session'=>..., 'logsByExercise'=>..., 'routine'=>...])`
  - `finish(WorkoutSession $session)`: authorize; `$session->update(['ended_at'=>now()])` → `Redirect::route('dashboard')`
- [ ] T029 [P] [US4] Create `app/Http/Controllers/Web/WorkoutLogController.php`:
  - `store(Request $request, WorkoutSession $session)`: authorize session ownership; validate set data; `WorkoutLog::create([..., 'workout_session_id'=>$session->id])` → `Redirect::back()`
  - `destroy(WorkoutLog $log)`: authorize via `WorkoutLogPolicy`; delete → `Redirect::back()`

### Vue Components

- [ ] T030 [P] [US4] Create `resources/js/Pages/Workouts/components/WorkoutTimer.vue`: receives `startedAt` (ISO string) prop; `setInterval` every 1s computing `elapsed = Math.floor((Date.now() - new Date(startedAt)) / 1000)`; displays as `HH:MM:SS` computed from elapsed; `onUnmounted` clears interval
- [ ] T031 [P] [US4] Create `resources/js/Pages/Workouts/components/RestTimer.vue`: receives `defaultSeconds` prop (default 60); `remaining` ref, `running` ref; "Start Rest" begins countdown via `setInterval`; shows remaining seconds; "Cancel" resets; `onUnmounted` clears interval

### Vue Page

- [ ] T032 [US4] Create `resources/js/Pages/Workouts/ActiveSession.vue`:
  - Header: `<WorkoutTimer :started-at="session.started_at" />`; "Finish Workout" button → `router.post(route('workouts.finish', session.id))`
  - Exercise blocks: iterate over `routine.routineExercises` (if from routine) or unique exercises in `logsByExercise`; each block shows exercise name + set rows
  - Set input form per exercise: `weight`, `reps` inputs, `set_type` select (warmup/normal/drop/failure); "Log Set" button → `router.post(route('workout-logs.store.web', session.id), formData, {preserveScroll: true, only: ['logsByExercise']})`
  - Logged set rows: weight, reps, set type badge; delete button → `router.delete(route('workout-logs.destroy.web', log.id), {preserveScroll: true, only: ['logsByExercise']})`
  - Rest section: `<RestTimer />` component per exercise block
  - Unsaved-input warning: `beforeunload` + Inertia `router.on('before', ...)` when weight/reps input has unsaved value

### Routes

- [ ] T033 [US4] Add workout routes to `routes/web.php`: `POST /workouts/start` (named `workouts.start`); `GET /workouts/{session}` (named `workouts.show`); `POST /workouts/{session}/finish` (named `workouts.finish`); `POST /workouts/{session}/logs` (named `workout-logs.store.web`); `DELETE /workout-logs/{log}` (named `workout-logs.destroy.web`)

**Checkpoint**: US4 independently functional — full live tracking session working end-to-end.

---

## Phase 7: User Story 5 — Analytics Dashboard (Priority: P5)

**Goal**: Weekly volume bar chart (last 12 weeks) and personal records table, both excluding warmup sets.

**Independent Test**: Navigate to `/analytics` → volume chart shows 12 weekly bars (zero bars for weeks with no data) → PR table lists each exercise with max non-warmup weight → user with no data sees empty state.

### Controller

- [ ] T034 [P] [US5] Create `app/Http/Controllers/Web/AnalyticsController.php`: `index()` computes last-12-weeks volume (same SQL as API phase 5 but formatted as `[{label: 'Week X', value: N}]` with zero-fill for missing weeks); computes PR per exercise (MAX weight, exclude warmup); returns `Inertia::render('Analytics/Index', ['volumeData'=>..., 'personalRecords'=>...])`

### Vue Page

- [ ] T035 [US5] Create `resources/js/Pages/Analytics/Index.vue`: uses `AppLayout`; `<Bar :data="chartData" :options="chartOptions" />` from `vue-chartjs`; `chartData` computed from `volumeData` prop (labels + dataset); PR table with exercise name + max weight columns; `isEmpty` computed — show "No data yet" message when all volume values are zero

### Routes

- [ ] T036 [US5] Add analytics route to `routes/web.php`: `GET /analytics` → `Web\AnalyticsController@index` (named `analytics.index`)

**Checkpoint**: US5 independently functional — charts and PR table rendering with correct warmup exclusion.

---

## Phase 8: Polish & Cross-Cutting Concerns

- [ ] T037 [P] Update `resources/js/Layouts/AppLayout.vue` with nav links for all sections: Dashboard, Exercises, Routines, Analytics, Profile; active-link highlighting using Inertia `usePage().url`
- [ ] T038 [P] Add zero/empty states to `Dashboard.vue` (new user with no sessions) and `Routines/Index.vue` (no routines yet) with helpful prompt messages and call-to-action links
- [ ] T039 Verify all routes in `routes/web.php` match `specs/002-inertia-frontend/contracts/web-routes.md` — confirm named routes, HTTP verbs, and controller bindings are correct
- [ ] T040 [P] Share flash message data via `HandleInertiaRequests::share()` so success/error flash messages are available in `AppLayout.vue` across all pages

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — must be first; Vite must compile before anything else runs
- **Phase 2 (Foundational)**: Depends on Phase 1 — BLOCKS all user stories; Inertia middleware + web auth must exist
- **Phase 3 (US1)**: Depends on Phase 2 — controllers use `BodyMetric` model from feature 001
- **Phase 4 (US2)**: Depends on Phase 2 — `ExerciseController` uses `Exercise::availableTo()` scope from feature 001
- **Phase 5 (US3)**: Depends on Phase 4 — `RoutineController@generate` uses `Exercise::availableTo()` + `Routine`/`RoutineExercise` models from feature 001
- **Phase 6 (US4)**: Depends on Phase 5 — `WorkoutSessionController@show` loads `routine.routineExercises`; depends on `WorkoutSession`/`WorkoutLog` models from feature 001
- **Phase 7 (US5)**: Depends on Phase 6 — analytics queries `workout_sessions` + `workout_logs`
- **Phase 8 (Polish)**: Depends on all story phases complete

### Cross-Feature Dependency

All web controllers depend on the models introduced by **feature 001** (`specs/001-workout-tracker-api`). Ensure feature 001 database migrations have been run before testing any frontend route.

### Within Each Phase (Execution Order)

- Controller tasks [P] → can be written in parallel with each other and with Vue components
- Vue page tasks → can be written in parallel with controllers (different files)
- Routes → must come last in each phase (controllers must exist first)

### Parallel Opportunities Per Phase

```
Phase 1 parallel batch: T003 + T004 (after T001+T002 complete)
→ then T005

Phase 2 parallel batch: T007 + T008 + T009 + T010 + T011 (after T006)
→ then T012

Phase 3 parallel batch: T013 + T014 + T015 (controllers)
             parallel: T016 + T017 (Vue pages — independent files)
→ then T018 (routes)

Phase 4 parallel batch: T019 (controller)
             parallel: T020 + T021 (Vue page + modal — independent)
→ then T022 (routes)

Phase 5 parallel batch: T023 (controller) + T025 + T026 (Vue pages)
             parallel: T024 (Index.vue — no dependencies on controller)
→ then T027 (routes)

Phase 6 parallel batch: T028 + T029 (controllers)
             parallel: T030 + T031 (timer components)
→ then T032 (ActiveSession.vue — needs timer components done)
→ then T033 (routes)
```

---

## Implementation Strategy

### MVP First (US1 Only)

1. Complete Phase 1 (Setup) + Phase 2 (Foundation)
2. Complete Phase 3 (US1 — Dashboard & Profile)
3. **STOP and validate**: auth → dashboard → profile → body metric logging all work
4. Demo to validate Inertia pipeline is correctly wired before investing in more pages

### Incremental Delivery

1. Setup + Foundation → Inertia pipeline live, auth working
2. US1 → dashboard + profile MVP (validates data flow from backend to Vue)
3. US2 → exercise library (validates Spatie QueryBuilder + MediaLibrary via web)
4. US3 → routine builder + generator
5. US4 → live workout tracker (most complex; validates partial reloads)
6. US5 → analytics charts

---

## Task Summary

| Phase | Tasks | Parallel Opportunities |
|-------|-------|----------------------|
| Phase 1 — Setup | T001–T005 | 2 |
| Phase 2 — Foundation | T006–T012 | 5 |
| Phase 3 — US1 (Dashboard) | T013–T018 | 4 |
| Phase 4 — US2 (Exercises) | T019–T022 | 2 |
| Phase 5 — US3 (Routines) | T023–T027 | 3 |
| Phase 6 — US4 (Sessions) | T028–T033 | 4 |
| Phase 7 — US5 (Analytics) | T034–T036 | 1 |
| Phase 8 — Polish | T037–T040 | 2 |
| **Total** | **40 tasks** | **23** |

---

## Notes

- No test tasks — plan explicitly excludes all testing
- **Critical setup order**: T001 (composer) and T002 (npm) must both complete before T003–T005
- Feature 001 (`001-workout-tracker-api`) database migrations must be run before any web route can return data
- `routines/generate` route must be registered **before** `Route::resource('routines', ...)` to avoid Laravel matching `generate` as a `{routine}` wildcard
- The `only: ['logsByExercise']` Inertia partial reload in T032 requires the controller to return `logsByExercise` as a deferred prop — verify `WorkoutSessionController@show` prop naming matches
- Tailwind CSS v4 is already installed via `@tailwindcss/vite`; do not reinstall it
