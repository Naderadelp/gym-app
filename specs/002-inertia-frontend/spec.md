# Feature Specification: Workout Tracker Web Interface (V1)

**Feature Branch**: `002-inertia-frontend`
**Created**: 2026-04-24
**Status**: Draft
**Input**: User description: "Inertia.js Vue 3 web frontend for the personal workout tracker — 5 phases covering dashboard, exercise library, routine builder, live workout tracking, and analytics"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - View Dashboard & Manage Profile (Priority: P1)

After logging in, a user lands on a personalised dashboard showing their recent workout activity, weekly training volume, and a shortcut to start a new session. From their profile page, they update their display name and unit preference, upload a new profile photo, and review their body measurement history.

**Why this priority**: The dashboard and profile are the entry point and identity layer for the entire application. Users must be able to land somewhere meaningful after login before any other feature is useful.

**Independent Test**: Log in → see the dashboard with weekly volume summary and latest workout info → navigate to Profile → update display name → upload avatar → log today's weight → see the new entry appear in the measurement history table.

**Acceptance Scenarios**:

1. **Given** an authenticated user, **When** they visit the dashboard, **Then** they see their weekly training volume, the date of their most recent workout, and a button to start a new empty workout session.
2. **Given** an authenticated user, **When** they visit their profile page, **Then** they see their current display name, unit preference toggle (Metric/Imperial), current avatar, and a table of past body measurements.
3. **Given** an authenticated user on the profile page, **When** they upload a new photo and save, **Then** the new avatar is displayed immediately without a full page reload.
4. **Given** an authenticated user on the profile page, **When** they log today's weight, **Then** the entry appears at the top of the measurement history table.
5. **Given** an unauthenticated visitor, **When** they access any protected page, **Then** they are redirected to the login screen.

---

### User Story 2 - Browse & Create Exercises (Priority: P2)

A user can browse the exercise library in a visual grid layout and filter it by muscle group or equipment type. They can create their own custom exercise with an optional demonstration photo using a modal form — without leaving the exercise list page.

**Why this priority**: The exercise library is the content foundation for building routines and logging workouts. It must be browsable before routines or sessions can be created.

**Independent Test**: Open exercise library → filter by "chest" muscle group → verify only chest exercises appear → open the Create modal → fill in exercise name, muscle group, and equipment → upload a demonstration image → submit → new exercise appears in the grid.

**Acceptance Scenarios**:

1. **Given** an authenticated user on the exercise library page, **When** they apply a muscle group filter, **Then** only exercises matching that muscle group are displayed.
2. **Given** an authenticated user on the exercise library page, **When** they apply an equipment filter, **Then** only exercises requiring that equipment are displayed.
3. **Given** an authenticated user, **When** they open the Create Exercise modal and submit valid data, **Then** the new exercise appears in the grid without a full page reload.
4. **Given** an authenticated user, **When** they attempt to edit or delete a system-wide exercise, **Then** the edit and delete controls are not visible for those exercises.

---

### User Story 3 - Build & Generate Routines (Priority: P3)

A user manages their saved workout routines. They can manually build a new routine by searching for exercises, adding them in order, and setting target sets/reps. Alternatively, they trigger the Smart Generator with a muscle group and difficulty choice to receive an instant balanced routine.

**Why this priority**: Routines are the planning layer. Building and saving them prepares the user to start guided workout sessions.

**Independent Test**: Open routines list → click "Generate Smart Workout" → choose muscle group and difficulty → generator creates a routine → routine appears in the list → open the manual Builder → add 3 exercises → set target values → save → routine appears in the list.

**Acceptance Scenarios**:

1. **Given** an authenticated user on the routines page, **When** they open the Smart Generator modal and submit, **Then** they are redirected to the newly generated routine's detail view.
2. **Given** an authenticated user on the routine builder page, **When** they search for an exercise by name, **Then** matching exercises appear as selectable options.
3. **Given** an authenticated user building a routine, **When** they add an exercise, **Then** it appears in the list with inputs for target sets and reps.
4. **Given** an authenticated user building a routine, **When** they save, **Then** the routine is persisted and they are redirected to the routines list.

---

### User Story 4 - Track a Live Workout Session (Priority: P4)

A user starts a workout session and is taken to a live tracking page. A running timer shows elapsed time. For each exercise, they see rows for each set and fill in weight, reps, and set type, then tap "Log Set" to save it without the page reloading. After completing a set, they can start a rest countdown timer. When done, they tap "Finish Workout" to close the session.

**Why this priority**: Live workout tracking is the primary daily-use feature — the reason the app exists. It requires real-time client-side interactivity.

**Independent Test**: Start an empty session → timer starts → add 3 sets for 2 exercises (including one warmup set) → delete one set → start rest timer after a set → finish the session → confirm the session appears in history with the correct set count.

**Acceptance Scenarios**:

1. **Given** an authenticated user who starts a workout, **When** the session page loads, **Then** a running timer counts upward from zero based on the session start time.
2. **Given** an authenticated user on the active session page, **When** they fill in weight, reps, and set type and tap "Log Set", **Then** the set is saved and appears in the list without a full page reload.
3. **Given** an authenticated user who has logged a set, **When** they tap the rest timer, **Then** a countdown begins with a configurable default duration.
4. **Given** an authenticated user on the active session page, **When** they tap "Finish Workout", **Then** the session is closed and they are redirected to a summary or the dashboard.
5. **Given** an authenticated user on the active session page, **When** they delete a mistakenly logged set, **Then** it disappears from the list immediately without a page reload.

---

### User Story 5 - View Progress & Personal Records (Priority: P5)

A user navigates to an analytics page showing a weekly volume chart (last 12 weeks) and a personal records table listing their best lift per exercise. Both views exclude warmup sets from calculations.

**Why this priority**: Analytics close the feedback loop, motivating users by showing measurable improvement. They depend on accumulated session data.

**Independent Test**: Navigate to Analytics → volume chart shows bars for each of the last 12 weeks — weeks with no data show zero — warmup sets are excluded from all totals → PR table lists each exercised exercise with the max weight ever lifted (non-warmup).

**Acceptance Scenarios**:

1. **Given** a user with logged sessions, **When** they view the analytics page, **Then** the volume chart displays weekly totals for the most recent 12 weeks, with warmup sets excluded.
2. **Given** a user with logged sessions, **When** they view the personal records table, **Then** each exercise shows the maximum weight lifted in a non-warmup set.
3. **Given** a user with no logged sessions, **When** they view the analytics page, **Then** the chart shows an empty/zero state with a helpful message.

---

### Edge Cases

- What happens when a user starts a workout session and then closes the browser? (Session remains open; timer resumes on next visit.)
- What if the routine generator returns no exercises for the selected criteria? (Show an inline error message; do not navigate away.)
- What if a user tries to navigate away from an active workout session? (Show a browser confirmation dialog warning unsaved sets will be lost.)
- How does the set-logging form behave when weight or reps is left blank for a bodyweight exercise?
- What happens when the dashboard is viewed by a brand-new user with no workout history?

## Requirements *(mandatory)*

### Functional Requirements

**Phase 1 — Dashboard & Profile**

- **FR-001**: System MUST display a dashboard showing the user's weekly training volume and most recent workout date after login.
- **FR-002**: System MUST provide a prominent action to start a new empty workout session from the dashboard.
- **FR-003**: System MUST allow users to update their display name and unit preference via the profile page.
- **FR-004**: System MUST allow users to upload a new profile photo from the profile page; the new photo must display immediately after upload.
- **FR-005**: System MUST display the user's body measurement history in a table on the profile page, with the most recent entry first.
- **FR-006**: System MUST allow users to log a new body measurement (weight) from the profile page.
- **FR-007**: System MUST redirect unauthenticated users to the login screen when they access any protected page.

**Phase 2 — Exercise Library**

- **FR-008**: System MUST display exercises in a visual grid layout with at least the exercise name, target muscle, and difficulty visible per card.
- **FR-009**: System MUST allow users to filter the exercise grid by primary muscle group and by equipment required using dropdown controls.
- **FR-010**: System MUST provide a Create Exercise modal accessible without leaving the exercise list page.
- **FR-011**: System MUST allow users to upload a demonstration image when creating a custom exercise.
- **FR-012**: After successfully creating an exercise, the new card MUST appear in the grid without a full page navigation.
- **FR-013**: System MUST hide edit and delete controls on exercises the user does not own.

**Phase 3 — Routine Builder & Generator**

- **FR-014**: System MUST display the user's saved routines as cards with name and exercise count visible.
- **FR-015**: System MUST provide a "Generate Smart Workout" trigger that opens a modal asking for target muscle group and difficulty.
- **FR-016**: After generation, the system MUST redirect the user to the newly created routine's detail page.
- **FR-017**: The manual routine builder MUST include an exercise search field that filters available exercises by name as the user types.
- **FR-018**: Each added exercise MUST show editable inputs for target sets and target reps.
- **FR-019**: Saving a routine MUST redirect the user to the routines list.

**Phase 4 — Live Workout Session**

- **FR-020**: System MUST display a running elapsed-time timer on the active session page, updating every second.
- **FR-021**: System MUST allow the user to log a set (weight, reps, set type) and save it without a full page reload.
- **FR-022**: System MUST display all logged sets for the current session grouped by exercise.
- **FR-023**: System MUST allow the user to delete a logged set without a full page reload.
- **FR-024**: System MUST provide a rest countdown timer that users can start after completing a set.
- **FR-025**: System MUST provide a "Finish Workout" button that ends the session and redirects the user.
- **FR-026**: System MUST warn users attempting to navigate away from an active session with unsaved input.

**Phase 5 — Analytics**

- **FR-027**: System MUST display a bar or line chart of total weekly training volume for the last 12 weeks; warmup sets are excluded.
- **FR-028**: System MUST display a personal records table listing the maximum weight lifted per exercise (warmup sets excluded).
- **FR-029**: System MUST show a zero/empty state with a helpful message when the user has no session history.

### Key Entities

- **Session Summary**: A time-bounded workout event shown with start time, duration, and set count.
- **Exercise Card**: A visual representation of an exercise showing name, muscle target, difficulty, and optional demonstration image.
- **Routine Card**: A named workout template showing exercise count and a launch action.
- **Set Log**: A single recorded set with weight, reps, set type, and RPE captured during a live session.
- **Volume Data Point**: A weekly aggregate of total volume (weight × reps), warmup sets excluded.
- **Personal Record**: The maximum weight lifted for a given exercise across all non-warmup sets.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can log in and reach the dashboard in under 5 seconds on a standard broadband connection.
- **SC-002**: Users can log a set during a live workout session in under 10 seconds from tapping "Log Set" to seeing it confirmed in the list.
- **SC-003**: The exercise library loads and displays the first page of results within 2 seconds; filter changes apply within 1 second.
- **SC-004**: The analytics page renders the volume chart and PR table within 3 seconds for users with up to 2 years of data.
- **SC-005**: The workout timer remains accurate (within ±1 second of elapsed time) continuously for sessions up to 3 hours.
- **SC-006**: 100% of set-save operations during an active session complete without a full page reload.
- **SC-007**: Users with no workout history see a helpful empty state (not a blank or broken screen) on all data-driven pages.

## Assumptions

- The web application shares the same authenticated user session as the backend; login/registration pages are handled by the existing auth system and are not in scope for this feature.
- All data operations (save set, generate routine, etc.) communicate with the same Laravel backend that powers the mobile API, using server-side form submissions or Inertia partial reloads — not direct REST API calls.
- The live workout timer runs entirely client-side using the `started_at` timestamp provided by the server; no WebSocket or polling is required.
- The rest countdown timer uses a sensible default duration (e.g., 60 seconds); users can dismiss or reset it at any time.
- Charts display the last 12 calendar weeks of data; no custom date range selection is in scope for V1.
- Unit preference (metric/imperial) affects the display labels on the dashboard and session pages but does not re-convert stored values (storage is always metric).
- The application is designed for a single authenticated user at a time; no multi-user collaboration or sharing features are in scope.
- Navigation between pages uses standard browser navigation enhanced by Inertia for smooth transitions; deep linking to any page is supported.
