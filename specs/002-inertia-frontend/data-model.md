# Data Model: Workout Tracker Web Interface (V1)

**Branch**: `002-inertia-frontend` | **Date**: 2026-04-24

This document describes the **page-data contracts** (props passed from Laravel controllers to Vue pages via Inertia) and the **client-side reactive state** within each component. No new database tables are introduced by this feature — it reads from the same schema defined in `specs/001-workout-tracker-api/data-model.md`.

---

## Page: Dashboard

### Inertia Props (server → Vue)

| Prop | Type | Source | Notes |
|------|------|--------|-------|
| `latestSession` | `Object\|null` | `WorkoutSession` model | `started_at`, `ended_at`, `routine.name` |
| `weeklyVolume` | `Number` | DB aggregate | `SUM(weight * reps)` for current ISO week, warmup excluded |

### Client State

None — purely display page.

---

## Page: Profile/Edit

### Inertia Props

| Prop | Type | Source | Notes |
|------|------|--------|-------|
| `user` | `Object` | `auth()->user()` with media | Includes `name`, `display_name`, `unit_preference`, `avatar_url` |
| `bodyMetrics` | `Array` | `bodyMetrics()->latest('logged_at')->get()` | Each: `{ id, weight, height, body_fat_percentage, logged_at }` |

### Client State (useForm)

```js
// Profile update form
const profileForm = useForm({
  name: props.user.name,
  display_name: props.user.display_name,
  unit_preference: props.user.unit_preference,
})

// Avatar upload form
const avatarForm = useForm({ avatar: null }, { forceFormData: true })

// Body metric form
const metricForm = useForm({ logged_at: today(), weight: null })
```

---

## Page: Exercises/Index

### Inertia Props

| Prop | Type | Source | Notes |
|------|------|--------|-------|
| `exercises` | `LengthAwarePaginator` | Spatie QueryBuilder | 20 per page; each: `{ id, name, primary_muscle, sub_muscle_target, difficulty_level, equipment_required, demonstration_url }` |
| `muscleOptions` | `Array<string>` | Distinct `primary_muscle` values | For filter dropdown |
| `equipmentOptions` | `Array<string>` | Distinct `equipment_required` values | For filter dropdown |
| `filters` | `Object` | `$request->only('filter')` | Preserved filter state for controlled inputs |

### Client State

```js
const showCreateModal = ref(false)
const activeFilters = reactive({ 'filter[primary_muscle]': '', 'filter[equipment_required]': '' })
```

### Component: CreateModal

```js
const form = useForm({
  name: '',
  primary_muscle: '',
  sub_muscle_target: '',
  difficulty_level: '',
  description: '',
  equipment_required: '',
  demonstration: null,
}, { forceFormData: true })
```

---

## Page: Routines/Index

### Inertia Props

| Prop | Type | Source | Notes |
|------|------|--------|-------|
| `routines` | `Array` | `routines()->withCount('routineExercises')` | Each: `{ id, name, description, routine_exercises_count }` |

### Client State

```js
const showGeneratorModal = ref(false)
```

### Component: SmartGeneratorModal

```js
const form = useForm({ primary_muscle: '', difficulty_level: '' })
```

---

## Page: Routines/Builder

### Inertia Props

| Prop | Type | Source | Notes |
|------|------|--------|-------|
| `availableExercises` | `Array` | `Exercise::availableTo()` | Flat list: `{ id, name, primary_muscle }` |

### Client State

```js
const searchQuery = ref('')
const selectedExercises = ref([]) // [{ exercise_id, name, order, target_sets, target_reps }]
const filteredExercises = computed(() =>
  props.availableExercises.filter(e =>
    e.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
)
const form = useForm({
  name: '',
  exercises: computed(() => selectedExercises.value.map((e, i) => ({
    exercise_id: e.exercise_id,
    order: i + 1,
    target_sets: e.target_sets,
    target_reps: e.target_reps,
  }))),
})
```

---

## Page: Workouts/ActiveSession

### Inertia Props

| Prop | Type | Source | Notes |
|------|------|--------|-------|
| `session` | `Object` | `WorkoutSession` model | `{ id, started_at, ended_at, routine_id }` |
| `logsByExercise` | `Object` | PHP groupBy on eager-loaded logs | Keyed by `exercise_id`; each value: `{ exercise: {id, name}, sets: [{id, set_number, weight, reps, set_type, rpe}] }` |
| `routine` | `Object\|null` | `session->routine->load('routineExercises.exercise')` | Nullable; provides exercise order list |

### Client State

```js
// Set input form (one per exercise block, or single shared form)
const setForm = useForm({
  exercise_id: null,
  set_number: 1,
  weight: null,
  reps: null,
  set_type: 'normal',
  rpe: null,
})

// Unsaved state warning
const hasUnsavedInput = computed(() =>
  setForm.weight !== null || setForm.reps !== null
)
// Register beforeunload + Inertia before-navigate hooks when hasUnsavedInput is true
```

### Component: WorkoutTimer

| Prop | Type | Notes |
|------|------|-------|
| `startedAt` | `string` | ISO 8601 datetime from server |

```js
const elapsed = ref(0) // seconds
const timer = setInterval(() => {
  elapsed.value = Math.floor((Date.now() - new Date(props.startedAt)) / 1000)
}, 1000)
onUnmounted(() => clearInterval(timer))
// Display: HH:MM:SS computed from elapsed
```

### Component: RestTimer

| Prop | Type | Default | Notes |
|------|------|---------|-------|
| `defaultSeconds` | `number` | `60` | Configurable rest duration |

```js
const remaining = ref(props.defaultSeconds)
const running = ref(false)
let countdown = null
function start() { running.value = true; countdown = setInterval(() => { if (--remaining.value <= 0) stop() }, 1000) }
function stop() { clearInterval(countdown); running.value = false; remaining.value = props.defaultSeconds }
onUnmounted(() => clearInterval(countdown))
```

---

## Page: Analytics/Index

### Inertia Props

| Prop | Type | Source | Notes |
|------|------|--------|-------|
| `volumeData` | `Array` | `AnalyticsController@index` | 12 items: `{ label: 'Week 16 2026', value: 12450 }`; zero-filled for weeks with no data |
| `personalRecords` | `Array` | DB aggregate | `[{ exercise_id, exercise_name, max_weight }]` |

### Client State (chart config)

```js
const chartData = computed(() => ({
  labels: props.volumeData.map(d => d.label),
  datasets: [{ label: 'Weekly Volume (kg)', data: props.volumeData.map(d => d.value), backgroundColor: '#6366f1' }],
}))
const chartOptions = { responsive: true, plugins: { legend: { display: false } } }
const isEmpty = computed(() => props.volumeData.every(d => d.value === 0))
```
