# Gym App — Project Planning

## Overview

A mobile-facing REST API built with **Laravel 13 + Sanctum**, serving a gym management system for a coach and his employees. Three roles: **Admin**, **Trainer**, and **Member**.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13 (API-only) |
| Auth | Laravel Sanctum (token-based) |
| Roles & Permissions | `spatie/laravel-permission` |
| Query (filter/sort/include) | `spatie/laravel-query-builder` |
| Media Uploads | `spatie/laravel-medialibrary` |
| Database | MySQL |
| Mobile Frontend | Flutter |
| Testing | Pest PHP |

---

## Roles

| Role | Description |
|---|---|
| **Admin** | Full control — manages users, trainers, plans, exercises |
| **Trainer** | Creates and manages workout plans for assigned members |
| **Member** | Employee/client — views plans, logs workouts, tracks progress |

Permission format: `{action}-{resource}` e.g. `create-exercise`, `index-user`, `delete-workout-plan`

---

## Architecture Layers

```
Request → FormRequest (validate + authorize) → Controller → Repository → Model
                                                    ↓
                                              QueryBuilder (filter/sort/include)
                                                    ↓
                                            ApiResource (response shape)
```

### Layer Responsibilities

| Layer | Responsibility |
|---|---|
| **FormRequest** | Validation rules + Policy authorization |
| **Controller** | Thin — calls repository, returns resource |
| **Repository** | Query logic, QueryBuilder config, media handling |
| **Model** | Eloquent relations, HasMedia, HasRoles |
| **Policy** | Fine-grained authorization per action |
| **Resource** | Response shaping — no raw model output |

---

## Repository Pattern

### Structure

```
app/Repositories/
  Contracts/
    BaseRepositoryInterface.php
    UserRepositoryInterface.php
    ExerciseRepositoryInterface.php
    WorkoutPlanRepositoryInterface.php
    WorkoutLogRepositoryInterface.php
  Eloquent/
    BaseRepository.php           ← shared CRUD + QueryBuilder wiring
    UserRepository.php
    ExerciseRepository.php
    WorkoutPlanRepository.php
    WorkoutLogRepository.php
```

### BaseRepositoryInterface

```php
interface BaseRepositoryInterface
{
    public function all(Request $request): LengthAwarePaginator;
    public function find(int $id): Model;
    public function create(array $data): Model;
    public function update(int $id, array $data): Model;
    public function delete(int $id): bool;
}
```

### BaseRepository — QueryBuilder wiring

Each concrete repository declares its allowed query params. The base repository
builds the Spatie QueryBuilder query from them automatically.

```php
abstract class BaseRepository
{
    protected array $allowedFilters = [];       // partial match (LIKE)
    protected array $allowedFiltersExact = [];  // exact match
    protected array $allowedFilterScopes = [];  // model scopes
    protected array $allowedSorts = [];
    protected array $allowedDefaultSorts = ['-id'];
    protected array $allowedIncludes = [];

    public function all(Request $request): LengthAwarePaginator
    {
        $query = QueryBuilder::for($this->model())
            ->allowedFilters($this->buildFilters())
            ->allowedSorts($this->allowedSorts)
            ->allowedIncludes($this->allowedIncludes)
            ->defaultSort(...$this->allowedDefaultSorts);

        return $query->paginate($request->per_page ?? 15);
    }
}
```

### Concrete Repository Example — ExerciseRepository

```php
class ExerciseRepository extends BaseRepository implements ExerciseRepositoryInterface
{
    protected array $allowedFilters      = ['name', 'description'];
    protected array $allowedFiltersExact = ['id', 'category', 'muscle_group'];
    protected array $allowedSorts        = ['id', 'name', 'category', 'created_at'];
    protected array $allowedDefaultSorts = ['-created_at'];
    protected array $allowedIncludes     = ['media', 'workoutPlans'];

    public function model(): string { return Exercise::class; }
}
```

### Media Repository Trait — HandleMediaEloquent

Models that implement `HasMedia` get a dedicated trait on their repository:

```php
trait HandleMediaEloquent
{
    public function createWithMedia(array $data, string $requestKey, string $collection = 'default'): HasMedia
    {
        $model = $this->create($data);
        if (request()->hasFile($requestKey)) {
            $model->addMediaFromRequest($requestKey)
                  ->usingFileName(md5(time()) . '.' . request()->file($requestKey)->extension())
                  ->toMediaCollection($collection);
        }
        return $model->load('media');
    }

    public function updateWithMedia(int $id, array $data, string $requestKey, string $collection = 'default', bool $sync = false): HasMedia
    {
        $model = $this->update($id, $data);
        if (request()->hasFile($requestKey)) {
            if ($sync) $model->clearMediaCollection($collection);
            $model->addMediaFromRequest($requestKey)
                  ->usingFileName(md5(time()) . '.' . request()->file($requestKey)->extension())
                  ->toMediaCollection($collection);
        }
        return $model->load('media');
    }
}
```

---

## Filtering, Sorting & Including (Query Parameters)

All list endpoints accept these query parameters via Spatie QueryBuilder:

### Filter
```
GET /api/exercises?filter[name]=bench
GET /api/exercises?filter[category]=Strength
GET /api/exercises?filter[muscle_group]=Chest
```

### Sort
```
GET /api/exercises?sort=name          ascending
GET /api/exercises?sort=-created_at   descending
```

### Include (eager load relations)
```
GET /api/exercises?include=media
GET /api/workout-plans?include=exercises,trainer,member
GET /api/users?include=roles,workoutPlans
```

### Pagination
```
GET /api/exercises?page=1&per_page=15
```

---

## Media Handling (Spatie Media Library)

Models with file uploads implement `HasMedia` + `InteractsWithMedia`.

### Models with media
| Model | Collection | Notes |
|---|---|---|
| `Exercise` | `cover` | Exercise demo image/gif |
| `User` | `avatar` | Profile photo |

### Media response shape (MediaResource)
```json
{1
  "id": 1,
  "collection_name": "cover",
  "file_name": "bench-press.jpg",
  "mime_type": "image/jpeg",
  "url": "https://...",
  "webp": "https://...webp"
}
```

### Upload endpoint pattern
```
POST /api/exercises          multipart/form-data  → image stored in 'cover' collection
PUT  /api/exercises/{id}     multipart/form-data  → replaces 'cover' (sync=true)
```

---

## Permissions & Policies

### Permission seeding
```php
// Seeded via RolesAndPermissionsSeeder
$permissions = [
    'index-user',   'show-user',   'create-user',   'edit-user',   'delete-user',
    'index-exercise','show-exercise','create-exercise','edit-exercise','delete-exercise',
    'index-workout-plan','show-workout-plan','create-workout-plan','edit-workout-plan','delete-workout-plan',
    'index-workout-log','show-workout-log','create-workout-log',
    'view-admin-stats',
];

// Role → Permission assignment
admin   → all permissions
trainer → index/show/create/edit exercise, index/show/create/edit workout-plan, index/show workout-log
member  → show own profile, show own workout-plan, create/index own workout-log
```

### Policy pattern (with super-admin bypass)
```php
class ExercisePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('index-exercise');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-exercise');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('delete-exercise');  // trainer cannot
    }
}
```

### FormRequest authorization
```php
class StoreExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Exercise::class);
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'category'     => ['required', 'string'],
            'muscle_group' => ['required', 'string'],
            'description'  => ['nullable', 'string'],
            'image'        => ['nullable', 'image', 'max:2048'],
        ];
    }
}
```

---

## Response Structure (API Resources)

### BaseResource
```php
abstract class BaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->data($request);
    }

    abstract protected function data(Request $request): array;
}
```

### BaseController
```php
abstract class BaseController extends Controller
{
    protected function success($data, int $status = 200, string $message = ''): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }
}
```

### Standard response envelopes
```json
// List
{
  "success": true,
  "data": [ ... ],
  "meta": { "current_page": 1, "per_page": 15, "total": 42 }
}

// Single
{
  "success": true,
  "data": { ... }
}

// Error
{
  "success": false,
  "message": "Validation failed",
  "errors": { "name": ["The name field is required."] }
}
```

---

## Database Schema

### `users`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| name | string | |
| email | string | unique |
| mobile | string | unique |
| password | string | hashed |
| age | integer | |
| height | decimal(5,2) | cm |
| weight | decimal(5,2) | kg |
| email_verified_at | timestamp | nullable |
| remember_token | string | nullable |
| timestamps | | |

### `exercises`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| name | string | |
| description | text | nullable |
| category | string | Strength / Cardio / Flexibility |
| muscle_group | string | Chest / Back / Legs / etc. |
| timestamps | | |
> Media (images/gifs) stored via Spatie Media Library — no image_url column

### `workout_plans`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| trainer_id | FK → users | |
| member_id | FK → users | |
| name | string | |
| description | text | nullable |
| start_date | date | |
| end_date | date | |
| status | enum | active, completed, paused |
| timestamps | | |

### `workout_plan_exercises` (pivot)
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| workout_plan_id | FK | |
| exercise_id | FK | |
| sets | integer | |
| reps | integer | nullable |
| duration_seconds | integer | nullable |
| rest_seconds | integer | |
| notes | text | nullable |
| order | integer | display order |
| timestamps | | |

### `workout_logs`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| member_id | FK → users | |
| workout_plan_id | FK | nullable |
| exercise_id | FK | |
| sets_done | integer | |
| reps_done | integer | nullable |
| weight | decimal(5,2) | nullable — weight used |
| duration_seconds | integer | nullable |
| notes | text | nullable |
| logged_at | timestamp | |
| timestamps | | |

---

## API Endpoints

### Auth (Public)
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
POST   /api/auth/forgot-password
POST   /api/auth/reset-password
```

### Profile (Authenticated)
```
GET    /api/profile
PUT    /api/profile
PUT    /api/profile/password
```

### Users (Admin only)
```
GET    /api/users              ?filter[name]=&filter[role]=&sort=&include=roles&page=
POST   /api/users
GET    /api/users/{id}         ?include=roles,workoutPlans
PUT    /api/users/{id}
DELETE /api/users/{id}
```

### Exercises
```
GET    /api/exercises           ?filter[name]=&filter[category]=&sort=name&include=media&page=
POST   /api/exercises           multipart/form-data (image optional)
GET    /api/exercises/{id}      ?include=media,workoutPlans
PUT    /api/exercises/{id}      multipart/form-data
DELETE /api/exercises/{id}      Admin only
```

### Workout Plans
```
GET    /api/workout-plans       ?filter[status]=&include=exercises,trainer,member&sort=-created_at
POST   /api/workout-plans
GET    /api/workout-plans/{id}  ?include=exercises.media,member,trainer
PUT    /api/workout-plans/{id}
DELETE /api/workout-plans/{id}
POST   /api/workout-plans/{id}/exercises
DELETE /api/workout-plans/{id}/exercises/{exerciseId}
```

### Workout Logs
```
GET    /api/logs                ?filter[exercise_id]=&sort=-logged_at&include=exercise.media
POST   /api/logs
GET    /api/members/{id}/logs   ?include=exercise&sort=-logged_at
GET    /api/members/{id}/progress
```

### Admin
```
GET    /api/admin/stats
```

---

## Full File Structure

```
app/
  Http/
    Controllers/
      BaseController.php
      AuthController.php
      ProfileController.php
      UserController.php
      ExerciseController.php
      WorkoutPlanController.php
      WorkoutLogController.php
      AdminController.php
    Requests/
      BaseRequest.php
      Auth/
        RegisterRequest.php
        LoginRequest.php
        ForgotPasswordRequest.php
        ResetPasswordRequest.php
      User/
        StoreUserRequest.php
        UpdateUserRequest.php
      Exercise/
        StoreExerciseRequest.php
        UpdateExerciseRequest.php
      WorkoutPlan/
        StoreWorkoutPlanRequest.php
        UpdateWorkoutPlanRequest.php
        AttachExerciseRequest.php
      WorkoutLog/
        StoreWorkoutLogRequest.php
    Resources/
      BaseResource.php
      MediaResource.php
      UserResource.php
      ExerciseResource.php
      WorkoutPlanResource.php
      WorkoutPlanExerciseResource.php
      WorkoutLogResource.php
  Models/
    User.php           (HasMedia, HasRoles, HasApiTokens)
    Exercise.php       (HasMedia)
    WorkoutPlan.php
    WorkoutPlanExercise.php
    WorkoutLog.php
  Policies/
    UserPolicy.php
    ExercisePolicy.php
    WorkoutPlanPolicy.php
    WorkoutLogPolicy.php
  Repositories/
    Contracts/
      BaseRepositoryInterface.php
      UserRepositoryInterface.php
      ExerciseRepositoryInterface.php
      WorkoutPlanRepositoryInterface.php
      WorkoutLogRepositoryInterface.php
    Eloquent/
      BaseRepository.php
      UserRepository.php
      ExerciseRepository.php
      WorkoutPlanRepository.php
      WorkoutLogRepository.php
    Traits/
      HandleMediaEloquent.php
  Providers/
    RepositoryServiceProvider.php   ← binds interfaces to implementations
database/
  migrations/
  seeders/
    RolesAndPermissionsSeeder.php
    DatabaseSeeder.php
routes/
  api.php
tests/
  Feature/
    Auth/
    Exercise/
    WorkoutPlan/
    WorkoutLog/
```

---

## Packages to Install

```bash
composer require laravel/sanctum
composer require spatie/laravel-permission
composer require spatie/laravel-query-builder
composer require spatie/laravel-medialibrary
```

---

## Implementation Phases

### Phase 1 — Foundation
- [x] Laravel 13 project setup
- [x] Sanctum installed & configured
- [x] Spatie Permission installed
- [ ] Install `spatie/laravel-query-builder`
- [ ] Install `spatie/laravel-medialibrary`
- [ ] Update users migration → `mobile`, `age`, `height`, `weight`
- [ ] Seed roles + permissions (`RolesAndPermissionsSeeder`)
- [ ] Seed admin user

### Phase 2 — Base Infrastructure
- [ ] `BaseController` (success/error response helpers)
- [ ] `BaseRequest`
- [ ] `BaseResource`
- [ ] `MediaResource`
- [ ] `BaseRepository` + `BaseRepositoryInterface`
- [ ] `HandleMediaEloquent` trait
- [ ] `RepositoryServiceProvider` (bind interfaces)

### Phase 3 — Authentication
- [ ] `AuthController` (register, login, logout)
- [ ] `PasswordResetController`
- [ ] Auth FormRequests
- [ ] `UserResource` (safe — no password)
- [ ] Feature tests

### Phase 4 — Exercises Module
- [ ] `Exercise` model (`HasMedia`)
- [ ] `ExerciseRepository` (filters, sorts, includes, media)
- [ ] `ExercisePolicy`
- [ ] `ExerciseController`
- [ ] Exercise FormRequests
- [ ] `ExerciseResource` (with `whenLoaded('media')`)
- [ ] Feature tests

### Phase 5 — Workout Plans Module
- [ ] `WorkoutPlan` + `WorkoutPlanExercise` models
- [ ] `WorkoutPlanRepository`
- [ ] `WorkoutPlanPolicy`
- [ ] `WorkoutPlanController`
- [ ] Feature tests

### Phase 6 — Workout Logs
- [ ] `WorkoutLog` model
- [ ] `WorkoutLogRepository`
- [ ] `WorkoutLogPolicy`
- [ ] `WorkoutLogController`
- [ ] Progress summary endpoint
- [ ] Feature tests

### Phase 7 — Admin & Polish
- [ ] `AdminController` (stats)
- [ ] `UserController` (admin CRUD + role assign)
- [ ] Rate limiting
- [ ] Postman collection

---

## Permission Matrix

| Action | Admin | Trainer | Member |
|---|:---:|:---:|:---:|
| Register / Login / Logout | ✓ | ✓ | ✓ |
| View/Edit own profile | ✓ | ✓ | ✓ |
| `index-user` / `show-user` | ✓ | — | — |
| `create-user` / `edit-user` / `delete-user` | ✓ | — | — |
| `index-exercise` / `show-exercise` | ✓ | ✓ | ✓ |
| `create-exercise` / `edit-exercise` | ✓ | ✓ | — |
| `delete-exercise` | ✓ | — | — |
| `create-workout-plan` / `edit-workout-plan` | ✓ | ✓ | — |
| `show-workout-plan` (own) | ✓ | ✓ | ✓ |
| `delete-workout-plan` | ✓ | — | — |
| `create-workout-log` | — | — | ✓ |
| `index-workout-log` (own) | — | — | ✓ |
| `index-workout-log` (any member) | ✓ | ✓ | — |
| `view-admin-stats` | ✓ | — | — |
