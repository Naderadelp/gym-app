# Feature Specification: Personal Workout Tracker API

**Feature Branch**: `001-workout-tracker-api`
**Created**: 2026-04-24
**Status**: Draft
**Input**: User description: "Personal workout tracker API with 5 phases: user profiles and body metrics, exercise library, routines and smart workout generator, active workout sessions and logging, and progression analytics"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Manage Profile & Track Body Metrics (Priority: P1)

A fitness user wants to maintain a personal profile and log their body measurements over time so they can track their physical progress.

**Why this priority**: Profile and body metrics form the foundation of personalization. Without this, no other feature can tailor its experience to the individual user.

**Independent Test**: A user can register, update their profile details (name, display name, unit preference), upload a profile picture, and log daily body weight/height/body fat — then retrieve the latest measurement alongside their profile.

**Acceptance Scenarios**:

1. **Given** an authenticated user, **When** they update their display name and unit preference to imperial, **Then** the profile reflects the new values immediately.
2. **Given** an authenticated user, **When** they upload a profile picture, **Then** the profile response includes a valid avatar URL.
3. **Given** an authenticated user, **When** they log body weight on the same calendar day twice, **Then** only one entry exists for that day (the second overwrites the first).
4. **Given** an authenticated user, **When** they request their profile, **Then** the response includes their latest body metric entry ordered by log date.
5. **Given** an authenticated user, **When** they request their body metrics list, **Then** results are paginated and sortable by log date.

---

### User Story 2 - Browse & Manage Exercise Library (Priority: P2)

A user wants to browse a shared library of exercises and add their own custom exercises so they can build workouts tailored to their needs.

**Why this priority**: The exercise library is the core content layer. Routines and workout sessions depend on exercises existing before they can be built.

**Independent Test**: A user can view all available exercises (system-wide + their own), filter by muscle group or difficulty, create a custom exercise with an optional demonstration image, and edit or delete only their own entries.

**Acceptance Scenarios**:

1. **Given** an authenticated user, **When** they request the exercise list, **Then** they see both system-wide exercises and their own custom exercises, filtered by muscle group or difficulty level if requested.
2. **Given** an authenticated user, **When** they create a custom exercise, **Then** the exercise is saved under their account and optionally includes a demonstration image.
3. **Given** an authenticated user, **When** they attempt to edit a system-wide exercise, **Then** the request is rejected with a permission error.
4. **Given** an authenticated user, **When** they delete their own custom exercise, **Then** it is removed from their available exercise list.

---

### User Story 3 - Build & Auto-Generate Workout Routines (Priority: P3)

A user wants to create structured workout routines manually or have the system generate a smart routine based on target muscle groups and difficulty so they can follow a consistent training plan.

**Why this priority**: Routines are the planning layer. They depend on exercises and are consumed by workout sessions.

**Independent Test**: A user can create a routine with a list of exercises and target sets/reps, update it, delete it, and also trigger auto-generation specifying a muscle group and difficulty level — receiving a balanced routine back instantly.

**Acceptance Scenarios**:

1. **Given** an authenticated user, **When** they create a routine with nested exercises and target values, **Then** the routine is saved with all exercise assignments and ordering intact.
2. **Given** an authenticated user, **When** they update a routine's exercise list, **Then** the pivot data is synced and old assignments are removed.
3. **Given** an authenticated user, **When** they request routine generation for a target muscle group and difficulty, **Then** the system returns a new routine with one exercise per sub-muscle group, selected randomly from eligible exercises.
4. **Given** an authenticated user, **When** no exercises match the generator criteria, **Then** the system returns a clear error indicating no eligible exercises were found.

---

### User Story 4 - Log an Active Workout Session (Priority: P4)

A user wants to start a workout session, log each set as they perform it (including weight, reps, and effort level), and mark the session complete when done.

**Why this priority**: Workout logging is the primary daily-use action. It produces the raw data that analytics depend on.

**Independent Test**: A user can start a session (optionally linked to a routine), log multiple sets for multiple exercises with full detail, delete a mistaken set, and finish the session — then view the session summary with sets grouped by exercise.

**Acceptance Scenarios**:

1. **Given** an authenticated user, **When** they start a workout session, **Then** a session record is created with the current time as the start time.
2. **Given** an active session, **When** the user logs a set with weight, reps, set type, and RPE, **Then** the set is saved and associated with the correct session and exercise.
3. **Given** an active session, **When** the user deletes a logged set, **Then** it is removed from the session.
4. **Given** an active session, **When** the user marks it as finished, **Then** the end time is recorded and the session is considered complete.
5. **Given** a completed session, **When** the user views its detail, **Then** all logged sets are returned grouped by exercise.

---

### User Story 5 - Track Progression & Personal Records (Priority: P5)

A user wants to view their volume trends over time and see their personal records per exercise so they can measure improvement and stay motivated.

**Why this priority**: Analytics close the feedback loop. They depend on accumulated session data from all prior phases.

**Independent Test**: A user with existing session history can query total volume grouped by week, and retrieve their personal best weight lifted for any exercise — with warmup sets always excluded from both calculations.

**Acceptance Scenarios**:

1. **Given** a user with logged sessions, **When** they request volume analytics grouped by week, **Then** they receive a time-series showing total volume (weight × reps) per week, excluding warmup sets.
2. **Given** a user with logged sessions, **When** they request personal records for a specific exercise, **Then** they see the maximum weight lifted in a non-warmup set for that exercise.
3. **Given** a user with logged sessions, **When** they request personal records without specifying an exercise, **Then** they see the personal best for every exercise they have logged.
4. **Given** a user whose only sets for an exercise are warmup sets, **When** they request personal records, **Then** that exercise does not appear in the results.

---

### Edge Cases

- What happens when a user logs body metrics for a date in the past or future?
- How does the system handle a routine generator request when all exercises for a muscle group belong to a single sub-muscle target?
- What happens when a user tries to finish a session that is already finished?
- How are workout volumes calculated when a set has no weight (e.g., bodyweight exercises)?
- What happens when a user deletes a custom exercise that is referenced by a routine or session log?

## Requirements *(mandatory)*

### Functional Requirements

**Phase 1 — User Profiles & Body Metrics**

- **FR-001**: System MUST allow authenticated users to view their own profile including avatar URL and latest body metric entry.
- **FR-002**: System MUST allow authenticated users to update their display name, full name, and unit preference (metric or imperial).
- **FR-003**: System MUST allow authenticated users to upload a profile picture; only one avatar is stored per user at a time.
- **FR-004**: System MUST allow authenticated users to log body metrics (weight, height, body fat percentage) for a specific date.
- **FR-005**: System MUST enforce one body metric entry per user per calendar day, overwriting the existing entry if one already exists for that date.
- **FR-006**: System MUST allow authenticated users to list their body metric history in paginated form, sortable by log date.
- **FR-007**: System MUST allow authenticated users to delete any of their own body metric entries.

**Phase 2 — Exercise Library**

- **FR-008**: System MUST allow authenticated users to browse all exercises that are either system-wide or belong to them.
- **FR-009**: System MUST allow authenticated users to filter exercises by primary muscle group, difficulty level, and equipment required.
- **FR-010**: System MUST allow authenticated users to create custom exercises linked to their account, with an optional demonstration image.
- **FR-011**: System MUST prevent users from editing or deleting system-wide exercises.
- **FR-012**: System MUST allow users to edit and delete their own custom exercises only.

**Phase 3 — Routines & Smart Generator**

- **FR-013**: System MUST allow authenticated users to create routines with nested exercise assignments including order, target sets, target reps, and target rest time.
- **FR-014**: System MUST synchronize the full exercise list on routine update, removing exercises no longer included and adding new ones.
- **FR-015**: System MUST allow authenticated users to delete their own routines.
- **FR-016**: System MUST allow authenticated users to generate a routine by specifying a primary muscle group and a maximum difficulty level.
- **FR-017**: The routine generator MUST group eligible exercises by sub-muscle target and select exactly one exercise per group at random.
- **FR-018**: The routine generator MUST persist the generated routine and attach the selected exercises with default target values.

**Phase 4 — Workout Sessions & Logging**

- **FR-019**: System MUST allow authenticated users to start a workout session, optionally linked to an existing routine.
- **FR-020**: System MUST default the session start time to the current time when not explicitly provided.
- **FR-021**: System MUST allow users to add individual set logs to an active session, capturing set number, weight, reps, duration, distance, RPE, and set type.
- **FR-022**: System MUST allow users to delete individual set logs.
- **FR-023**: System MUST allow users to mark a session as finished, recording the end time.
- **FR-024**: System MUST return session details with all set logs grouped by exercise when a session is viewed.

**Phase 5 — Analytics**

- **FR-025**: System MUST calculate and return total workout volume (weight × reps) from logged sets, grouped by session or by week based on a query parameter.
- **FR-026**: System MUST exclude warmup sets from all volume calculations.
- **FR-027**: System MUST return the maximum weight lifted per exercise (personal records), excluding warmup sets.
- **FR-028**: System MUST support filtering personal records by a specific exercise or returning records for all exercises the user has logged.

### Key Entities

- **User**: A registered individual with a profile, unit preference, and optional avatar.
- **BodyMetric**: A dated snapshot of a user's physical measurements (weight, height, body fat); one per user per day.
- **Exercise**: A named movement pattern with muscle targets and difficulty; either system-wide (shared) or user-owned (custom).
- **Routine**: A user-owned, named collection of exercises in a defined order with target performance values.
- **RoutineExercise**: The assignment of an exercise to a routine, including order, target sets, target reps, and rest time.
- **WorkoutSession**: A time-bounded event during which a user performs exercises; optionally based on a routine.
- **WorkoutLog**: A single logged set within a session, capturing performance data (weight, reps, duration, distance, RPE, set type).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can view their full profile including the latest body measurement in a single request, with no extra steps required.
- **SC-002**: Users can log a body metric, browse exercises, build a routine, start and finish a workout session, and view their personal records — completing the full workflow in under 10 minutes from a fresh start.
- **SC-003**: The routine generator returns a ready-to-use routine in under 3 seconds for any valid input combination.
- **SC-004**: All paginated list endpoints return results in under 2 seconds for users with up to 2 years of logged data.
- **SC-005**: Personal record and volume analytics correctly exclude warmup sets in 100% of cases, verified across all test scenarios.
- **SC-006**: Users can never access, modify, or delete data belonging to another user — zero cross-user data leaks.
- **SC-007**: System enforces one body metric entry per user per calendar day with no duplicates under concurrent submissions.

## Assumptions

- All API consumers are authenticated mobile or web clients; unauthenticated access to any data endpoint is not permitted.
- Unit conversion (metric ↔ imperial) is a display-layer concern — all data is stored in metric units (kg, cm) regardless of user preference.
- The system is single-tenant B2C; there are no admin roles or multi-tenancy requirements in V1.
- Exercises marked as system-wide are pre-seeded by the platform; no in-app interface for creating system exercises is needed in V1.
- A "warmup" set type is excluded from volume and personal record calculations; all other set types (normal, drop, failure) are included.
- Body fat percentage is optional and stored as-provided; the system does not calculate or validate it against weight/height.
- When a user's routine is deleted, existing workout sessions previously linked to it remain intact (the session retains a null routine reference).
- Demonstration images for exercises and avatar images for profiles are stored via a media management service already integrated into the platform.
- No push notifications, social features, or sharing functionality are in scope for V1.
