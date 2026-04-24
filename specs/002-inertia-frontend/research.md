# Research: Workout Tracker Web Interface (V1)

**Branch**: `002-inertia-frontend` | **Date**: 2026-04-24

All decisions were deterministic from the plan. Documented for traceability.

---

## Decision 1: Inertia.js vs. Separate SPA

**Decision**: Inertia.js with Laravel backend rendering page data server-side.
**Rationale**: Plan explicitly specifies Inertia.js. Eliminates duplicate API endpoints for the web app. Controllers pass typed PHP data directly to Vue pages. Session auth works natively — no Sanctum tokens needed.
**Alternatives considered**: Vue SPA + separate REST API (more moving parts, auth complexity), Livewire (different mental model, less Vue flexibility for real-time session tracker).

---

## Decision 2: Set Logging Without Full Page Reload

**Decision**: `router.post(route, data, { preserveScroll: true, only: ['logsByExercise'] })` — Inertia partial reload targeting only the `logsByExercise` prop.
**Rationale**: Inertia's `only` option is the idiomatic solution for updating a subset of page props without a full navigation. Avoids Axios and keeps the data flow server-authoritative.
**Alternatives considered**: Axios direct (loses Inertia error handling, needs manual state update), Vuex/Pinia store (adds state management complexity for a simple list).

---

## Decision 3: Elapsed Workout Timer

**Decision**: `setInterval` every second in `WorkoutTimer.vue`, computing diff between `Date.now()` and `new Date(props.startedAt)`.
**Rationale**: `started_at` is provided by the server as a prop — the timer is purely presentational. No WebSocket or polling needed. If the user refreshes, the timer re-hydrates from the server-provided `started_at` and immediately shows the correct elapsed time.
**Alternatives considered**: WebSocket push (overkill for a display-only timer), server polling (network overhead for seconds-level updates).

---

## Decision 4: Rest Countdown Timer

**Decision**: Local component state (`remaining`, `running` refs) with `setInterval`; configurable `defaultSeconds` prop (default: 60 seconds).
**Rationale**: Purely client-side ephemeral state — no need to persist to server. User triggers it manually after completing a set.
**Alternatives considered**: Persistent server-side rest timer (over-engineered; rest time doesn't need to survive page refresh).

---

## Decision 5: Exercise Filtering (Server vs. Client)

**Decision**: Server-side filtering via `router.get(route, filters, { preserveState: true })` — Inertia re-renders only the exercise list.
**Rationale**: Exercises are paginated (20 per page). Client-side filtering on a single page of 20 results would not show results from other pages. Server-side filtering with `preserveState: true` maintains scroll position and filter state.
**Alternatives considered**: Client-side `computed` filter (breaks pagination), Algolia/search index (overkill for a personal tracker).

---

## Decision 6: Routine Exercise Sync (Web)

**Decision**: Same delete-and-reinsert pattern as the API controller. `RoutineController@store` and `@update` delete all `RoutineExercises` for the routine and reinsert from the submitted form array.
**Rationale**: Consistent with API decision. Simplest correct approach for a pivot with extra columns.
**Alternatives considered**: Per-item diff (complex, no benefit at V1 scale).

---

## Decision 7: Chart Library

**Decision**: `vue-chartjs` wrapping `chart.js` for the volume bar chart.
**Rationale**: Plan specifies `chart.js` via `vue-chartjs`. Most widely used Vue 3 chart integration. Supports bar and line charts. Bundle size acceptable for a single analytics page.
**Alternatives considered**: ApexCharts (heavier), ECharts (heavier), D3 (too low-level for a simple bar chart).

---

## Decision 8: Avatar & Demonstration Image Upload

**Decision**: `useForm({ file: null }, { forceFormData: true })` from `@inertiajs/vue3` — forces multipart encoding automatically.
**Rationale**: Inertia's `useForm` with `forceFormData: true` handles file uploads natively. `file` input bound to the form object. No manual FormData construction needed.
**Alternatives considered**: Axios with manual FormData (more boilerplate, bypasses Inertia error handling), pre-signed S3 URLs (overkill for a self-hosted tracker).

---

## Decision 9: Unsaved-Changes Warning on Active Session

**Decision**: `beforeunload` browser event + Inertia's `router.on('before', ...)` hook to detect navigation away from `ActiveSession.vue` when the set input form has unsaved data.
**Rationale**: Spec FR-026 requires a warning. `beforeunload` covers browser close/refresh; Inertia's `before` hook covers in-app navigation.
**Alternatives considered**: `vue-router` navigation guard (not applicable — Inertia handles routing, not vue-router directly).

---

## Decision 10: Web Auth (Session vs. Sanctum)

**Decision**: Standard Laravel session-based auth via `auth` middleware on web routes.
**Rationale**: Plan explicitly specifies this. Inertia web apps run in the same browser session as the Laravel app — cookies handle auth transparently. No Bearer tokens needed.
**Alternatives considered**: Sanctum SPA cookies (works but adds complexity for same-origin apps), Sanctum API tokens (wrong mode for Inertia).
