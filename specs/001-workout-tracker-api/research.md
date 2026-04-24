# Research: Personal Workout Tracker API (V1)

**Branch**: `001-workout-tracker-api` | **Date**: 2026-04-24

All technical decisions were deterministic from the plan — no external research required. Decisions documented below for traceability.

---

## Decision 1: Authentication Strategy

**Decision**: Laravel Sanctum token-based authentication for all API routes.
**Rationale**: Plan explicitly specifies `auth:sanctum`. Sanctum is the idiomatic Laravel choice for SPA/mobile API token auth. It's already installed in the project.
**Alternatives considered**: Passport (OAuth2 — overkill for B2C personal app), JWT (no first-party Laravel support).

---

## Decision 2: Media Storage (Avatars & Exercise Images)

**Decision**: Spatie MediaLibrary with single-file collections (`singleFile()`).
**Rationale**: Plan specifies Spatie MediaLibrary. `singleFile()` enforces one file per collection, auto-deleting the previous file on upload — exactly what profile avatars and exercise demonstration images need.
**Alternatives considered**: Manual file storage (no cleanup guarantees, more boilerplate), Cloudinary/S3 direct (no local abstraction).

---

## Decision 3: Query Filtering & Sorting

**Decision**: Spatie QueryBuilder for `index` endpoints on body metrics and exercises.
**Rationale**: Plan specifies Spatie QueryBuilder. Provides declarative allowed-filter and allowed-sort lists, preventing column injection. Handles pagination natively.
**Alternatives considered**: Manual `when()` chains (verbose, error-prone), Scout (full-text only).

---

## Decision 4: One Body Metric Per Day

**Decision**: `updateOrCreate(['user_id' => $userId, 'logged_at' => $request->logged_at], $data)`.
**Rationale**: Plan explicitly specifies `updateOrCreate` matching on `logged_at`. This is atomic under a unique index on `(user_id, logged_at)`. A unique index should be added to the migration to prevent race conditions.
**Alternatives considered**: Check-then-insert (race condition), separate update endpoint (worse UX).

---

## Decision 5: System vs User-Owned Exercises

**Decision**: Nullable `user_id` on `exercises` table. NULL = system-wide, non-null = user-owned.
**Rationale**: Plan specifies this pattern. Simple, single-table, no joins needed for the availability scope. `scopeAvailableTo` encapsulates the `WHERE user_id IS NULL OR user_id = ?` logic cleanly.
**Alternatives considered**: Separate tables (sync overhead), polymorphic type column (more complex queries).

---

## Decision 6: Routine Exercise Sync Strategy

**Decision**: Delete-and-reinsert on `PUT /routines/{routine}` — delete all existing `routine_exercises` for the routine, then insert the new set from the request.
**Rationale**: Plan says "sync via Eloquent relationships". For a pivot with extra columns (order, target_sets, target_reps, target_rest_seconds), Eloquent's `sync()` only works for simple pivots. Delete-reinsert is simpler and correct.
**Alternatives considered**: Eloquent `sync()` (doesn't handle extra pivot columns cleanly), per-item diff (complex, no benefit at this scale).

---

## Decision 7: Analytics — Warmup Exclusion

**Decision**: `->where('set_type', '!=', 'warmup')` filter applied before all aggregations.
**Rationale**: Plan explicitly states "strictly exclude warmup sets" for both volume and personal records. This is a hard business rule.
**Alternatives considered**: None — requirement is non-negotiable.

---

## Decision 8: Volume Grouping Strategy

**Decision**: `?group_by=week` (default) or `?group_by=session` query parameter on `GET /analytics/volume`.
**Rationale**: Plan specifies "grouped by session or week (via query param)". Weekly grouping uses `YEARWEEK(workout_sessions.started_at)` or equivalent date truncation. Session grouping joins on `workout_session_id`.
**Alternatives considered**: Separate endpoints (more URLs, same logic duplicated).

---

## Decision 9: Routine Generator Default Target Values

**Decision**: `target_sets = 3`, `target_reps = 10`, `target_rest_seconds = 60` as defaults when the generator creates pivot rows.
**Rationale**: Plan says "attach selected exercises with default target values" without specifying what those defaults are. 3×10 with 60s rest is the standard hypertrophy default in fitness applications.
**Alternatives considered**: null defaults (poor UX, clients must handle nulls), configurable per-user (out of scope for V1).

---

## Decision 10: Response Standardization

**Decision**: All controllers extend `App\Http\Controllers\BaseController` and call `$this->success()`, `$this->error()`, or `$this->paginated()` exclusively.
**Rationale**: Plan explicitly mandates this. Ensures consistent JSON envelope across all 5 phases.
**Alternatives considered**: Laravel API Resources (not excluded, but must be wrapped by BaseController methods).
