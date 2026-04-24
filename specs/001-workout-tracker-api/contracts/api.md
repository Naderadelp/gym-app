# API Contracts: Personal Workout Tracker (V1)

**Base path**: `/api/v1`
**Auth**: All endpoints require `Authorization: Bearer {token}` (Sanctum)
**Content-Type**: `application/json`
**Response envelope** (all responses via `BaseController`):

```json
// success
{ "success": true, "data": { ... }, "message": "..." }

// paginated
{ "success": true, "data": [...], "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 42 } }

// error
{ "success": false, "message": "...", "errors": { ... } }
```

---

## Phase 1 — Profile & Body Metrics

### GET /profile

Returns authenticated user's profile, avatar URL, and latest body metric.

**Response `data`**:
```json
{
  "id": 1,
  "name": "John Doe",
  "display_name": "JohnFit",
  "email": "john@example.com",
  "unit_preference": "metric",
  "avatar_url": "https://example.com/media/1/avatar/avatar.jpg",
  "latest_body_metric": {
    "id": 12,
    "weight": 82.50,
    "height": 178.00,
    "body_fat_percentage": 18.50,
    "logged_at": "2026-04-24"
  }
}
```

---

### PUT /profile

**Request body**:
```json
{
  "name": "John Doe",
  "display_name": "JohnFit",
  "unit_preference": "imperial"
}
```
All fields optional. `unit_preference` must be `metric` or `imperial`.

**Response `data`**: Same shape as GET /profile.

---

### POST /profile/avatar

**Request**: `multipart/form-data` with `avatar` file field.

**Response `data`**:
```json
{ "avatar_url": "https://example.com/media/1/avatar/avatar.jpg" }
```

---

### GET /body-metrics

**Query params**: `sort=logged_at` (or `-logged_at` for desc), `page`, `per_page`

**Response** (paginated):
```json
{
  "id": 1,
  "weight": 82.50,
  "height": 178.00,
  "body_fat_percentage": 18.50,
  "logged_at": "2026-04-24",
  "created_at": "2026-04-24T10:00:00Z"
}
```

---

### POST /body-metrics

**Request body**:
```json
{
  "logged_at": "2026-04-24",
  "weight": 82.50,
  "height": 178.00,
  "body_fat_percentage": 18.50
}
```
`logged_at` required. At least one metric field expected. Creates or updates the entry for that date.

**Response `data`**: Single body metric object.

---

### DELETE /body-metrics/{id}

**Response**: `{ "success": true, "message": "Deleted." }`

---

## Phase 2 — Exercise Library

### GET /exercises

**Query params**:
- `filter[primary_muscle]=chest`
- `filter[difficulty_level]=2`
- `filter[equipment_required]=barbell`
- `sort=name`, `page`, `per_page`

**Response** (paginated):
```json
{
  "id": 5,
  "user_id": null,
  "name": "Bench Press",
  "description": "Horizontal push movement.",
  "primary_muscle": "chest",
  "sub_muscle_target": "pectoralis_major",
  "difficulty_level": 2,
  "equipment_required": "barbell",
  "demonstration_url": null
}
```

---

### POST /exercises

**Request**: `multipart/form-data` or `application/json` (no image) or `multipart/form-data` (with image).

```json
{
  "name": "Cable Fly",
  "description": "Isolation chest movement.",
  "primary_muscle": "chest",
  "sub_muscle_target": "pectoralis_minor",
  "difficulty_level": 1,
  "equipment_required": "cable_machine"
}
```
`demonstration` — optional file field.

**Response `data`**: Single exercise object (with `user_id` set to auth user).

---

### GET /exercises/{id}

**Response `data`**: Single exercise object.

---

### PUT /exercises/{id}

Same body as POST. Only allowed for user-owned exercises (`user_id = auth()->id()`).

**Error (403)**:
```json
{ "success": false, "message": "Cannot modify system exercises." }
```

---

### DELETE /exercises/{id}

Only allowed for user-owned exercises.

---

## Phase 3 — Routines & Generator

### GET /routines

**Response** (paginated): Array of routine summaries.
```json
{
  "id": 3,
  "name": "Push Day A",
  "description": "Chest, shoulders, triceps.",
  "exercises_count": 5,
  "created_at": "2026-04-24T10:00:00Z"
}
```

---

### POST /routines

**Request body**:
```json
{
  "name": "Push Day A",
  "description": "Optional description.",
  "exercises": [
    { "exercise_id": 5, "order": 1, "target_sets": 4, "target_reps": 8, "target_rest_seconds": 90 },
    { "exercise_id": 12, "order": 2, "target_sets": 3, "target_reps": 12, "target_rest_seconds": 60 }
  ]
}
```

**Response `data`**: Full routine with nested `exercises` array.

---

### GET /routines/{id}

**Response `data`**:
```json
{
  "id": 3,
  "name": "Push Day A",
  "description": "...",
  "exercises": [
    {
      "id": 5,
      "name": "Bench Press",
      "order": 1,
      "target_sets": 4,
      "target_reps": 8,
      "target_rest_seconds": 90
    }
  ]
}
```

---

### PUT /routines/{id}

Same body as POST. Full exercise list replaces existing pivot rows.

---

### DELETE /routines/{id}

---

### POST /routines/generate

**Request body**:
```json
{
  "primary_muscle": "chest",
  "difficulty_level": 2
}
```
- `primary_muscle`: required, string
- `difficulty_level`: required, integer 1–3 (generator selects exercises with `difficulty_level <= input`)

**Response `data`**: Newly created routine (same shape as GET /routines/{id}) with auto-generated name and default target values (3 sets × 10 reps × 60s rest).

**Error (422)** when no eligible exercises found:
```json
{ "success": false, "message": "No exercises found for the given criteria." }
```

---

## Phase 4 — Workout Sessions & Logs

### GET /workout-sessions

**Response** (paginated):
```json
{
  "id": 7,
  "routine_id": 3,
  "started_at": "2026-04-24T08:00:00Z",
  "ended_at": null,
  "notes": null
}
```

---

### POST /workout-sessions

**Request body**:
```json
{
  "routine_id": 3,
  "started_at": "2026-04-24T08:00:00Z",
  "notes": "Morning session"
}
```
`routine_id` and `notes` optional. `started_at` defaults to `now()`.

---

### GET /workout-sessions/{id}

**Response `data`** — session with logs grouped by exercise:
```json
{
  "id": 7,
  "started_at": "2026-04-24T08:00:00Z",
  "ended_at": null,
  "exercises": [
    {
      "exercise_id": 5,
      "exercise_name": "Bench Press",
      "sets": [
        { "id": 101, "set_number": 1, "weight": 60.0, "reps": 10, "set_type": "warmup", "rpe": 5 },
        { "id": 102, "set_number": 2, "weight": 80.0, "reps": 8,  "set_type": "normal", "rpe": 7 }
      ]
    }
  ]
}
```

---

### PUT /workout-sessions/{id}

Updates `notes` and/or `routine_id`.

---

### DELETE /workout-sessions/{id}

---

### POST /workout-sessions/{id}/finish

No request body required.

**Response `data`**: Updated session with `ended_at` set.

---

### POST /workout-sessions/{id}/logs

**Request body**:
```json
{
  "exercise_id": 5,
  "set_number": 1,
  "weight": 80.0,
  "reps": 8,
  "duration_seconds": null,
  "distance_km": null,
  "rpe": 7,
  "set_type": "normal"
}
```
`exercise_id`, `set_number` required. `set_type` defaults to `'normal'`.

**Response `data`**: Created workout log object.

---

### DELETE /workout-logs/{id}

---

## Phase 5 — Analytics

### GET /analytics/volume

**Query params**:
- `group_by=week` (default) or `group_by=session`

**Response `data`** (group_by=week):
```json
[
  { "period": "2026-W16", "total_volume": 12450.00, "session_count": 3 },
  { "period": "2026-W15", "total_volume": 10200.00, "session_count": 2 }
]
```

**Response `data`** (group_by=session):
```json
[
  { "session_id": 7, "started_at": "2026-04-24T08:00:00Z", "total_volume": 4200.00 },
  { "session_id": 6, "started_at": "2026-04-22T07:30:00Z", "total_volume": 3800.00 }
]
```

Warmup sets always excluded.

---

### GET /analytics/personal-records

**Query params**: `exercise_id=5` (optional)

**Response `data`** (all exercises):
```json
[
  { "exercise_id": 5, "exercise_name": "Bench Press", "max_weight": 100.00, "achieved_at": "2026-04-20T08:00:00Z" },
  { "exercise_id": 12, "exercise_name": "Squat", "max_weight": 140.00, "achieved_at": "2026-04-15T08:00:00Z" }
]
```

**Response `data`** (single exercise):
```json
{ "exercise_id": 5, "exercise_name": "Bench Press", "max_weight": 100.00, "achieved_at": "2026-04-20T08:00:00Z" }
```

Warmup sets always excluded.
